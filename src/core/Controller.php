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

/**
 * Clase base para los controladores de la aplicación
 */
abstract class Controller
{

    public $request; ///< Objeto Request
    public $response; ///< Objeto Response
    public $viewVars = []; ///< Variables que se pasarán al renderizar la vista
    public $autoRender = true; ///< Autorenderizar una vista asociada a una acción
    public $Components = null; ///< Colección de componentes que se cargarán
    public $components = []; ///< Nombre de componentes que este controlador utiliza
    public $layout; ///< Layout que se usará por defecto para renderizar
    public $View = null; ///< Objeto para la vista que utilizará el controlador
    protected $redirect = null; ///< Donde redireccionar una vez que se ha terminado de ejecutar la acción (incluyendo renderizado de vista)

    /**
     * Constructor de la clase
     * @param request Objeto con la solicitud realizada
     * @param response Objeto para la respuesta que se enviará al cliente
     */
    public function __construct(Network_Request $request, Network_Response $response)
    {
        // copiar objeto para solicitud y respuesta
        $this->request = $request;
        $this->response = $response;
        // crear colección de componentes
        if (count($this->components)) {
            $this->Components = new Controller_Component_Collection();
            // crear las clases e inicializa atributos con componentes
            $this->Components->init($this);
        }
        // agregar variables por defecto que se pasarán a la vista
        $this->set(array(
            '_base' => $this->request->getBaseUrlWithoutSlash(),
            '_request' => $this->request->getRequestUriDecoded(),
            '_url' => $this->request->getFullUrlWithoutQuery(),
        ));
        // obtener layout por defecto (el de la sesión)
        $this->layout = app('session')->get('config.page.layout');
    }

    /**
     * Método que se ejecuta al iniciar la ejecución del controlador.
     * Wrapper de Controller::beforeFilter()
     */
    public function startupProcess()
    {
        $this->beforeFilter();
    }

    /**
     * Método que se ejecuta al terminar la ejecución del controlador
     * Wrapper de Controller::afterFilter()
     */
    public function shutdownProcess()
    {
        $this->afterFilter();
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
     * Método que se ejecuta al terminar la ejecución del controlador
     */
    public function afterFilter()
    {
        if ($this->redirect !== null) {
            if (!is_array($this->redirect)) {
                $this->redirect($this->redirect);
            } else {
                if (isset($this->redirect['msg'])) {
                    \sowerphp\core\Model_Datasource_Session::message(
                        $this->redirect['msg']
                    );
                }
                $this->redirect($this->redirect['page']);
            }
        }
        if ($this->Components) {
            $this->Components->trigger('afterFilter');
        }
    }

    /**
     * Método que se ejecuta antes de renderizar la página
     */
    public function beforeRender()
    {
        if ($this->Components) {
            $this->Components->trigger('beforeRender');
        }
    }

    /**
     * Método que se ejecuta antes de redirigir la página
     */
    public function beforeRedirect($params)
    {
        if ($this->Components) {
            $this->Components->trigger('beforeRedirect', $params);
        }
    }

    /**
     * Método que ejecuta el método público solicitado como acción del
     * controlador
     * @param return Valor de retorno de la acción ejecutada
     */
    public function invokeAction()
    {
        // Probar si el método existe
        try {
            // Obtener método
            $method = new \ReflectionMethod($this, $this->request->getParsedParams()['action']);
            // Verificar que el método no sea privado
            if ($method->name[0] === '_' || !$method->isPublic()) {
                throw new Exception_Controller_Action_Private(array(
                    'controller' => get_class($this),
                    'action' => $this->request->getParsedParams()['action']
                ));
            }
            // Verificar la cantidad de parámetros que se están pasando
            $n_args = count($this->request->getParsedParams()['pass']);
            if ($n_args<$method->getNumberOfRequiredParameters()) {
                $args = [];
                foreach($method->getParameters() as &$p) {
                    $args[] = $p->isOptional() ? '['.$p->name.']' : $p->name;
                }
                throw new Exception_Controller_Action_Args_Missing([
                    'controller' => get_class($this),
                    'action' => $this->request->getParsedParams()['action'],
                    'args' => implode(', ', $args)
                ]);
            }
            // Invocar el método con los argumentos de $request->getParsedParams()['pass']
            if ($n_args)
                return $method->invokeArgs($this, $this->request->getParsedParams()['pass']);
            else
                return $method->invoke($this);
        // Si el método no se encuentra
        } catch (\ReflectionException $e) {
            // Generar excepción
            throw new Exception_Controller_Action_Missing(array(
                    'controller' => get_class($this),
                    'action' => $this->request->getParsedParams()['action']
            ));
        }
    }

    /**
     * Método que renderiza la vista del controlador
     * @param view Vista que se desea renderizar
     * @param location Ubicación de la vista
     * @return Objeto Response con la página ya renderizada
     */
    public function render($view = null, $location = null)
    {
        // Ejecutar eventos que se deben realizar antes de renderizar
        $this->beforeRender();
        // Si la vista es nula se carga la vista según el controlador y accion solicitado
        if (!$view) {
            $view = Utility_Inflector::camelize($this->request->getParsedParams()['controller']).'/'.$this->request->getParsedParams()['action'];
        }
        // Crear vista para este controlador
        if (!$this->View) {
            $this->View = new View ($this);
        }
        // Renderizar vista y layout
        $this->response->body($this->View->render($view, $location));
        // Entregar respuesta
        return $this->response;
    }

    /**
     * Guarda una(s) variable(s) para usarla en una vista
     * @param one Nombre de la variable o arreglo asociativo
     * @param two Valor del variable o null si se paso un arreglo en one
     */
    public function set($one, $two = null)
    {
        // Si se paso como arreglo se usa
        if (is_array($one)) {
            $data = $one;
        }
        // Si no se paso como arreglo se arma
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
        $this->beforeRedirect(array($url, $status));
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
     * Método que entrega los valores de los parámetros solicitados.
     * Siempre y cuando estén presentes en la query de la URL (GET).
     * @param params Arreglo con los parámetros, si se manda param => value, value será el valor por defecto (sino será null).
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
            $vars[$param] = isset($_GET[$param]) ? urldecode($_GET[$param]) : $default;
        }
        return $vars;
    }

}
