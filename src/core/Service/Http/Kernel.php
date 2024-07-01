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

class Service_Http_Kernel implements Interface_Service
{

    /**
     * Instancia de la aplicación.
     *
     * @var App
     */
    protected $app;

    /**
     * Servicio de capas.
     *
     * @var Service_Layers
     */
    protected $layersService;

    /**
     * Servicio de módulos.
     *
     * @var Service_Module
     */
    protected $moduleService;

    /**
     * Servicio de configuración.
     *
     * @var Service_Config
     */
    protected $configService;

    /**
     * Servicio de invocación de métodos en clases.
     *
     * @var Service_Invoker
     */
    protected $invokerService;

    /**
     * Servicio de vistas.
     *
     * @var Service_Http_View
     */
    protected $viewService;

    /**
     * Instancia de la solicitud.
     *
     * @var Network_Request
     */
    protected $request;

    /**
     * Instancia de la respuesta.
     *
     * @var Network_Response
     */
    protected $response;

    /**
     * Middlewares globales.
     *
     * @var array
     */
    protected $middlewares = [
        //Middleware_Example::class,
    ];

    /**
     * Middlewares específicos de rutas.
     *
     * @var array
     */
    protected $routeMiddlewares = [
        //'auth' => Middleware_Auth::class,
        //'throttle' => Middleware_Throttle::class,
    ];

    /**
     * Constructor de Service_Http_Kernel.
     *
     * @param Service_Layers $layersService
     * @param Service_Module $moduleService
     * @param Service_Config $configService
     * @param Network_Request $request
     */
    public function __construct(
        App $app,
        Service_Layers $layersService,
        Service_Module $moduleService,
        Service_Config $configService,
        Service_Invoker $invokerService,
        Service_Http_View $viewService
    )
    {
        $this->app = $app;
        $this->layersService = $layersService;
        $this->moduleService = $moduleService;
        $this->configService = $configService;
        $this->invokerService = $invokerService;
        $this->viewService = $viewService;
    }

    /**
     * Registra el servicio HTTP kernel.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio HTTP kernel.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->getRequest();
        $this->getResponse();
    }

    /**
     * Terminar el servicio HTTP kernel.
     */
    public function terminate(): void
    {
        // Realizar cualquier limpieza necesaria después de enviar la respuesta.
        // TODO: buscar forma de ejecutar tareas después de enviar respuesta.
        // Por ejemplo tareas como: borrar un archivo temporal creado en la
        // acción del controlador (ej: zip de PDF).
        // IDEA: Se podría pasar un callback al response con lo que se debe
        // ejecutar acá o ejecutar el terminate() (revisar bien la idea).
        // Incluir otras limpiezas que sean necesarias.
    }

    /**
     * Obtener la instancia de Network_Request.
     *
     * @return Network_Request
     */
    public function getRequest(): Network_Request
    {
        if (!isset($this->request)) {
            $this->request = Network_Request::capture();
            $this->app->registerService('request', $this->request);
        }
        return $this->request;
    }

    /**
     * Obtener la instancia de Network_Response.
     *
     * @return Network_Response
     */
    public function getResponse(): Network_Response
    {
        if (!isset($this->response)) {
            $this->response = new Network_Response();
            $this->app->registerService('response', $this->response);
        }
        return $this->response;
    }

    /**
     * Maneja la solicitud HTTP.
     *
     * @return int
     */
    public function handle(): int
    {
        $request = $this->getRequest();
        // Procesar middlewares antes de manejar la solicitud.
        $request = $this->processMiddlewaresBefore(
            $request,
            function ($request) {
                return $request;
            }
        );
        // Manejar la solicitud
        $response = $this->handleRequest($request);
        // Procesar middlewares después de manejar la solicitud.
        $response = $this->processMiddlewaresAfter(
            $request,
            $response,
            function ($request, $response) {
                return $response;
            }
        );
        // Enviar resultado y terminar la ejecución.
        return $response->send();
    }

    /**
     * Procesa los middlewares antes de manejar la solicitud.
     *
     * @param Network_Request $request
     * @param \Closure $next
     * @return Network_Request
     */
    protected function processMiddlewaresBefore(
        Network_Request $request,
        \Closure $next
    ): Network_Request
    {
        $middlewares = $this->getBeforeMiddlewares();
        $pipeline = array_reduce(
            array_reverse($middlewares),
            function ($next, $middleware) {
                return function ($request) use ($next, $middleware) {
                    return (new $middleware)->handleBefore(
                        $request,
                        $next
                    );
                };
            },
            $next
        );
        return $pipeline($request);
    }

    /**
     * Procesa los middlewares después de manejar la solicitud.
     *
     * @param Network_Request $request
     * @param Network_Response $response
     * @param \Closure $next
     * @return Network_Response
     */
    protected function processMiddlewaresAfter(
        Network_Request $request,
        Network_Response $response,
        \Closure $next
    ): Network_Response
    {
        $middlewares = $this->getAfterMiddlewares();
        $pipeline = array_reduce(
            array_reverse($middlewares),
            function ($next, $middleware) {
                return function ($request, $response) use ($next, $middleware) {
                    return (new $middleware)->handleAfter(
                        $request,
                        $response,
                        $next
                    );
                };
            },
            $next
        );
        return $pipeline($request, $response);
    }

