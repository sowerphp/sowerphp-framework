<?php

/**
 * SowerPHP: Simple and Open Web Ecosystem Reimagined for PHP.
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

use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class Service_Http_Router implements Interface_Service
{

    /**
     * Instancia de la aplicación.
     *
     * @var App
     */
    protected $app;

    /**
     * Instancia del servicio de capas de la aplicación.
     *
     * @var Service_Layers
     */
    protected $layersService;

    /**
     * Instancia del servicio de módulos de la aplicación.
     *
     * @var Service_Module
     */
    protected $moduleService;

    /**
     * Instancia del servicio de vistas de la aplicación.
     *
     * @var Service_View
     */
    protected $viewService;

    /**
     * Instancia del router.
     *
     * @var Router
     */
    protected $router;

    /**
     * Generador de URL a partir de una ruta.
     *
     * @var UrlGenerator
     */
    protected $urlGenerator;

    /**
     * Prefijo por defecto de las rutas.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Rutas conectadas con el método Service_Http_Router::connect().
     *
     * Mantiene la funcionalidad antigua del framework para resolver rutas,
     * previa al uso de Illuminate\Routing\Router.
     *
     * @var array
     */
    protected $routes = [
        'static' => [],
        'dynamic' => [
            'regexp' => [],
            'parameters' => [],
        ],
    ];

    /**
     * Constructor del servicio.
     */
    public function __construct(
        App $app,
        Service_Layers $layersService,
        Service_Module $moduleService,
        Service_View $viewService
    )
    {
        $this->app = $app;
        $this->layersService = $layersService;
        $this->moduleService = $moduleService;
        $this->viewService = $viewService;
    }

    /**
     * Registra el servicio de enrutamiento HTTP.
     *
     * @return void
     */
    public function register(): void
    {
        $container = $this->app->getContainer();
        $events = $container->make('events');
        $this->router = new Router($events, $container);
    }

    /**
     * Inicializa el servicio de enrutamiento HTTP.
     *
     * @return void
     */
    public function boot(): void
    {
        $request = request();
        $this->prefix = env('ROUTE_PREFIX', $request->getBaseUrlWithoutSlash());
        $this->loadRoutes();
    }

    /**
     * Definir las rutas de la aplicación.
     *
     * Este método define 3 grupos de rutas:
     *   - Rutas del proyecto.
     *   - Rutas de cada capa.
     *   - Rutas de módulos.
     *
     * @return void
     */
    protected function loadRoutes(): void
    {
        // Cargar las rutas del directorio project:/routes/
        $routesDir = $this->layersService->getProjectPath('/routes');
        foreach (glob($routesDir . '/*.php') as $filepath) {
            require_once $filepath;
        }
        // Cargar las rutas de cada capa de la aplicación.
        $this->layersService->loadFiles([
            '/App/routes.php',
        ]);
        // Cargar las rutas de cada módulo (en todos sus directorios).
        $modules = $this->moduleService->getLoadedModules();
        foreach ($modules as $module) {
            $this->moduleService->loadFiles($module, [
                '/App/routes.php',
            ]);
        }
    }

    /**
     * Finaliza el servicio de enrutamiento HTTP.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Obtener la instancia del generador de URL a partir de rutas.
     *
     * @return UrlGenerator
     */
    protected function getUrlGenerator(): UrlGenerator
    {
        if (!isset($this->urlGenerator)) {
            $this->urlGenerator = new UrlGenerator(
                $this->router->getRoutes(),
                request()
            );
        }
        return $this->urlGenerator;
    }

    /**
     * Obtener la URL de una ruta definida.
     *
     * @param string $name El nombre de la ruta.
     * @param array $parameters Los parámetros de la ruta.
     * @return string|null
     */
    public function resolveRouteToUrl(string $name, array $parameters = []): ?string
    {
        try {
            return $this->getUrlGenerator()->route($name, $parameters);
        } catch (RouteNotFoundException $e) {
            return null;
        }
    }

    /**
     * Procesar una solicitud HTTP y entregar la configuración de la ruta
     * a partir de dicha solicitud HTTP.
     *
     * La configuración se entrega normalizada, pero no verificada que se pueda
     * utilizar. Esto último se debe hacer con router()->checkRouteConfig().
     *
     * @param Network_Request $request
     * @return array Configuración de la ruta.
     */
    public function parse(Network_Request $request): array
    {
        $config = $this->resolveRoute($request);
        if (!isset($config['controller'])) {
            return $config;
        }
        return $this->normalizeRouteConfig($config);
    }

    /**
     * Resolución de una ruta de una solicitud HTTP.
     *
     * Entrega la configuración de la ruta en bruto, sin ser post procesada
     * ni validada.
     *
     * @param Network_Request $request
     * @return array Configuración no normalizada de la ruta de la solicitud.
     */
    protected function resolveRoute(Network_Request $request): array
    {
        // Resolver ruta usando el servicio de enrutamiento con
        // Illuminate\Routing\Router.
        try {
            $route = $this->router->getRoutes()->match($request);
        }
        // Resolver ruta usando el sistema de rutas antiguo de SowerPHP que
        // se configuraron con Service_Http_Router::connect().
        catch (NotFoundHttpException $e) {
            return $this->getRouteConfig($request->getRequestUriDecoded());
        }
        // Si la ruta es de Illuminate\Routing\Router se procesa.
        $actionName = explode('@', $route->getActionName());
        $actionClass = $actionName[0];
        $actionMethod = $actionName[1] ?? null;
        $parameters = $route->parameters();
        // La ruta es una redirección de Illuminate.
        if ($actionClass == '\Illuminate\Routing\RedirectController') {
            return [
                'controller' => 'app',
                'action' => 'redirect',
                'parameters' => $parameters,
            ];
        }
        // La ruta es una función anónima que entrega la configuración.
        else if ($actionClass == 'Closure') {
            $action = $route->getAction('uses');
            return call_user_func_array($action, $parameters);
        }
        // Se obtuvo una clase específica que se debe ejecutar, ya resuelta.
        // Esto ocurrirá cuando no se procesa con la ruta por defecto sino por
        // una que haya sido asignada por los métodos de $this->router.
        else {
            return [
                'controller' => $actionClass,
                'action' => $actionMethod,
                'parameters' => $parameters,
            ];
        }
    }

    /**
     * Normalización de la configuración de una ruta de una solicitud HTTP.
     *
     * @param array $config Configuración no normalizada de la ruta de la
     * solicitud.
     * @return array Configuración normalizada de la ruta de la solicitud.
     */
    protected function normalizeRouteConfig(array $config): array
    {
        $prefixes = ['Controller_', 'Controller'];
        // Configuración base normalizada.
        $routeConfig = [
            'module' => $config['module'] ?? null,
            'controller' => $config['controller'],
            'class' => null,
            'action' => $config['action'] ?? 'index',
            'parameters' => $config['parameters'] ?? [],
        ];
        // Si venía una FQCN se normaliza el controlador para dejarlo solo.
        if (strpos($routeConfig['controller'], '\\') !== false) {
            $routeConfig['class'] = $routeConfig['controller'];
            $aux = explode('\\', $routeConfig['controller']);
            $routeConfig['controller'] = str_replace(
                $prefixes,
                '',
                $aux[count($aux) - 1]
            );
        }
        // Se aplana el controlador.
        $routeConfig['controller'] = Str::snake($routeConfig['controller']);
        // Se determina la base de la clase asociada al controlador.
        if (!isset($routeConfig['class'])) {
            $routeConfig['class'] = ucfirst(Str::camel(
                $routeConfig['controller']
            ));
        }
        // Armar el controlador correcto si no fue especificado como FQCN
        // (Fully Qualified Class Name).
        if ($routeConfig['class'][0] != '\\') {
            // Agregar prefijo "Controller_" a la clase.
            $routeConfig['class'] = 'Controller_' . $routeConfig['class'];
            // Agregar módulo a la clase si existe.
            if (!empty($routeConfig['module'])) {
                if (
                    $routeConfig['class'] != 'Controller_App'
                    && $routeConfig['action'] != 'module'
                ) {
                    $routeConfig['class'] = str_replace(
                        '.',
                        '\\',
                        $routeConfig['module']
                    ) . '\\' . $routeConfig['class'];
                }
            }
        }
        // Si la clase no es un FQCN se agrega el namespace de autocarga.
        if ($routeConfig['class'][0] != '\\') {
            $routeConfig['class'] =
                '\\sowerphp\\autoload\\' . $routeConfig['class']
            ;
        }
        // Se decodifican los parámetros de la URL que son parte del PATH.
        foreach ($routeConfig['parameters'] as &$parameter) {
            $parameter = rawurldecode($parameter);
        }
        // Entregar configuración normalizada.
        return $routeConfig;
    }

    /**
     * Validar la configuración normalizada de una ruta de una solicitud HTTP.
     *
     * @param array $config Configuración normalizada de la ruta de la
     * solicitud.
     */
    public function checkRouteConfig(array $config): void
    {
        // Cargar clase del controlador.
        if (!class_exists($config['class'])) {
            throw new \Exception(__(
                'Controlador %s no fue encontrado.',
                ucfirst(Str::camel($config['controller']))
            ), 404);
        }
        // Verificar que la acción (método del controlador) no sea privado.
        try {
            $method = new \ReflectionMethod($config['class'], $config['action']);
        }
        // Si la acción (método del controlador) no existe se genera un error.
        catch (\ReflectionException $e) {
            throw new \Exception(__(
                'Acción %s::%s() no fue encontrada.',
                ucfirst(Str::camel($config['controller'])),
                $config['action']
            ), 404);
        }
        if (!$method->isPublic() || $method->name[0] == '_') {
            throw new \Exception(__(
                'Acción %s::%s() es privada y no puede ser accedida mediante la URL.',
                ucfirst(Str::camel($config['controller'])),
                $config['action']
            ), 405);
        }
        // Verificar la cantidad de parámetros que se deben pasar a la acción.
        $n_requestArgs = count($config['parameters']);
        $n_requiredArgs = 0;
        $args = [];
        foreach($method->getParameters() as $param) {
            $type = $param->getType() ? $param->getType()->getName() : null;
            if ($type && class_exists($type)) {
                // El parámetro es una dependencia que se inyectará de manera
                // automática. Por lo que se omite de la lista de argumentos
                // requeridos de la acción del controlador.
                continue;
            }
            $args[] = $param->isOptional()
                ? '[' . $param->name .']'
                : $param->name
            ;
            if (!$param->isOptional()) {
                $n_requiredArgs++;
            }
        }
        if ($n_requestArgs < $n_requiredArgs) {
            throw new \Exception(__(
                'Argumentos insuficientes para la acción %s::%s(%s).',
                ucfirst(Str::camel($config['controller'])),
                $config['action'],
                implode(', ', $args)
            ), 400);
        }
    }

    /**
     * Método para conectar nuevas rutas.
     *
     * Se definen 2 tipos de rutas que se pueden conectar:
     *
     *   - static: son rutas donde $route coincide de manera exacta.
     *   - dynamic: son rutas donde $route tiene parámetros.
     *     - regexp: parámetros en la URL con expresiones regulares.
     *     - parameters: parámetros que se pasarán a la acción (rutas con *).
     *
     * Para las rutas estáticas, su configuración se normalizará asegurando que
     * tenga los índices:
     *
     *   - module: Módulo donde se debe buscar el controlador.
     *   - controller: Controlador que contiene la acción de la ruta.
     *   - action: Acción que se ejecutará para procesar la ruta.
     *   - pass: Argumentos que se deben pasar a la acción de la ruta.
     *
     * El índice controlador es obligatorio que venga en la configuración de
     * la ruta estática. Otros índices tomarán valores por defecto para rutas
     * estáticas.
     *
     * Las rutas conectadas se ordenan por nombre de ruta de manera
     * descendente. Esto busca dejar las rutas más específicas primero y las
     * menos específicas después para asignar prioridad al resolverlas.
     *
     * @param string $route Ruta que se desea conectar (recurso de la URL).
     * @param array $config Configuración de conexión de la ruta.
     * @param array $regexp Expresiones regulares para hacer match con los
     * parámetros que se pasan con nombre en rutas dinámicas (defecto: .*).
     */
    public function connect(string $route, array $config, array $regexp = []): void
    {
        // Determinar el tipo de ruta que se está conectando.
        $withRegexp = strpos($route, ':') !== false;
        $withParameters = strpos($route, '*') !== false;
        $type = $withRegexp || $withParameters ? 'dynamic' : 'static';
        // Si la ruta es dinámica, se determinan procesa según si es con
        // expresión regular o solo parámetros.
        if ($type == 'dynamic') {
            if ($withRegexp) {
                $config['regexp'] = [];
                $parts = explode('/', $route);
                foreach($parts as $part) {
                    if (isset($part[0]) && $part[0] == ':') {
                        $config['regexp'][$part] = isset($regexp[$part])
                            ? $regexp[$part]
                            : '.*'
                        ;
                    }
                }
                $this->routes['dynamic']['regexp'][$route] = $config;
                krsort($this->routes['dynamic']['regexp']);
            } else {
                $this->routes['dynamic']['parameters'][$route] = $config;
                krsort($this->routes['dynamic']['regexp']);
            }
        }
        // Si la ruta es estática se asigna la configuración normalizada.
        else {
            $normalizedConfig = [
                'module' => $config['module'] ?? null,
                'controller' => $config['controller'],
                'action' => $config['action'] ?? 'index',
            ];
            unset($config['module'], $config['controller'], $config['action']);
            $normalizedConfig['parameters'] = $config;
            $this->routes['static'][$route] = $normalizedConfig;
            krsort($this->routes['static']);
        }
    }

    /**
     * Buscar la configuración para un recurso configurado con el sistema
     * antiguo de rutas del framework.
     *
     * Las rutas se pueden determinar de la siguiente forma:
     *   - Es una ruta conectada estática, esta puede ser:
     *     - Ruta de controlador.
     *     - Ruta de redirección.
     *   - Es una ruta de una página estática.
     *   - Es una ruta conectada dinámica que resuelve a un controlador.
     *   - Es una ruta no conectada que se resuelve automágicamente.
     *
     * @param string $resource Recurso de la URL que se desea buscar su config.
     * @return array Arreglo con índices: module, controller, action y pass.
     */
    protected function getRouteConfig(string $resource): array
    {
        // Estandarizar recurso solicitado iniciando con "/".
        // Este es el formato esperado por SowerPHP para las rutas.
        $resource = $resource ? $resource : '/';
        if ($resource[0] != '/') {
            $resource = '/' . $resource;
        }
        // Buscar configuración de ruta conectada estática.
        $config = $this->getRouteConfigConnectedStatic($resource);
        if ($config) {
            return $config;
        }
        // Buscar configuración de ruta conectada dinámica.
        $config = $this->getRouteConfigConnectedDynamic($resource);
        if ($config) {
            return $config;
        }
        // Buscar configuración de página estática.
        $config = $this->getRouteConfigStaticPage($resource);
        if ($config) {
            return $config;
        }
        // Buscar configuración de ruta automáfica (no conectada).
        return $this->getRouteConfigAutomagically($resource);
    }

    /**
     * Buscar configuración de ruta conectada estática.
     *
     * Si existe una ruta conectada que coincida de manera exacta para el
     * recurso solicitado se entrega su configuración.
     *
     * @param string $resource Recurso que podría ser una ruta conectada
     * estática.
     * @return array|null Configuración de la ruta conectada estática.
     */
    protected function getRouteConfigConnectedStatic(string $resource): ?array
    {
        return $this->routes['static'][$resource] ?? null;
    }

    /**
     * Buscar si existe una página estática para el recurso solicitado.
     *
     * Si existe una página estática para el recurso se entregará la
     * configuración correspondiente para procesar la página estática.
     *
     * @param string $resource Recurso que podría ser una página estática.
     * @param string $module Nombre del módulo.
     * @return array|null Configuración de la página estática o null si no se
     * encontró una asociada al recurso.
     */
    protected function getRouteConfigStaticPage(string $resource): ?array
    {
        $module = $this->moduleService->findModuleByResource($resource);
        $page = $this->removeModuleFromResource($resource, $module);
        $filepath = $this->viewService->resolveViewRelative(
            'Pages' . $page,
            (string)$module
        );
        if (!$filepath) {
            return null;
        }
        return [
            'module' => $module,
            'controller' => 'app',
            'action' => 'page',
            'parameters' => [$page],
            'view' => $filepath,
        ];
    }

    /**
     * Limpiar el recurso de una URL quitando el módulo de la ruta.
     *
     * @param string $resource Recurso de la URL completo (incluye el módulo).
     * @param string $module Nombre del módulo.
     * @return string Recurso sin el módulo de la ruta.
     */
    protected function removeModuleFromResource(string $resource, ?string $module): string
    {
        if (!$module) {
            return $resource;
        }
        return substr(
            Utility_String::replaceFirst(
                str_replace('.', '/', Utility_Inflector::underscore($module)),
                '',
                $resource
            ),
            1
        );
    }

    /**
     * Buscar configuración de ruta conectada dinámica.
     *
     * Si existe una ruta conectada que coincida de manera variable para el
     * recurso solicitado se entrega su configuración.
     *
     * @param string $resource Recurso que podría ser una ruta conectada
     * dinámica.
     * @return array|null Configuración de la ruta conectada dinámica.
     */
    protected function getRouteConfigConnectedDynamic(string $resource): ?array
    {
        // Buscar rutas dinámicas que tienen parámetros en la URL.
        // Son del estilo :controller o :action y usan expresiones regulares.
        $config = $this->getRouteConfigConnectedDynamicWithRegexp($resource);
        if ($config) {
            return $config;
        }
        // Buscar rutas dinámicas que tienen parámetros que se deben pasar a
        // la acción del controlador.
        $config = $this->getRouteConfigConnectedDynamicWithParameters($resource);
        if ($config) {
            return $config;
        }
        // No se encontró una configuración válida de ruta conectada dinámica.
        return null;
    }

    /**
     * Obtener la configuración de una ruta conectada dinámica que tiene
     * parámetros que usan expresiones regulares.
     *
     * @param string $resource Recurso que podría ser una ruta conectada
     * dinámica con parámetros que usan expresiones regulares.
     * @return array|null Configuración de la ruta conectada dinámica.
     */
    protected function getRouteConfigConnectedDynamicWithRegexp(string $resource): ?array
    {
        // Configuración por defecto de la ruta.
        $defaultConfig = [
            'module' => null,
            'controller' => null,
            'action' => null,
            'parameters' => [],
        ];
        // Iterar cada ruta dinámica para buscar coincidencia con el recurso.
        $resource_parts = explode('/', $resource);
        $n_resource_parts = count($resource_parts);
        foreach ($this->routes['dynamic']['regexp'] as $route => $routeConfig) {
            // Armar configuración base de la ruta y corroborar que se deba
            // procesar buscando coincidencias por cada una de las partes.
            $config = array_merge($defaultConfig, $routeConfig);
            $route_parts = explode('/', $route);
            $n_route_parts = count($route_parts);
            if ($n_resource_parts < $n_route_parts) {
                continue;
            }
            // Revisar cada una de las partes del recurso y ver si coinciden
            // con cada una de las partes de la ruta.
            $match = true;
            for ($i = 0; $i < $n_route_parts; $i++) {
                // Si la parte $i del recurso es igual a la de la ruta
                // se pasa a la siguiente, porque no es parte variable.
                if ($resource_parts[$i] == $route_parts[$i]) {
                    continue;
                }
                // Si la parte es un parámetro variable del recurso (URL), o
                // sea parte con ":", se asigna la parte donde corresponda
                // (controlador, acción o variable acción).
                // Se asignará solo si se logra validar con su expresión
                // regular, si no pasa la validación se indica que no hubo
                // coincidencia.
                else if ($route_parts[$i][0] == ':') {
                    // Verificar formato de la parte contra su expresión
                    // regular. Si no hace match, se rompe este ciclo de partes
                    // para pasar a revisar la siguiente ruta.
                    $regexp = '/' . $config['regexp'][$route_parts[$i]] . '/';
                    if (!preg_match($regexp, $resource_parts[$i])) {
                        $match = false;
                        break;
                    }
                    // Asignar la parte donde corresponda.
                    if ($route_parts[$i] == ':controller') {
                        $config['controller'] = $resource_parts[$i];
                    }
                    else if ($route_parts[$i] == ':action') {
                        $config['action'] = $resource_parts[$i];
                    }
                    else {
                        $config['parameters'][] = $resource_parts[$i];
                    }
                    // Como se logró usar la parte, se pasa a la siguiente
                    // parte de la ruta.
                    continue;
                }
                // Si la parte es "*" significa que todo desde $i en el recurso
                // son parámetros de la acción. Por lo que se asignan los
                // parámetros y se dejan de procesar las partes.
                else if ($route_parts[$i] == '*') {
                    if (isset($resource_parts[$i])) {
                        $config['parameters'] = array_merge(
                            (array)$config['parameters'],
                            array_slice($resource_parts, $i)
                        );
                    }
                    break;
                }
                // Si no se logró hacer match, se rompe la ejecución, pues no
                // se podrá determinar la configuración analizando las partes
                // del recurso y comparando con las partes de la ruta.
                $match = false;
                break;
            }
            // Si todas las partes de la ruta hicieron match con las partes del
            // recurso entonces se encontró (y armó) la configuración de la
            // ruta. Por lo que se entrega retornándola.
            if ($match) {
                return $config;
            }
        }
        // Ninguna ruta hizo match, por lo que se retorna null.
        return null;
    }

    /**
     * Obtener la configuración de una ruta que es dinámica con parámetros que
     * se pasan a la acción del controlador.
     *
     * No es una ruta con partes variables en la URL (sin regexp), por lo que
     * solo se busca si la ruta tiene al final un "*" y si hace match con el
     * recurso, todo lo que no haga match (final del recurso) serán los
     * parámetros que se pasarán a la acción del controlador.
     *
     * @param string $resource Recurso que podría ser una ruta conectada
     * dinámica con parámetros que se deben pasar a la acción del controlador.
     * @return array|null Configuración de la ruta conectada dinámica.
     */
    protected function getRouteConfigConnectedDynamicWithParameters(string $resource): ?array
    {
        // Configuración por defecto de la ruta.
        $defaultConfig = [
            'module' => null,
            'controller' => null,
            'action' => null,
            'parameters' => [],
        ];
        // Iterar cada ruta dinámica para buscar coincidencia con el recurso.
        foreach ($this->routes['dynamic']['parameters'] as $route => $routeConfig) {
            $route = substr($route, 0, -1);
            if (strpos($resource, $route) === 0) {
                $config = array_merge($defaultConfig, $routeConfig);
                $config['parameters'] = explode(
                    '/',
                    str_replace($route, '', $resource)
                );
                return $config;
            }
        }
        // Ninguna ruta hizo match, por lo que se retorna null.
        return null;
    }

    /**
     * Obtener la configuración de una ruta que no está conectada de manera
     * automágica.
     *
     * Esta es la funcionalidad del router original de SowerPHP que es la que
     * tiene la ventaja. Ya que no se requiere especificar las rutas
     * manualmente (conectarlas), pues todas son detectadas automágicamente.
     * O sea, de manera automática, según la configuración de módulos y partes
     * del recurso en la ruta.
     *
     * El recurso de la URL se procesa considerando el siguiente formato:
     *
     *   /módulo(s)/controlador/accion/parámetro(s)
     *   /api/módulo(s)/controlador/accion/parámetro(s)
     *
     * Donde:
     *
     *   - módulo(s): puede ser 0, 1 o más módulos separados por "/".
     *   - parámetro(s): puede ser 0, 1 o más parámetros separados por "/".
     *
     * @param string $resource Recurso que podría ser una ruta automágica.
     * @return array Configuración de la ruta automágica.
     */
    protected function getRouteConfigAutomagically(string $resource): array
    {
        // Si es una llamada de API, se quita el prefijo y se busca como ruta
        // normal web.
        if (strpos($resource, '/api/') === 0) {
            $resource = substr($resource, 4);
            $isApiRoute = true;
        } else {
            $isApiRoute = false;
        }
        // Buscar si existe un módulo en el recurso de la URL y asignarlo a la
        // configuración base de la ruta.
        $config = [
            'module' => $this->moduleService->findModuleByResource($resource),
        ];
        // Obtener la página de la ruta (sin módulo si existiese), luego
        // obtener las partes de la página de la ruta y con eso su configuración.
        $page = $this->removeModuleFromResource($resource, $config['module']);
        $parts = array_slice(explode('/', $page), 1);
        $config['controller'] = isset($parts[0]) ? array_shift($parts) : null;
        $config['action'] = isset($parts[0]) ? array_shift($parts) : 'index';
        $config['parameters'] = $parts;
        // Si no hay controlador y es un módulo se asigna un controlador
        // estándar para cargar la página con el menú del módulo.
        if (empty($config['controller']) && !empty($config['module'])) {
            $config['controller'] = 'app';
            $config['action'] = 'module';
        }
        // Se ajusta configuración encontrada si es API.
        if ($isApiRoute) {
            array_unshift($config['parameters'], $config['action']);
            $config['action'] = 'api';
        }
        // Retornar configuración de la ruta determinada.
        return $config;
    }

    /**
     * Cualquier método que no esté definido en el servicio será llamado en el
     * administrador de la sesión.
     *
     * Ejemplos de métodos del administrador de la sesión que se usarán:
     *   - post()       C
     *   - get()        R
     *   - put()        U
     *   - delete()     D
     */
    public function __call($method, $parameters)
    {
        $routeMethods = ['get', 'post', 'put', 'delete', 'patch', 'options', 'any', 'redirect'];
        if (in_array($method, $routeMethods)) {
            if (!empty($this->prefix)) {
                $parameters[0] = $this->prefix . $parameters[0];
            }
        }
        return call_user_func_array([$this->router, $method], $parameters);
    }

}
