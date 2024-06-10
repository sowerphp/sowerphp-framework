<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
 * Copyright (C) SowerPHP <https://www.sowerphp.org>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace sowerphp\core;

use \sowerphp\core\Facade_Session_Message as SessionMessage;

/**
 * Clase base para los controladores de la aplicación.
 */
abstract class Controller
{

    public $request; ///< Objeto Request.
    public $response; ///< Objeto Response.
    public $viewVars = []; ///< Variables que se pasarán al renderizar la vista.
    public $autoRender = true; ///< Autorenderizar una vista asociada a una acción.
    public $Components = null; ///< Colección de componentes que se cargarán.
    public $components = []; ///< Nombre de componentes que este controlador utiliza.
    public $layout; ///< Layout que se usará por defecto para renderizar.
    public $View = null; ///< Objeto para la vista que utilizará el controlador.
    protected $redirect = null; ///< Donde redireccionar una vez que se ha terminado de ejecutar la acción (incluyendo renderizado de vista).

    /**
     * Constructor de la clase controlador.
     *
     * @param Network_Request $request Instancia con la solicitud realizada.
     * @param Network_Response $response Instancia para la respuesta que se
     * enviará al cliente.
     */
    public function __construct(Network_Request $request, Network_Response $response)
    {
        // Copiar objeto para solicitud y respuesta.
        $this->request = $request;
        $this->response = $response;
        // Crear colección de componentes, las clases e iniciar los atributos
        // del controlador con los componentes.
        if (count($this->components)) {
            $this->Components = new Controller_Component_Collection();
            $this->Components->init($this);
        }
        // Agregar variables por defecto que se pasarán a la vista.
        $this->set([
            '_base' => $this->request->getBaseUrlWithoutSlash(),
            '_request' => $this->request->getRequestUriDecoded(),
            '_url' => $this->request->getFullUrlWithoutQuery(),
        ]);
        // Obtener layout por defecto (el de la sesión).
        $this->layout = session('config.page.layout');
    }

    /**
     * Método que se ejecuta al iniciar la ejecución del controlador.
     */
    public function beforeFilter()
    {
        if ($this->Components) {
            $this->Components->trigger('beforeFilter');
        }
    }

    /**
     * Método que se ejecuta al terminar la ejecución del controlador.
     */
    public function afterFilter()
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
            $this->Components->trigger('afterFilter');
        }
    }

    /**
     * Método que se ejecuta antes de renderizar la página.
     */
    public function beforeRender()
    {
        if ($this->Components) {
            $this->Components->trigger('beforeRender');
        }
    }

    /**
     * Método que se ejecuta antes de redirigir la página.
     */
    public function beforeRedirect($params)
    {
        if ($this->Components) {
            $this->Components->trigger('beforeRedirect', $params);
        }
    }

    /**
     * Método que renderiza la vista del controlador.
     * @param string $view Vista que se desea renderizar.
     * @param string $location Ubicación de la vista.
     * @return Network_Response Respuesta con la página renderizada para enviar.
     */
    public function render($view = null): Network_Response
    {
        // Si no se especificó la vista se determina en base al controlador y
        // la acción solicitada.
        if (!$view) {
            $viewFolder = explode('Controller_', get_class($this))[1];
            $viewAction = $this->request->getRouteConfig()['action'];
            $view = $viewFolder . DIRECTORY_SEPARATOR . $viewAction;
        }
        // Ejecutar eventos que se deben realizar antes de renderizar.
        $this->beforeRender();
        // Renderizar vista (con su layout), asignar al response y retornar.
        $body = $this->getView()->render($view);
        $this->response->body($body);
        return $this->response;
    }

    protected function getView()
    {
        return $this->View ?? new View($this);
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
     * Redireccionar página.
     * @param url Dirección web a donde se debe redireccionar
     * @param status Estado de término del script PHP
     */
    public function redirect(?string $url = null, int $status = 0): void
    {
        $this->beforeRedirect([$url, $status]);
        if (!$url) {
            $url = $this->request->getRequestUriDecoded();
        }
        if ($url[0] == '/') {
            header('location: '.$this->request->getBaseUrlWithoutSlash().$url);
        } else {
            header('location: '.$url);
        }
        exit($status);
    }

    /**
     * Método que entrega los valores de los parámetros solicitados por la URL.
     * Se entregarán siempre y cuando estén presentes en la query de la URL
     * (parámetros GET). Si no existen, se podrán entregar valores por defecto.
     *
     * @param array $params Arreglo con los parámetros, si es param => value,
     * value será el valor por defecto (sino será null el valor por defecto.
     * @return array Arreglo con los parámetros y sus valores.
     */
    public function getQuery(array $params): array
    {
        $vars = [];
        foreach ($params as $param => $default) {
            if (is_int($param)) {
                $param = $default;
                $default = null;
            }
            $vars[$param] = isset($_GET[$param])
                ? urldecode($_GET[$param])
                : $default
            ;
        }
        return $vars;
    }

}
