<?php

/**
 * SowerPHP: Minimalist Framework for PHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

namespace sowerphp\core;

/**
 * Clase que renderizará las vistas de la aplicación
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-12-10
 */
class View
{

    protected $request; ///< Objeto Request
    protected $response; ///< Objeto Response
    public $viewVars = array(); ///< Variables que se pasarán al renderizar la vista
    private $layout; ///< Tema que se debe usar para renderizar la página
    private $defaultLayout = 'Bootstrap'; ///< Layout por defecto
    protected static $_viewsLocation; ///< Listado de vistas que se han buscado
    protected static $extensions = null; ///< Listado de extensiones

    /**
     * Constructor de la clase View
     * @param controller Objeto con el controlador que ocupa la vista
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-11-09
     */
    public function __construct(Controller $controller)
    {
        $this->request = $controller->request;
        $this->response = $controller->response;
        $this->viewVars = $controller->viewVars;
        $this->layout = $controller->layout;
    }

    /**
     * Método para renderizar una página
     * El como renderizará dependerá de la extensión de la página encontrada
     * @param page Ubicación relativa de la página
     * @param location Ubicación de la vista
     * @return Buffer de la página renderizada
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-12-18
     */
    public function render ($page, $location = null)
    {
        // buscar página
        if ($location) {
            $location = self::location(\sowerphp\core\App::layer($location).'/'.$location.'/View/'.$page);
        } else {
            $location = self::location($page, $this->request->params['module']);
        }
        // si no se encontró error
        if (!$location) {
            if($this->request->params['controller']=='pages')
                $this->render('/error/404');
            else {
                throw new Exception_View_Missing(array(
                    'view' => $page,
                    'controller' => Utility_Inflector::camelize(
                        $this->request->params['controller']
                    ),
                    'action' => $this->request->params['action'],
                ));
            }
            return;
        }
        // preparar _header_extra (se hace antes de renderizar la página para
        // quitarlo de las variables por si existe
        if (isset($this->viewVars['_header_extra'])) {
            $_header_extra = '';
            if (isset($this->viewVars['_header_extra']['css'])) {
                foreach ($this->viewVars['_header_extra']['css'] as &$css) {
                    $_header_extra .= '        <link type="text/css" href="'.$this->request->base.$css.'" rel="stylesheet" />'."\n";
                }
            }
            if (isset($this->viewVars['_header_extra']['js'])) {
                foreach ($this->viewVars['_header_extra']['js'] as &$js) {
                    $_header_extra .= '        <script type="text/javascript" src="'.$this->request->base.$js.'"></script>'."\n";
                }
            }
            unset ($this->viewVars['_header_extra']);
        } else $_header_extra = '';
        // dependiendo de la extensión de la página se renderiza
        $ext = substr($location, strrpos($location, '.')+1);
        $class = App::findClass('View_Helper_Pages_'.ucfirst($ext));
        $page_content = $class::render($location, $this->viewVars);
        if ($this->layout === null)
            return $page_content;
        // buscar archivo del tema que está seleccionado, si no existe
        // se utilizará el tema por defecto
        $layout = $this->getLayoutLocation($this->layout);
        if (!$layout) {
            $this->layout = $this->defaultLayout;
            $layout = $this->getLayoutLocation($this->layout);
        }
        // página que se está viendo
        if(!empty($this->request->request)) {
            $slash = strpos($this->request->request, '/', 1);
            $page = $slash===false ? $this->request->request : substr($this->request->request, 0, $slash);
        } else $page = '/'.Configure::read('homepage');
        // determinar module breadcrumb
        $module_breadcrumb = [];
        if ($this->request->params['module']) {
            $modulos = explode('.', $this->request->params['module']);
            $url = '';
            foreach ($modulos as &$m) {
                $link = Utility_Inflector::underscore($m);
                $module_breadcrumb[$link] = $m;
                $url .= '/'.$link;
            }
            $module_breadcrumb += explode('/', substr(str_replace($url, '', $this->request->request), 1));
        }
        // determinar titulo
        $titulo_pagina = isset($this->viewVars['header_title']) ? $this->viewVars['header_title'] : $this->request->request;
        // renderizar layout de la página (con su contenido)
        return View_Helper_Pages_Php::render($layout, array_merge(array(
            '_header_title' => Configure::read('page.header.title').($titulo_pagina?': '.$titulo_pagina:''),
            '_body_title' => Configure::read('page.body.title'),
            '_footer' => Configure::read('page.footer'),
            '_header_extra' => $_header_extra,
            '_page' => $page,
            '_nav_website' => Configure::read('nav.website'),
            '_nav_app' => Configure::read('nav.app'),
            '_timestamp' => date(Configure::read('time.format'), filemtime($location)),
            '_layout' => $this->layout,
            '_content' => $page_content,
            '_module_breadcrumb' => $module_breadcrumb,
        ), $this->viewVars));
    }

    /**
     * Método que entrega la ubicación del tema que se está utilizando
     * @param layout Tema que se quiere buscar su ubicación
     * @return Ubicación del tema (o falso si no se encontró)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-09-14
     */
    private function getLayoutLocation ($layout)
    {
        $paths = App::paths();
        foreach($paths as $path) {
            $file = $path.'/View/Layouts/'.$layout.'.php';
            if (is_readable($file)) {
                return $file;
            }
        }
        return false;
    }

    /**
     * Método que busca la vista en las posibles rutas y para todas las posibles extensiones
     * @param view Nombre de la vista buscada (ejemplo: /inicio)
     * @param module Nombre del módulo en caso de pertenecer a uno
     * @return Ubicación de la vista que se busca
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-22
     */
    public static function location ($view, $module = null)
    {
        // si la página está en caché se retorna
        if (isset(self::$_viewsLocation[$view]))
            return self::$_viewsLocation[$view];
        // extensiones
        if (!self::$extensions) {
            self::$extensions = Configure::read('page.extensions');
            if(!in_array('php', self::$extensions))
                self::$extensions[] = 'php'; // php siempre debe estar
        }
        // si la vista parte con / entonces se está pasando la ruta y solo falta su extension
        if ($view[0]=='/') {
            foreach(self::$extensions as $extension) {
                if (is_readable($view.'.'.$extension)) {
                    self::$_viewsLocation[$view] = $view.'.'.$extension;
                    return $view.'.'.$extension;
                }
            }
            return null;
        }
        // Determinar de donde sacar la vista
        $location = $module ? '/Module/'.str_replace('.', '/Module/', $module) : '';
        // obtener paths
        $paths = App::paths();
        // buscar archivo en cada ruta
        foreach ($paths as $path) {
            foreach(self::$extensions as $extension) {
                $file = $path.$location.'/View/'.$view.'.'.$extension;
                if (is_readable($file)) {
                    self::$_viewsLocation[$view] = $file;
                    return $file;
                }
            }
        }
        // Si se busca la vista Module/index y no fue encontrada se carga la por defecto
        if ($view=='Module/index') {
            // Buscar el archivo de la vista en las posibles rutas
            foreach ($paths as $path) {
                $file = $path . '/View/Module/index.php';
                    if (is_readable($file)) {
                        return $file;
                    }
            }
        }
        // si no se encontró el archivo de la vista se retorna falso
        return false;
    }

}
