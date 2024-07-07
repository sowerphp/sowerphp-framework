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

use Illuminate\Support\Str;

/**
 * Servicio para manejar las vistas de las solicitudes HTTP.
 */
class Service_View implements Interface_Service
{

    /**
     * Instancia de la aplicación.
     *
     * @var App
     */
    protected $app;

    /**
     * Servicio de capas de la aplicación.
     *
     * @var Service_Layers
     */
    protected $layersService;

    /**
     * Vistas que ya han sido resueltas por el servicio.
     *
     * @var array
     */
    protected $views = [];

    /**
     * Vistas que son especiales y se resuelven de manera diferente al resto
     * de vistas de la aplicación.
     *
     * @var array
     */
    protected $specialViews = [
        'Maintainer/listar',
        'Maintainer/crear_editar',
        'App/module',
        'App/error',
    ];

    /**
     * Motores de renderizado de vistas.
     *
     * @var array
     */
    protected $engines = [
        //'.blade.php' => View_Engine_Blade::class,
        '.twig' => View_Engine_Twig::class,
        '.php' => View_Engine_Php::class,
        //'.md' => View_Engine_Markdown::class,
    ];

    /**
     * Layout por defecto cuando no se encuentra el layout solicitado.
     *
     * @var string
     */
    protected $defaultLayout = 'bootstrap';

    /**
     * Constructor del servicio con sus dependencias.
     */
    public function __construct(App $app, Service_Layers $layersService)
    {
        $this->app = $app;
        $this->layersService = $layersService;
    }

    /**
     * Registra el servicio de vistas.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio de vistas.
     *
     * @return void
     */
    public function boot(): void
    {
        $engines = config('app.ui.view_engines', $this->engines);
        foreach ($engines as $extension => $class) {
            $aux = explode('_', $class);
            $key = 'view_engine_' . Str::snake(end($aux));
            $this->app->registerService($key, $class);
            $this->engines[$extension] = app($key);
        }
        $this->defaultLayout = config('app.ui.layout', $this->defaultLayout);
    }

    /**
     * Finaliza el servicio de vistas.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Método para renderizar una vista con su contexto (variables).
     *
     * @param string $view Vista que se desea renderizar.
     * @param array $data Variables que se pasarán a la vista al renderizar.
     * @return string String con el contenido (cuerpo) de la vista renderizada.
     */
    public function render(string $view, array $data = []): string
    {
        // Buscar el archivo de la vista que se utilizará para renderizar.
        $filepath = $this->resolveView($view);
        if (!$filepath) {
            throw new \Exception(__(
                'Vista %s no ha sido encontrada.',
                $view
            ));
        }
        // Obtener motor de renderizado para la vista.
        $engine = $this->getEngineByExtension($filepath);
        if (!$engine) {
            throw new \Exception(__(
                'El archivo %s de la vista %s no se pudo renderizar.',
                $filepath,
                $view
            ));
        }
        // Normalizar los datos que se utilizarán para el renderizado de la
        // vista y, eventualmente, del layout.
        $data = $this->normalizeData($data);
        // Renderizar la vista con el motor encontrado.
        return $engine->render($filepath, $data);
    }

    /**
     * Método para renderizar una vista con su contexto (variables).
     *
     * @param string $view Vista que se desea renderizar.
     * @param array $data Variables que se pasarán a la vista al renderizar.
     * @return Network_Response Objeto con la respuesta de la solicitud HTTP
     * que contiene en el cuerpo (body) la vista renderizada.
     */
    public function renderToResponse(string $view, array $data = []): Network_Response
    {
        $response = response();
        $body = $this->render($view, $data);
        $response->body($body);
        return $response;
    }

