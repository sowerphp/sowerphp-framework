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

class Service_Http_Kernel implements Interface_Service
{

    protected $layersService;
    protected $moduleService;
    protected $configService;
    protected $sessionService;

    protected $request;
    protected $response;

    public function __construct(
        Service_Layers $layersService,
        Service_Module $moduleService,
        Service_Config $configService,
        Service_Http_Session $sessionService,
        Network_Request $request
    )
    {
        $this->layersService = $layersService;
        $this->moduleService = $moduleService;
        $this->configService = $configService;
        $this->sessionService = $sessionService;
        $this->request = $request;
    }

    public function register()
    {
    }

    public function boot()
    {
        // Cargar las rutas de la aplicación.
        $this->layersService->loadFiles([
            '/App/routes.php',
        ]);
    }

    /**
     * Obtener la instancia de Network_Request.
     *
     * @return \sowerphp\core\Network_Request
     */
    public function getRequest(): Network_Request
    {
        return $this->request;
    }

    /**
     * Obtener la instancia de Network_Response.
     *
     * @return \sowerphp\core\Network_Response
     */
    public function getResponse(): Network_Response
    {
        if (!isset($this->response)) {
            $this->response = new Network_Response();
        }
        return $this->response;
    }

    public function handle(): int
    {
        $request = $this->getRequest();
        $response = $this->handleRequest($request);
        $result = $response->send();
        $this->terminate($request, $response);
        return $result;
    }

    /**
     * Manejar la solicitud HTTP y obtener la respuesta.
     *
     * @param Network_Request $request
     * @return Network_Response
     */
    private function handleRequest(Network_Request $request): Network_Response
    {
        // Revisar si la solicitud es por un archivo estático y procesarla.
        $response = $this->handleStaticFileRequest($request);
        if ($response) {
            return $response;
        }
        // Procesar la solicitud con un controlador.
        return $this->handleControllerRequest($request);
    }

    private function handleStaticFileRequest(Network_Request $request): ?Network_Response
    {
        $filepath = $this->getFilePath($request->getRequestUriDecoded());
        if (!$filepath) {
            return null;
        }
        $response = $this->getResponse();
        return $response->prepareFileResponse($filepath);
    }

    /**
     * Busca si lo solicitado existe físicamente en el servidor y lo entrega.
     * La búsqueda se realiza en todos los posibles directorios webroot de la
     * aplicación, incluyendo las rutas de los modulos si la solicitud es de un
     * módulo.
     *
     * @param string $url Ruta de los que se está solicitando.
     * @return string Ruta del archivo estático o null si no se encontró.
     */
    private function getFilePath(string $filename): ?string
    {
        // Si no hay archivo se retorna inmediatamente.
        if ($filename == '') {
            return null;
        }
        // Si hay más de dos slash en la url podría ser un módulo así que se
        // busca path para el módulo.
        $paths = null;
        $slashPos = strpos($filename, '/', 1);
        if ($slashPos) {
            // Rutas de módulos
            $module = $this->moduleService->findModuleByUrl($filename);
            if (isset($module[0])) {
                $this->moduleService->loadModule($module);
                $paths = $this->moduleService->getModulePaths($module);
            }
            // Si existe el módulo en las rutas entonces si es un módulo lo que
            // se está pidiendo, y es un módulo ya cargado. Si este no fuera el
            // caso podría no ser un módulo o no estar cargado.
            if ($paths) {
                $removeCount = count(explode('.', $module)) + 1;
                $aux = explode('/', $filename);
                while ($removeCount-- > 0) {
                    array_shift($aux);
                }
                $filename = '/' . implode('/', $aux);
            }
        }
        // Si no están definidas las rutas para buscar el archivos, entonces no
        // era de módulo y se deberá buscar en las rutas base de la aplicación.
        if (!$paths) {
            $paths = $this->layersService->getPaths();
        }
        // Buscar el archivo solicitado en las rutas determinadas.
        $filepath = null;
        foreach ($paths as &$path) {
            $file = $path . '/webroot' . $filename;
            if (file_exists($file) && !is_dir($file)) {
                $filepath = $file;
                break;
            }
        }
        return $filepath;
    }

