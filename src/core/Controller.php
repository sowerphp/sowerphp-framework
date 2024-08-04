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
    protected $viewVars = [];

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
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Método que se ejecuta al iniciar la ejecución del controlador.
     */
    public function boot(): void
    {
        // Determinar el método real que se desea ejecutar.
        // Se debe determinar porque si es de API no es la acción lanzada en el
        // controlador. Sino una con un formato de nombre diferente (de API).
        $routeConfig = $this->request->getRouteConfig();
        $action = $routeConfig['action'];
        if ($action == 'api' && !empty($routeConfig['parameters'][0])) {
            $action = '_api_'
                . $routeConfig['parameters'][0]
                . '_' . $this->request->method()
            ;
        }
        // Validar permisos para acceder a la acción del controlador que se
        // desea ejecutar (invocar).
        if (!app('auth')->checkFullAuthorization($action)) {
            $message = __(
                'Usuario no está autorizado para acceder a %s.',
                $this->request->getRequestUriDecoded()
            );
            if ($this->request->isApiRequest()) {
                throw new \Exception($message, 401);
            } else {
                redirect('/')->withError($message)->now();
            }
        }
    }

    /**
     * Método que se ejecuta al terminar la ejecución del controlador.
     */
    public function terminate(): void
    {
    }

    /**
     * Guarda una(s) variable(s) para usarla en una vista.
     *
     * @param mixed $one Nombre de la variable o arreglo asociativo con variables.
     * @param mixed $two Valor del variable o null si se paso un arreglo en $one.
     * @deprecated Se debe construir un arreglo con las variables y pasar a view().
     */
    public function set($one, $two = null): void
    {
        // Si se pasó como arreglo se usa directamente.
        if (is_array($one)) {
            $data = $one;
        }
        // Si no se paso como arreglo se arma.
        else {
            $data = [$one => $two];
        }
        // Agregar a las variables que se usarán en la vista.
        $this->viewVars = array_merge($this->viewVars, $data);
    }

    /**
     * Método que renderiza la vista del controlador.
     *
     * @param string $view Vista que se desea renderizar.
     * @param array $data Variables que se pasarán a la vista al renderizar.
     * @return Network_Response Respuesta con la página renderizada para enviar.
     * @deprecated Se debe llamar a view($view, $data) con la vista y datos.
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
        // Asignar layout del usuario si hay sesión iniciada y tiene layout
        // asignado en su configuración.
        $user = user();
        if ($user && $user->config_app_ui_layout) {
            $this->viewVars['__view_layout'] = $user->config_app_ui_layout;
        }
        // Preparar los datos que se pasarán a la vista para ser renderizada.
        $data = array_merge($this->viewVars, $data);
        // Renderizar vista y retornar.
        return view($view, $data);
    }

    /**
     * Método que lanza el servicio web (API) que se ha solicitado.
     *
     * @param string $resource Recurso de la API que se desea consumir.
     * @param array $args Argumentos variables que se pasaron a la API.
     * @return Network_Response Respuesta con la respuesta de la API para enviar.
     */
    public function api(string $resource, ...$args): Network_Response
    {
        $request = request();
        $method = '_api_' . $resource . '_' . $request->method();
        // Verificar si el método de la API existe.
        if (!method_exists($this, $method)) {
            return $this->response->json(
                __(
                    'La API de %s no tiene disponible el recurso %s mediante %s.',
                    $request->getRouteConfig()['controller'],
                    $resource,
                    $request->method()
                ),
                405
            );
        }
        // Ejecutar la acción de la API.
        $result = app('invoker')->call($this, $method, $args);
        // Generar respuesta del servicio web ejecutado.
        if (is_object($result) && $result instanceof Network_Response) {
            $response = $result;
        } else {
            $response = $this->response->json($result);
        }
        // Entregar respuesta de la API.
        return $response;
    }

    /**
     * Método que permite consumir por POST o GET un recurso de la misma
     * aplicación.
     * @deprecated Se debe usar http_client()->consume();
     */
    protected function consume(string $recurso, $datos = [], bool $assoc = true)
    {
        $user = user();
        $hash = $user ? $user->hash : config('auth.api.default_token');
        $rest = new \sowerphp\core\Network_Http_Rest();
        $rest->setAuth($hash);
        $rest->setAssoc($assoc);
        $url = url($recurso);
        if ($datos) {
            $response = $rest->post($url, $datos);
        } else {
            $response = $rest->get($url);
        }
        if ($response === false) {
            throw new \Exception(
                'Error al consumir internamente el recurso ' . $recurso
                    . ': ' . implode(' / ', $rest->getErrors())
            );
        }
        return $response;
    }

    /**
     * Método que permite ejecutar un comando en la terminal.
     * @deprecated Se debe usar servicio de jobs.
     */
    protected function shell($cmd, $log = false, &$output = [])
    {
        if ($log && !is_string($log)) {
            $log = DIR_TMP . '/screen_' . $this->request->fromIp() . '_' . date('YmdHis') . '.log';
        }
        return shell_exec_async($cmd, $log, $output);
    }

}