    /**
     * Resolver una vista a su archivo en el sistema de archivos.
     *
     * @param string $view Vista que se desea resolver su ubicación real.
     * @param string $module Módulo donde se debe buscar la vista.
     * @return string|null Ruta hacia el archivo de la vista solicitado o null.
     */
    protected function resolveView(string $view, string $module = null): ?string
    {
        // Si la vista ya está resuelta se retorna directamente.
        if (array_key_exists($view, $this->views)) {
            return $this->views[$view];
        }
        // Localizar vista absoluta.
        $this->views[$view] = $this->resolveViewAbsolute($view);
        if ($this->views[$view]) {
            return $this->views[$view];
        }
        // Localizar vista relativa.
        $this->views[$view] = $this->resolveViewRelative($view, $module);
        if ($this->views[$view]) {
            return $this->views[$view];
        }
        // Localizar vista especial.
        $this->views[$view] = $this->resolveViewSpecial($view);
        if ($this->views[$view]) {
            return $this->views[$view];
        }
        // No se encontró la vista, se recuerda y retorna null.
        $this->views[$view] = null;
        return $this->views[$view];
    }

    /**
     * Resolver rutas "absolutas". Esto es rutas que parten por "/" pero que
     * les falta que se defina la extensión que se debe usar.
     *
     * @param string $view Ruta "absoluta" (le podría faltar la extensión).
     * @return string|null Ruta hacia el archivo de la vista solicitado o null.
     */
    protected function resolveViewAbsolute(string $view): ?string
    {
        if ($view[0] != '/') {
            return null;
        }
        if (is_readable($view)) {
            return $view;
        }
        foreach($this->engines as $extension => $engine) {
            $filepath = $view . $extension;
            if (is_readable($filepath)) {
                return $filepath;
            }
        }
        return null;
    }

    /**
     * Buscar la vista en las posibles rutas de la aplicación.
     *
     * @param string $view Ruta relativa (a una capa o a un módulo de una capa).
     * @param string $module Módulo donde se debe buscar la vista.
     * @return string|null Ruta hacia el archivo de la vista solicitado o null.
     */
    public function resolveViewRelative(string $view, string $module = null): ?string
    {
        if ($module === null) {
            $routeConfig = request()->getRouteConfig();
            $module = $routeConfig['module'] ?? null;
        }
        $baseFilepath = (
            $module
                ? ('/Module/' . str_replace('.', '/Module/', $module))
                : ''
            )
            . '/View/' . $view
        ;
        foreach($this->engines as $extension => $engine) {
            $filename = $baseFilepath . $extension;
            $filepath = $this->layersService->getFilePath($filename);
            if ($filepath) {
                return $filepath;
            }
        }
        return null;
    }

    /**
     * Resolver vistas que son especiales.
     *
     * Existen algunas vistas que podrían no ser encontradas por no se parte de
     * un módulo específico y ser generales para todos los módulos. Estas
     * vistas si no están definidas y no se encuentran previamente se buscarán
     * acá las por defecto que están escritas con extensión PHP.
     *
     * @param string $view Nombre de la vista que "podría" ser especial.
     * @return string|null Ruta hacia el archivo de la vista solicitado o null.
     */
    protected function resolveViewSpecial(string $view): ?string
    {
        foreach ($this->specialViews as $specialView) {
            if ($view == $specialView) {
                $filename = '/View/' . $specialView . '.php';
                $filepath = $this->layersService->getFilePath($filename);
                if ($filepath) {
                    $this->views[$view] = $filepath;
                    return $this->views[$view];
                }
            }
        }
        return null;
    }

