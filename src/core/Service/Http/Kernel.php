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
        Service_Layers $layersService,
        Service_Module $moduleService,
        Service_Config $configService,
        Network_Request $request

    )
    {
        $this->layersService = $layersService;
        $this->moduleService = $moduleService;
        $this->configService = $configService;
        $this->request = $request;
    }

    /**
     * Registra el servicio HTTP kernel.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Inicializa el servicio HTTP kernel.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Obtener la instancia de Network_Request.
     *
     * @return Network_Request
     */
    public function getRequest(): Network_Request
    {
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
        $result = $response->send();
        $this->terminate($request, $response);
        return $result;
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
        $routeConfig = $request->getRouteConfig();
        $response = $this->getResponse();
        // Instanciar el controlador.
        $controller = new $routeConfig['class']($request, $response);
        // Inicializar el controlador.
        $controller->beforeFilter();
        // Llamar al método del controlador con los parámetros.
        $result = call_user_func_array(
            [$controller, $routeConfig['action']],
            $routeConfig['parameters']
        );
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
        $controller->afterFilter();
        // Retornar respuesta al handler de la solicitud HTTP.
        return $response;
    }

    /**
     * Terminar la solicitud (realizar cualquier limpieza necesaria).
     *
     * @param Network_Request $request
     * @param Network_Response $response
     * @return void
     */
    protected function terminate(
        Network_Request $request,
        Network_Response $response
    ): void
    {
        // Realizar cualquier limpieza necesaria después de enviar la respuesta.
        // TODO: buscar forma de ejecutar tareas después de enviar respuesta.
        // Por ejemplo tareas como: borrar un archivo temporal creado en la
        // acción del controlador (ej: zip de PDF).
        // IDEA: Se podría pasar un callback al response con lo que se debe
        // ejecutar acá o ejecutar el afterFilter() (revisar bien la idea).
        // Incluir otras limpiezas que sean necesarias.

        // Guardar y cerrar la sesión.
        $request->session()->save();
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
            $controller->error_reporting = $this->configService->get('debug');
            $controller->display($data);
            $controller->afterFilter();
            $response->status($data['code']);
            $response->send();
        }
    }

}
