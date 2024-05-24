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
 * Clase que renderizará las vistas de la aplicación
 */
class View
{

    protected $request; ///< Objeto Request
    protected $response; ///< Objeto Response
    public $viewVars = []; ///< Variables que se pasarán al renderizar la vista
    private $layout; ///< Tema que se debe usar para renderizar la página
    private $defaultLayout = 'bootstrap'; ///< Layout por defecto
    protected static $_viewsLocation; ///< Listado de vistas que se han buscado
    protected static $extensions = null; ///< Listado de extensiones

    /**
     * Constructor de la clase View
     * @param controller Objeto con el controlador que ocupa la vista
     */
    public function __construct(Controller $controller)
    {
        $this->request = $controller->request;
        $this->response = $controller->response;
        $this->viewVars = $controller->viewVars;
        $this->layout = $controller->layout;
        $this->defaultLayout = config('page.layout', $this->defaultLayout);
    }

    /**
     * Método para renderizar una página.
     * El como renderizará dependerá de la extensión de la página encontrada.
     * @param string $page Ubicación relativa de la página.
     * @param string $location Ubicación de la vista.
     * @return string Buffer de la página renderizada.
     */
    public function render(string $page, ?string $location = null): string
    {
        // buscar página
        if ($location) {
            $location = self::location(
                app('layers')->getLayer($location)['path'] . '/View/' . $page
            );
        } else {
            $module = is_array($this->request->params)
                ? $this->request->params['module']
                : null
            ;
            $location = self::location($page, $module);
        }
        // si no se encontró error
        if (!$location) {
            if (!empty($this->request->params)) {
                if ($this->request->params['controller'] == 'pages') {
                    $this->render('/error/404');
                } else {
                    throw new Exception_View_Missing(array(
                        'view' => $page,
                        'controller' => Utility_Inflector::camelize(
                            $this->request->params['controller']
                        ),
                        'action' => $this->request->params['action'],
                    ));
                }
            } else {
                throw new Exception_View_Missing(array(
                    'view' => $page,
                    'controller' => 'Controller',
                    'action' => 'action',
                ));
            }
        }
        // preparar _header_extra (se hace antes de renderizar la página para
        // quitarlo de las variables por si existe
        if (isset($this->viewVars['_header_extra'])) {
            $_header_extra = '';
            if (isset($this->viewVars['_header_extra']['css'])) {
                foreach ($this->viewVars['_header_extra']['css'] as &$css) {
                    $_header_extra .= '<link type="text/css" href="'.$this->request->base.$css.'" rel="stylesheet" />'."\n";
                }
            }
            if (isset($this->viewVars['_header_extra']['js'])) {
                foreach ($this->viewVars['_header_extra']['js'] as &$js) {
                    $_header_extra .= '<script type="text/javascript" src="'.$this->request->base.$js.'"></script>'."\n";
                }
            }
            unset($this->viewVars['_header_extra']);
        } else {
            $_header_extra = '';
        }
        // dependiendo de la extensión de la página se renderiza
        $ext = substr($location, strrpos($location, '.')+1);
        $class = 'View_Helper_Pages_' . ucfirst($ext);
        $page_content = $class::render($location, $this->viewVars);
        // determinar si se usa el layout por defecto de la app
        // o se usa uno personalizado de la vista que se renderiza
        if (!empty($this->viewVars['__block_layout'])) {
            $this->layout = $this->viewVars['__block_layout'];
        }
        // si no hay layout se debe entregar sólo el contenido
        if ($this->layout === null) {
            return $page_content;
        }
        // buscar archivo del tema que está seleccionado, si no existe
        // se utilizará el tema por defecto definido en $this->defaultLayout
        $layout_location = $this->getLayoutLocation($this->layout);
        if (!$layout_location) {
            $this->layout = $this->defaultLayout;
            $layout_location = $this->getLayoutLocation($this->layout);
            if (!$layout_location) {
                die(__('No se encontró layout por defecto %s.', $this->layout));
            }
        }
        // página que se está viendo
        if (!empty($this->request->request)) {
            $slash = strpos($this->request->request, '/', 1);
            $page = $slash === false ? $this->request->request : substr($this->request->request, 0, $slash);
        } else {
            $page = '/'.config('homepage');
        }
        // determinar module breadcrumb
        $module_breadcrumb = [];
        if (is_array($this->request->params) && $this->request->params['module']) {
            $modulos = explode('.', $this->request->params['module']);
            $url = '';
            foreach ($modulos as &$m) {
                $link = Utility_Inflector::underscore($m);
                $module_breadcrumb[$link] = $m;
                $url .= '/' . $link;
            }
            $module_breadcrumb += explode('/', substr(str_replace($url, '', $this->request->request), 1));
        }
        // determinar título
        $titulo_pagina = isset($this->viewVars['header_title']) ? $this->viewVars['header_title'] : $this->request->request;
        $_header_title = isset($this->viewVars['__block_title'])
            ? $this->viewVars['__block_title']
            : config('page.header.title') . ($titulo_pagina ? (': ' . $titulo_pagina) : '');
        // renderizar layout de la página (con su contenido)
        $viewVars = array_merge([
            '_header_title' => $_header_title,
            '_body_title' => config('page.body.title'),
            '_footer' => config('page.footer'),
            '_header_extra' => $_header_extra,
            '_request' => $this->request->request,
            '_page' => $page,
            '_nav_website' => config('nav.website'),
            '_nav_app' => (array)config('nav.app'),
            '_nav_module' => config('nav.module'),
            '_timestamp' => date(config('time.format'), filemtime($location)),
            '_layout' => $this->layout,
            '_content' => $page_content,
            '_module_breadcrumb' => $module_breadcrumb,
        ], $this->viewVars);
        return View_Helper_Pages_Php::render($layout_location, $viewVars);
    }