    /**
     * Normalizar los datos que se pasarán al motor de renderizado de la vista.
     *
     * Se preocupa de que existan las siguientes variables para la vista:
     *   - _base: si la aplicación está en un subdirectorio esto lo tendrá.
     *   - _request: solicitud que se hizo a la aplicación.
     *   - _url: dirección web completa de la aplicación.
     *   - _page: página que se está ejecutando.
     *   - _nav_website: menú de la página web (público).
     *   - _nav_app: menú de la aplicación web (privado).
     *   - _nav_module: menú del módulo que se está ejecutando.
     *   - __view_title: título para tag title.
     *   - __view_header: tags para JS y CSS en el header.
     *
     * @param array $data Datos que se pasarán a la vista sin preparar.
     * @return array Datos preparados para pasar a la vista.
     */
    protected function normalizeData(array $data): array
    {
        $request = $this->app->getContainer()->bound('request')
            ? request()
            : null
        ;
        // Diferentes menús que se podrían utilizar.
        $data['_nav_website'] = (array)config('nav.website');
        $data['_nav_app'] = (array)config('nav.app');
        // Agregar variables por defecto que se pasarán a la vista.
        $data['_url'] = url();
        if ($request) {
            $data['_base'] = $request->getBaseUrlWithoutSlash();
            $data['_request'] = $request->getRequestUriDecoded();
        }
        // Página que se está viendo.
        if (!empty($data['_request'])) {
            $slash = strpos($data['_request'], '/', 1);
            $data['_page'] = $slash === false
                ? $data['_request']
                : substr($data['_request'], 0, $slash)
            ;
        } else {
            $data['_page'] = config('app.ui.homepage');
        }
        // Asignar el título de la página si no está asignado.
        if (empty($data['__view_title'])) {
            $data['__view_title'] = config('app.name')
                . (($data['_request']??null) ? (': ' . $data['_request']) : '')
            ;
        }
        // Preparar __view_header pues viene como arreglo y debe ser string.
        $__view_header = '';
        if (isset($data['__view_header'])) {
            if (isset($data['__view_header']['css'])) {
                foreach ($data['__view_header']['css'] as &$css) {
                    $__view_header .= '<link type="text/css" href="'
                        . $request->getBaseUrlWithoutSlash()
                        . $css . '" rel="stylesheet" />' . "\n"
                    ;
                }
            }
            if (isset($data['__view_header']['js'])) {
                foreach ($data['__view_header']['js'] as &$js) {
                    $__view_header .= '<script type="text/javascript" src="'
                        . $request->getBaseUrlWithoutSlash()
                        . $js . '"></script>' . "\n"
                    ;
                }
            }
        }
        $data['__view_header'] = $__view_header;
        // Entregar los datos preparados.
        return $data;
    }

    /**
     * Busca la extensión del motor de renderizado basado en el nombre del
     * archivo.
     *
     * @param string $filename Nombre del archivo de la vista.
     * @return object|null Retorna el motor de renderizado o null si no hay
     * coincidencia.
     */
    protected function getEngineByExtension(string $filename): ?object
    {
        foreach ($this->engines as $extension => $engine) {
            if (substr($filename, -strlen($extension)) === $extension) {
                return $engine;
            }
        }
        return null;
    }

    /**
     * Determinar layout que se debe usar para el contenido renderizado.
     *
     * @param string $layout Nombre del layout que se desea utilizar.
     * @return string Ruta absoluta al layout que se desea utilizar.
     */
    public function resolveLayout(string $layout): string
    {
        if ($layout[0] != '/') {
            $layout = 'Layouts/' . $layout;
        }
        $filepath = $this->resolveView($layout, '');
        if (!$filepath) {
            $layout = 'Layouts/' . $this->defaultLayout;
            $filepath = $this->resolveView($layout, '');
        }
        return $filepath;
    }

}

/**
 * Clase base para los motores de renderizado de plantillas HTML.
 */
abstract class View_Engine
{

    /**
     * Servicio de capas de la aplicación.
     *
     * @var Service_Layers
     */
    protected $layersService;

    /**
     * Servicio de vistas de la aplicación.
     *
     * @var Service_View
     */
    protected $viewService;

    /**
     * Constructor del motor de renderizado de las vistas.
     */
    public function __construct(
        Service_Layers $layersService,
        Service_View $viewService
    )
    {
        $this->layersService = $layersService;
        $this->viewService = $viewService;
        $this->boot();
    }

    protected function boot(): void
    {
    }

}

/**
 * Motor de renderizado de plantilla HTML utilizando Blade de Laravel.
 */
class View_Engine_Blade extends View_Engine
{

    /**
     * Renderizar una plantilla blade y devolver el resultado como una cadena.
     *
     * @param string $filepath Ruta a la plantilla blade que se va a renderizar.
     * @param array $data Datos que se pasarán a la plantilla blade para su uso
     * dentro de la vista.
     * @return string El contenido HTML generado por la plantilla blade.
     */
    public function render(string $filepath, array $data): string
    {
        throw new \Exception('Plantillas blade actualmente no soportadas.');
    }

}

