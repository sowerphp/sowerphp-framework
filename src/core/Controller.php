<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
 * Copyright (C) SowerPHP <https://www.sowerphp.org>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero
 * de GNU publicada por la Fundación para el Software Libre, ya sea la
 * versión 3 de la Licencia, o (a su elección) cualquier versión
 * posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU
 * para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General
 * Affero de GNU junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace sowerphp\core;

use \sowerphp\core\Facade_Session_Message as SessionMessage;

/**
 * Clase base para los controladores de la aplicación.
 */
abstract class Controller
{

    /**
     * Instancia de la solicitud.
     *
     * @var Network_Request
     */
    public $request;

    /**
     * Instancia de la respuesta.
     *
     * @var Network_Response
     */
    public $response;

    /**
     * Variables que se pasarán al renderizar la vista.
     *
     * @var array
     */
    public $viewVars = [];

    public $autoRender = true; ///< Autorenderizar una vista asociada a una acción.
    public $Components = null; ///< Colección de componentes que se cargarán.
    public $components = []; ///< Nombre de componentes que este controlador utiliza.
    public $layout; ///< Layout que se usará por defecto para renderizar.
    protected $redirect = null; ///< Donde redireccionar una vez que se ha terminado de ejecutar la acción (incluyendo renderizado de vista).

    /**
     * Constructor de la clase controlador.
     *
     * @param Network_Request $request Instancia con la solicitud realizada.
     * @param Network_Response $response Instancia para la respuesta que se
     * enviará al cliente.
     */
    public function __construct(
        Network_Request $request,
        Network_Response $response
    )
    {
        // Guardar instancias de la solicitud y respuesta.
        $this->request = $request;
        $this->response = $response;
        // Obtener layout por defecto (el de la sesión).
        $this->layout = session('config.app.ui.layout');
        // Crear colección de componentes, las clases e iniciar los atributos
        // del controlador con los componentes.
        if (count($this->components)) {
            $this->Components = new Controller_Component_Collection();
            $this->Components->init($this);
        }
    }

    /**
     * Método que se ejecuta al iniciar la ejecución del controlador.
     */
    public function boot()
    {
        if ($this->Components) {
            $this->Components->trigger('boot');
        }
    }

    /**
     * Método que se ejecuta al terminar la ejecución del controlador.
     */
    public function terminate()
    {
        if ($this->redirect !== null) {
            if (!is_array($this->redirect)) {
                $this->redirect($this->redirect);
            } else {
                if (isset($this->redirect['msg'])) {
                    SessionMessage::info($this->redirect['msg']);
                }
                $this->redirect($this->redirect['page']);
            }
        }
        if ($this->Components) {
            $this->Components->trigger('terminate');
        }
    }

    /**
     * Guarda una(s) variable(s) para usarla en una vista.
     * @param $one Nombre de la variable o arreglo asociativo con variables.
     * @param $two Valor del variable o null si se paso un arreglo en one
     */
    public function set($one, $two = null)
    {
        // Si se pasó como arreglo se usa directamente.
        if (is_array($one)) {
            $data = $one;
        }
        // Si no se paso como arreglo se arma.
        else {
            $data = array($one => $two);
        }
        // Agregar a las variables que se usarán en la vista
        $this->viewVars = array_merge($this->viewVars, $data);
    }

    /**
     * Método que renderiza la vista del controlador.
     * @param string $view Vista que se desea renderizar.
     * @param string $location Ubicación de la vista.
     * @return Network_Response Respuesta con la página renderizada para enviar.
     */
    public function render(string $view = null, array $data = []): Network_Response
    {
        // Si no se especificó la vista se determina en base al controlador y
        // la acción solicitada.
        if (!$view) {
            $viewFolder = explode('Controller_', get_class($this))[1];
            $viewAction = $this->request->getRouteConfig()['action'];
            $view = $viewFolder . DIRECTORY_SEPARATOR . $viewAction;
        }
        // Preparar los datos que se pasarán a la vista para ser renderizada.
        $data = $data ? $data : $this->viewVars;
        $data['__view_layout'] = $data['__view_layout'] ?? $this->layout;
        // Renderizar vista y retornar.
        return view($view, $data);
    }

    /**
     * Redireccionar página.
     * @param string $uri Recurso o dirección web a donde se debe redireccionar.
     * @param int $status Estado de término del script PHP.
     */
    public function redirect(?string $uri = null, int $status = 0): void
    {
        if (!$uri) {
            $uri = $this->request->getRequestUriDecoded();
        }
        if ($uri[0] == '/') {
            header('location: ' . $this->request->getBaseUrlWithoutSlash() . $uri);
        } else {
            header('location: '.$uri);
        }
        exit($status);
    }

}