    private function handleControllerRequest(Network_Request $request): Network_Response
    {
        // Obtener instancia de reflexión del controlador.
        $reflection = $this->getControllerReflection(
            $request->getParsedParams()['controller'],
            $request->getParsedParams()['module'] ?? null
        );
        if (!$reflection) {
            throw new Exception_Controller_Missing(array(
                'class' => 'Controller_'.Utility_Inflector::camelize(
                    $request->getParsedParams()['controller']
                )
            ));
        }
        // Instanciar controlador.
        $response = $this->getResponse();
        $controller = $reflection->newInstance($request, $response);
        // Despachar controlador y ejecutar su acción.
        $controller->startupProcess();
        $result = $controller->invokeAction();
        // Asignar respuesta de la acción invocada.
        // TODO: revisar si se debe verificar que body() ya esté asignado y si
        // lo está tener esa respuesta como prioridad (útil en respuesta que
        // terminan antes como archivos y ya dejan algo en el body).
        if ($controller->autoRender) {
            $response = $controller->render();
        } else if ($response->body() === null) {
            $response->body($result);
        }
        // Terminar tareas controlador.
        // TODO: revisar si se debe mover a terminate()
        $controller->shutdownProcess();
        // Retornar respuesta al handler de la solicitud HTTP.
        return $response;
    }

    /**
     * Método que obtiene la instancia del controlador.
     */
    private function getControllerReflection(string $controller, ?string $module): ?\ReflectionClass
    {
        // Si se solicita un módulo tratar de cargar y verificar que quede
        // activo.
        if (!empty($module)) {
            $this->moduleService->loadModule($module);
            if (!$this->moduleService->isModuleLoaded($module)) {
                throw new Exception_Module_Missing(array(
                    'module' => $module
                ));
            }
        }
        // Determinar nombre de la clase que se cargará mágicamente.
        $class = 'Controller_'.Utility_Inflector::camelize(
            $controller
        );
        if ($module && $class != 'Controller_Module') {
            $class = str_replace('.', '\\', $module) . '\\' . $class;
        }
        $class = '\\sowerphp\\magicload\\' . $class;
        // Cargar clase del controlador.
        if (!class_exists($class)) {
            return null;
        }
        // Se verifica que la clase no sea abstracta
        $reflection = new \ReflectionClass($class);
        if ($reflection->isAbstract()) {
            return null;
        }
        // Se retorna la instancia de reflexión del controlador.
        return $reflection;
    }

    /**
     * Terminar la solicitud (realizar cualquier limpieza necesaria).
     *
     * @param Network_Request $request
     * @param Network_Response $response
     * @return void
     */
    private function terminate(Network_Request $request, Network_Response $response): void
    {
        // Realizar cualquier limpieza necesaria después de enviar la respuesta.
        // TODO: buscar forma de ejecutar tareas después de enviar respuesta.
        // Por ejemplo tareas como: borrar un archivo temporal creado en la
        // acción del controlador (ej: zip de PDF).
        // IDEA: Se podría pasar un callback al response con lo que se debe
        // ejecutar acá o ejecutar el shutdownProcess() (revisar bien la idea).
        // Incluir otras limpiezas que sean necesarias.
    }

    public function handleThrowable(\Throwable $throwable): void
    {
        if ($throwable instanceof \Error ) {
            $this->handleError($throwable);
        } else {
            $this->handleException($throwable);
        }
    }

    private function handleError(\Error $error): void
    {
        $this->sessionService->close();
        if ($this->configService->get('error.exception')) {
            $exception = new \ErrorException(
                $error->getMessage(),
                $error->getCode(),
                \E_ERROR, // $severity siempre asignada como E_ERROR
                $error->getFile(),
                $error->getLine(),
                $error
            );
            $this->handleException($exception);
        } else {
            $message = sprintf(
                '[Error] %s en %s:%s.',
                $message = $error->getMessage(),
                $error->getFile(),
                $error->getLine()
            );
            echo $message , PHP_EOL;
        }
    }

    private function handleException(\Exception $exception): void
    {
        ob_clean();
        $request = $this->getRequest();
        $response = $this->getResponse();
        // Generar arreglo con los datos para la vista.
        $data = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'code' => $exception->getCode(),
            'severity' => $exception->severity ?? LOG_ERR,
        ];
        // Renderizar la excepción a través del controlador de errores.
        $controller = new Controller_Error($request, $response);
        // Es una solicitud mediante un servicio web.
        if ($request->isApiRequest()) {
            $controller->Api->sendException($exception);
        }
        // Es una solicitud mediante la interfaz web.
        else {
            $controller->error_reporting = config('debug');
            $controller->display($data);
            $controller->shutdownProcess();
            $response->status($data['code']);
            $response->send();
        }
    }

}