    /**
     * Método que entrega la ubicación del tema que se está utilizando.
     * @param layout Tema que se quiere buscar su ubicación.
     * @return Ubicación del tema (o falso si no se encontró).
     */
    private function getLayoutLocation($layout)
    {
        if (!is_string($layout) || empty($layout)) {
            return false;
        }
        // si el layout es una ruta absoluta se entrega directamente
        if (isset($layout[0]) && $layout[0] == '/') {
            return $layout;
        }
        // buscar en las rutas de Layouts
        $filename = '/View/Layouts/' . $layout . '.php';
        $location = app('layers')->getFilePath($filename);
        if ($location) {
            return $location;
        }
        return false;
    }

    /**
     * Método que busca la vista en las posibles rutas y para todas las posibles extensiones
     * @param view Nombre de la vista buscada (ejemplo: /inicio)
     * @param module Nombre del módulo en caso de pertenecer a uno
     * @return string Ubicación de la vista que se busca
     */
    public static function location($view, $module = null)
    {
        // si la página está en caché se retorna
        if (isset(self::$_viewsLocation[$view])) {
            return self::$_viewsLocation[$view];
        }
        // extensiones
        if (!self::$extensions) {
            self::$extensions = config('page.extensions');
            if (!in_array('php', self::$extensions)) {
                self::$extensions[] = 'php'; // php siempre debe estar
            }
        }
        // si la vista parte con / entonces se está pasando la ruta y solo falta su extension
        if ($view[0] == '/') {
            foreach(self::$extensions as $extension) {
                if (is_readable($view . '.' . $extension)) {
                    self::$_viewsLocation[$view] = $view . '.' . $extension;
                    return $view . '.' . $extension;
                }
            }
            return null;
        }
        // Buscar la vista en los posibles paths de la aplicación
        $base_location = ($module
            ? ('/Module/' . str_replace('.', '/Module/', $module))
            : ''
        ) . '/View/' . $view . '.';
        foreach(self::$extensions as $extension) {
            $filename = $base_location . $extension;
            $location = app('layers')->getFilePath($filename);
            if ($location) {
                self::$_viewsLocation[$view] = $location;
                return $location;
            }
        }
        // Existen algunas vistas que podrían no ser encontradas por no se parte de un
        // módulo específico y ser generales para todos los módulos. Estas vistas si no
        // están definidas y no se encuentran previamente se buscarán acá las por defecto
        // que están escritas con extensión PHP y serán mostradas
        $special_views = [
            'Module/index',
            'Error/error',
        ];
        foreach ($special_views as $special_view) {
            if ($view == $special_view) {
                // Buscar el archivo de la vista en todas las posibles rutas
                // Esto es casi idéntico a lo de arriba y probablemente se pueda refactorizar
                $filename = '/View/' . $special_view . '.php';
                $location = app('layers')->getFilePath($filename);
                if ($location) {
                    self::$_viewsLocation[$view] = $location;
                    return $location;
                }
            }
        }
        // si no se encontró el archivo de la vista se retorna falso
        return false;
    }

}