    /**
     * Obtiene los middlewares a ejecutar antes de manejar la solicitud.
     *
     * @return array
     */
    protected function getBeforeMiddlewares(): array
    {
        $middlewares = [];
        foreach ($this->middlewares as $middleware) {
            $middlewares[] = new $middleware;
        }
        // TODO: crear condicionalmente según ruta parseada.
        // Quizás va en otro lado, en el Router y se pasa al kernel luego.
        /*foreach ($this->routeMiddlewares as $name => $middleware) {
            $middlewares[] = new $middleware;
        }*/
        return $middlewares;
    }

    /**
     * Obtiene los middlewares a ejecutar después de manejar la solicitud.
     *
     * @return array
     */
    protected function getAfterMiddlewares(): array
    {
        return array_reverse($this->getBeforeMiddlewares());
    }

    /**
     * Manejar la solicitud HTTP y obtener la respuesta.
     *
     * @param Network_Request $request
     * @return Network_Response
     */
    protected function handleRequest(Network_Request $request): Network_Response
    {
        // Revisar si la solicitud es por un archivo estático y procesarla.
        $response = $this->handleStaticFileRequest($request);
        if ($response) {
            return $response;
        }
        // Procesar la solicitud a través del servicio de rutas.
        return $this->handleRouterRequest($request);
    }

    /**
     * Maneja la solicitud de archivos estáticos.
     *
     * @param Network_Request $request
     * @return Network_Response|null
     */
    protected function handleStaticFileRequest(
        Network_Request $request
    ): ?Network_Response
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
    protected function getFilePath(string $filename): ?string
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
            // Rutas de módulos.
            $module = $this->moduleService->findModuleByResource($filename);
            if ($module) {
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

    /**
     * Procesa la solicitud usando el router y obtiene la respuesta.
     *
     * @param Network_Request $request
     * @return Network_Response
     */
    protected function handleRouterRequest(
        Network_Request $request
    ): Network_Response
    {
        // Obtener configuración de la ruta de la solicitud.
        $routeConfig = $request->getRouteConfig();
        //debug(app('config')->get('modules')); exit;
        // Procesar la solicitud redirigiendo.
        if (!empty($routeConfig['redirect'])) {
            $response = $this->getResponse();
            $response->header('Location', $routeConfig['redirect']);
            return $response;
        }
        // Procesar la solicitud con un controlador.
        return $this->handleControllerRequest($request);
    }

    /**
     * Procesa la solicitud con un controlador y obtiene la respuesta.
     *
     * @param Network_Request $request
     * @return Network_Response
     */
    protected function handleControllerRequest(
        Network_Request $request
    ): Network_Response
    {
        // Ejecutar acción del controlador.
        $routeConfig = $request->getRouteConfig();
        list($controller, $result) = $this->invokerService->invoke(
            $routeConfig['class'],
            $routeConfig['action'],
            $routeConfig['parameters']
        );
        // Recibir resultado de la ejecución de la acción del controlador.
        $response = $this->getResponse();
        // Se retorno algo desde el controlador:
        //   - Objeto Network_Response.
        //   - Datos para asignar al body.
        if ($result) {
            // La respuesta del controlador es un objeto Network_Response, esto
            // significa que la respuesta ya está lista y su vista renderizada.
            if (
                is_object($result)
                && get_class($result) == 'sowerphp\core\Network_Response'
            ) {
                $response = $result;
            }
            // La respuesta no es un objeto Network_Response. Se debe asignar
            // al cuerpo de la respuesta. Solo se asignará si no hay datos
            // previamente asignados al body de la respuesta.
            else if ($response->body() === null) {
                // Si el resultado es un string se asigna directamente, viene
                // listo para pasar al body.
                if (is_string($result)) {
                    $response->body($result);
                }
                // Si no es string, se asume que se debe entregar como JSON.
                else {
                    $response->header('Content-Type', 'application/json');
                    $response->body(json_encode($result));
                }
            }
        }
        // No se retornó algo desde el controlador, entonces se deberá ejecutar
        // el renderizado de la vista del controlador.
        else {
            $response = $controller->render();
        }
        // Retornar respuesta al handler de la solicitud HTTP.
        return $response;
    }

    /**
     * Maneja las excepciones o errores capturados durante la ejecución.
     *
     * @param \Throwable $throwable
     * @return void
     */
    public function handleThrowable(\Throwable $throwable): void
    {
        if ($throwable instanceof \Error ) {
            $this->handleError($throwable);
        } else {
            $this->handleException($throwable);
        }
    }

    /**
     * Maneja los errores de tipo \Error.
     *
     * @param \Error $error
     * @return void
     */
    protected function handleError(\Error $error): void
    {
        if ($this->configService->get('app.php.error_as_exception')) {
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
            $request = $this->getRequest();
            $request->session()->save();
            $response = $this->getResponse();
            $response->header('Content-Type', 'text/plain; charset=UTF-8');
            $response->body(sprintf(
                '[Error] %s en %s:%s.',
                $error->getMessage(),
                $error->getFile(),
                $error->getLine()
            ));
            $response->send();
        }
    }

    /**
     * Maneja las excepciones de tipo \Exception.
     *
     * @param \Exception $exception
     * @return void
     */
    protected function handleException(\Exception $exception): void
    {
        ob_clean();
        $request = $this->getRequest();
        $response = $this->getResponse();
        $request->session()->save();
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
            $controller->boot();
            $response = $controller->display($data);
            $controller->terminate();
            $response->status($data['code']);
            $response->send();
        }
    }

}