/**
 * Motor de renderizado de plantilla HTML utilizando PHP puro.
 */
class View_Engine_Php extends View_Engine
{

    /**
     * Renderizar un archivo PHP y devolver el resultado como una cadena.
     * Además, si se ha solicitado, se entregará el contenido dentro de un
     * layout que también se renderizará con PHP.
     *
     * @param string $filepath Ruta al archivo PHP que se va a renderizar.
     * @param array $data Datos que se pasarán al archivo PHP para su uso
     * dentro de la vista.
     * @return string El contenido HTML generado por el archivo PHP.
     */
    public function render(string $filepath, array $data): string
    {
        // Renderizar contenido del archivo PHP.
        $content = $this->renderPhp($filepath, $data);
        if (empty($data['__view_layout'])) {
            return $content;
        }
        $data['_content'] = $content;
        // Renderizar el layout solicitado con el contenido previamente
        // determinado ya incluído en los datos del layout.
        $layout = $this->viewService->resolveLayout($data['__view_layout']);
        return $this->renderPhp($layout, $data);
    }

    /**
     * Método que toma un archivo PHP y lo renderiza reemplazando las variables
     * que existan en dicho archivo.
     *
     * @param string $__filepath Ruta absoluta al archivo PHP.
     * @param array $__data Variables que se desean reemplazar.
     * @return string El contenido del archivo ya renderizado.
     */
    public function renderPhp(string $__filepath, array $__data): string
    {
        ob_start(); // NOTE: obligatorio o se incluirá en la salida.
        extract($__data, EXTR_SKIP);
        include $__filepath;
        $vars = get_defined_vars();
        foreach ($vars as $var => $val) {
            if (substr($var, 0, 7) === '__view_') {
                $__data[$var] = $val;
            }
        }
        return ob_get_clean();
    }

}

/**
 * Motor de renderizado de plantilla HTML utilizando Twig.
 */
class View_Engine_Twig extends View_Engine
{

    /**
     * Posibles rutas para la búsqueda de vistas.
     *
     * Se configurará con las rutas de cada capa. Por lo que las vistas se
     * buscarán en cada una de las capas de la aplicación desde su base.
     *
     * @var array
     */
    protected $viewPaths;

    /**
     * Ruta, dentro del directorio de almacenamiento, para el caché de las
     * vistas ya procesadas de Twig.
     *
     * @var string
     */
    protected $cachePath = 'framework/views/twig';

    /**
     * Instancia del objeto de Twig para el renderizado.
     *
     * @var \Twig\Environment
     */
    protected $twig;

    /**
     * Inicialización de Twig.
     *
     * @return void
     */
    protected function boot(): void
    {
        // Crear el FilesystemLoader con los posibles directorios para las
        // vistas.
        $this->viewPaths = array_values($this->layersService->getPaths());
        $loader = new \Twig\Loader\FilesystemLoader($this->viewPaths);
        // Configurar el entorno de Twig:
        //   - Definir el caché en el directorio estándar si es producción.
        //   - Cargar la extensión por defecto de Twig.
        $config = [];
        if (config('app.env') != 'local') {
            $config['cache'] = storage_path($this->cachePath);
        }
        $this->twig = new \Twig\Environment($loader, $config);
        $this->twig->addExtension(new View_Engine_Twig_Extension());
    }

    /**
     * Renderizar una plantilla Twig y devolver el resultado como una cadena.
     *
     * @param string $filepath Ruta a la plantilla Twig que se va a renderizar.
     * @param array $data Datos que se pasarán a la plantilla Twig para su uso
     * dentro de la vista.
     * @return string El contenido HTML generado por la plantilla Twig.
     */
    public function render(string $filepath, array $data): string
    {
        // Convertir la ruta absoluta a relativa.
        $relativePath = substr(str_replace($this->viewPaths, '', $filepath), 1);
        // Renderizar la plantilla.
        return $this->twig->render($relativePath, $data);
    }

