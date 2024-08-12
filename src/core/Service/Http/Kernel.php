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
     * @var Service_View
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
        Middleware_Auth::class,
        Middleware_Throttle::class,
        Middleware_Csrf::class,
        Middleware_Log::class,
    ];

    /**
     * Middlewares específicos de rutas.
     *
     * @var array
     */
    protected $routeMiddlewares = [
        // NOTE: sin definir ni usar (ver abajo).
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
        Service_View $viewService
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
        // Registrar request de la solicitud HTTP.
        $this->request = Network_Request::capture();
        $this->app->registerService('request', $this->request);
        // Registrar response de la solicitud HTTP.
        $this->response = new Network_Response();
        $this->app->registerService('response', $this->response);
    }

    /**
     * Inicializa el servicio HTTP kernel.
     *
     * @return void
     */
    public function boot(): void
    {
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
     * Maneja la solicitud HTTP.
     *
     * @return int
     */
    public function handle(): int
    {
        $request = $this->request;
        // Procesar middlewares antes de manejar la solicitud.
        $request = $this->processMiddlewaresBefore(
            $request,
            function ($request) {
                return $request;
            }
        );
        // Manejar la solicitud.
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
        return response()->file($filepath);
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
                $paths = $this->moduleService->getPaths($module);
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
        $routeConfig = $request->getRouteConfig();
        return $this->invokeControllerAction($routeConfig);
    }

    /**
     * Procesa la solicitud con un controlador y obtiene la respuesta.
     *
     * @param Network_Request $request
     * @return Network_Response
     */
    protected function invokeControllerAction(array $config): Network_Response
    {
        // Ejecutar acción del controlador.
        list($controller, $result) = $this->invokerService->invoke(
            $config['class'],
            $config['action'],
            $config['parameters']
        );
        // Recibir resultado de la ejecución de la acción del controlador.
        $response = $this->response;
        // Se retorno algo desde el controlador:
        //   - Objeto Network_Response.
        //   - Datos para asignar al body.
        if ($result) {
            // La respuesta del controlador es un objeto Network_Response, esto
            // significa que la respuesta ya está lista y su vista renderizada.
            if (is_object($result) && $result instanceof Network_Response) {
                $response = $result;
            }
            // La respuesta no es un objeto Network_Response. Se debe asignar
            // al cuerpo de la respuesta. Solo se asignará si no hay datos
            // previamente asignados al body de la respuesta.
            else if (!$response->body()) {
                // Si el resultado es un string se asigna directamente, viene
                // listo para pasar al body.
                if (is_string($result)) {
                    $response->body($result);
                }
                // Si no es string, se asume que se debe entregar como JSON.
                else {
                    $response->json($result);
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
     * Maneja los errores o excepciones capturadas durante la ejecución de la
     * aplicación una vez que el $kernel está cargado y operativo.
     *
     * @param \Throwable $throwable
     * @return int
     */
    public function handleThrowable(\Throwable $throwable): int
    {
        if ($throwable instanceof \Error) {
            $response = $this->handleError($throwable);
        } else {
            $response = $this->handleException($throwable);
        }
        return $response->send();
    }

    /**
     * Maneja los errores de tipo \Error.
     *
     * @param \Error $error
     * @return Network_Response
     */
    protected function handleError(\Error $error): Network_Response
    {
        $message = sprintf(
            '[Error] %s in %s:%s.',
            $error->getMessage(),
            $error->getFile(),
            $error->getLine()
        );
        if ($this->configService->get('app.php.error_as_exception')) {
            $exception = new \ErrorException(
                $message,
                $error->getCode(),
                \E_ERROR, // $severity siempre asignada como E_ERROR
                $error->getFile(),
                $error->getLine(),
                $error
            );
            return $this->handleException($exception);
        } else {
            $this->response->header('Content-Type', 'text/plain; charset=UTF-8');
            $this->response->body($message);
            return $this->response;
        }
    }

    /**
     * Maneja las excepciones de tipo \Exception o las que hereden de esta.
     *
     * @param \Exception $exception
     * @return Network_Response
     */
    protected function handleException(\Exception $exception): Network_Response
    {
        $response = $this->invokeControllerAction([
            'class' => '\sowerphp\autoload\Controller_App',
            'action' => 'error',
            'parameters' => [
                'exception' => $exception,
            ],
        ]);
        return $response;
    }

}