    /**
     * Registrar una función en el motor de renderizado Twig.
     *
     * Permite registrar una función mediante:
     *   - Función anónima.
     *   - Función.
     *   - Método de una clase.
     *
     * Ejemplos de uso de una función:
     *   {{ greet() }}
     *   {{ greet('Alice') }}
     *   {{ greet('Bob', 'Hi') }}
     *
     * @param string $name Nombre de la función que estará disponible en Twig.
     * @param Closure|string|array $function Función o llamada a la misma.
     * @return void
     */
    public function addFunction(string $name, $function): void
    {
        $this->twig->addFunction(
            new \Twig\TwigFunction($name, $function)
        );
    }

    /**
     * Registrar un filtro en el motor de renderizado Twig.
     *
     * Permite registrar un filtro mediante:
     *   - Función anónima.
     *   - Función.
     *   - Método de una clase.
     *
     * Ejemplos de uso de un filtro:
     *   {{ 'text' | my_filter('prefix_', '_suffix') }}
     *   {{ 'text' | my_filter('prefix_') }}
     *   {{ 'text' | my_filter }}
     *
     * @param string $name Nombre del filtro que estará disponible en Twig.
     * @param Closure|string|array $filter Función o llamada a la misma.
     * @return void
     */
    public function addFilter($name, $filter): void
    {
        $this->twig->addFilter(
            new \Twig\TwigFilter($name, $filter)
        );
    }

    /**
     * Cualquier método que no esté definido en el motor de renderizado será
     * llamado en la instancia de Twig.
     *
     * Ejemplos de métodos de la instancia de Twig que se usarán:
     *   - addExtension()
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->twig, $method], $parameters);
    }

}

/**
 * Extensión por defecto con funciones y filtros para Twig.
 */
class View_Engine_Twig_Extension extends \Twig\Extension\AbstractExtension
{

    public function getFunctions()
    {
        return [
            new \Twig\TwigFunction('project_path', [$this, 'function_project_path']),
            new \Twig\TwigFunction('session_messages', [$this, 'function_session_messages']),
        ];
    }

    public function function_project_path(?string $path = null): string
    {
        return app('layers')->getProjectPath($path);
    }

    public function function_session_messages()
    {
        return new \Twig\Markup(
            \sowerphp\core\Facade_Session_Message::getMessagesAsString(),
            'UTF-8'
        );
    }

    public function getFilters()
    {
        return [
            new \Twig\TwigFilter('num', 'num'),
        ];
    }

}

/**
 * Motor de renderizado de plantilla HTML utilizando Markdown para el contenido
 * principal de la vista y PHP puro para el Layout de la página con markdown.
 */
class View_Engine_Markdown extends View_Engine
{

    /**
     * Renderizar una plantilla markdown y devolver el resultado como una
     * cadena. Además, si se ha solicitado, se entregará el contenido dentro de
     * un layout que se renderizará con PHP.
     *
     * @param string $filepath Ruta a la plantilla markdown que se va a
     * renderizar.
     * @param array $data Datos que se pasarán a la plantilla markdown para su
     * uso dentro de la vista.
     * @return string El contenido HTML generado por la plantilla markdown.
     */
    public function render(string $filepath, array $data): string
    {
        // Renderizar contenido del archivo Markdown.
        $content = file_get_contents($filepath);
        foreach ($data as $key => $value) {
            if (
                is_scalar($value)
                || (is_object($value) && method_exists($value, '__toString'))
            ) {
                $content = preg_replace(
                    '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/',
                    $value,
                    $content
                );
            }
        }
        $content = str_replace(
            ['<h1>', '</h1>'],
            ['<div class="page-header"><h1>', '</h1></div>'],
            \Michelf\Markdown::defaultTransform($content)
        );
        if (empty($data['__view_layout'])) {
            return $content;
        }
        $data['_content'] = $content;
        // Renderizar el layout solicitado con el contenido previamente
        // determinado ya incluído en los datos del layout.
        $layout = $this->viewService->resolveLayout($data['__view_layout']);
        return app('view_engine_php')->renderPhp($layout, $data);
    }

}
