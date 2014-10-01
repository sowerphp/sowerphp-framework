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
 * Clase para manejar rutas de la aplicación
 * Las rutas conectan URLs con controladores y acciones
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-05-06
 */
class Routing_Router
{

    private static $routes = array(); ///< Rutas conectadas, "does the magic"
    public static $autoStaticPages = true; ///< Permite cargar páginas estáticas desde /, sin usar /pages/

    /**
     * Procesa la url indicando que es lo que se espera obtener según las
     * rutas que existen conectadas
     * @todo Buscar forma de reducir este método pero manteniendo las mismas funcionalidades del parser
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-17
     */
    public static function parse ($url)
    {
        // La url requiere partir con "/", si no lo tiene se coloca
        if (empty($url) || $url[0]!='/')
            $url = '/'.$url;
        // Si existe una ruta para la url que se está revisando se carga su configuración
        if (isset(self::$routes[$url])) {
            return self::routeNormalize(self::$routes[$url]);
        }
        // buscar página estática
        if (self::$autoStaticPages && ($params = self::parseStaticPage ($url))!==false) {
            return $params;
        }
        // buscar página estática nuevamente, pero esta vez dentro del módulo (si existe)
        $module = Module::find($url);
        if (self::$autoStaticPages && ($params = self::parseStaticPage (self::urlClean ($url, $module), $module))!==false) {
            return $params;
        }
        // Buscar alguna que sea parcial (:controller, :action o *)
        foreach (self::$routes as $key=>$aux) {
            $params = array_merge(array('module'=>null, 'controller'=>null, 'action'=>null, 'pass'=>null), $aux);
            // Tiene :controller
            $controller = strpos($key, ':controller');
            if ($controller) {
                $inicioKey = rtrim(substr($key, 0, $controller), '/');
                // Si la URL parte con lo que está antes de :controler
                if (strpos($url, $inicioKey)===0) {
                    $ruta = ltrim(str_replace($inicioKey, '', $url), '/');
                    $partes = explode('/', $ruta);
                    $params['controller'] = array_shift($partes);
                    if (empty($params['action']))
                        $params['action'] = count($partes) ? array_shift($partes) : 'index';
                    $params['pass'] = $partes;
                    return $params;
                } else continue;
            }
            // No tiene :controller, pero si :action
            $action = strpos($key, ':action');
            if ($action) {
                $inicioKey = rtrim(substr($key, 0, $action), '/');
                // Si la URL parte con lo que está antes de :action
                if (strpos($url, $inicioKey)===0) {
                    $ruta = ltrim(str_replace($inicioKey, '', $url), '/');
                    if (strlen($ruta)) {
                        $partes = explode('/', $ruta);
                        if (empty($params['action']))
                            $params['action'] = count($partes) ? array_shift($partes) : 'index';
                        $params['pass'] = $partes;
                    } else {
                        $params['action'] = 'index';
                        $params['pass'] = array();
                    }
                    return $params;
                } else continue;
            }
            // Si no esta el tag :controler ni el de :action se busca si termina en *
            if ($key[strlen($key)-1]=='*') {
                $ruta = substr($key, 0, -1);
                // Si se encuentra la ruta al inicio de la url
                if (strpos($url, $ruta)===0) {
                    $params['pass'] = explode('/', str_replace($ruta, '', $url));
                    return $params;
                }
            }
        }
        // Procesar la URL recibida, en el formato /modulo(s)/controlador/accion/parámetro1/parámetro2/etc
        $url = self::urlClean ($url, $module);
        // Arreglo por defecto para los datos de módulo, controlador, accion y parámetros pasados
        $params = array('module'=>$module, 'controller'=>null, 'action'=>'index', 'pass'=>null);
        // Separar la url solicitada en partes separadas por los "/"
        $partes = explode('/', $url);
        // quitar primer elemento que es vacio, ya que el string parte con "/"
        array_shift($partes);
        $params['controller'] = array_shift($partes);
        $params['action'] = count($partes) ? array_shift($partes) : 'index';
        $params['pass'] = $partes;
        // Si no hay controlador y es un módulo se asigna un controlador estándar para cargar la página con el menú del modulo
        if (empty($params['controller']) && !empty($params['module'])) {
            $params['controller'] = 'module';
            $params['action'] = 'display';
        }
        // Retornar url procesada
        return $params;
    }

    /**
     * Método para conectar nuevas rutas
     * @param from Ruta que se desea conectar (URL)
     * @param to Hacia donde (módulo, controlador, acción y parámetros) se conectará la ruta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-11-15
     */
    public static function connect ($from, $to = array())
    {
        self::$routes[$from] = $to;
        krsort(self::$routes);
    }

    /**
     * Método para obtener una ruta normalizada (con todos sus campos obligatorios)
     * @param route Arreglo con los datos de la ruta, índice 'controller' es obligatorio
     * @return Arreglo con la ruta normalizada, incluyendo índices: 'module', 'controller', 'action' y 'pass'
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-14
     */
    private static function routeNormalize ($route)
    {
        $params = [
            'module' => isset($route['module']) ? $route['module'] : null,
            'controller' => $route['controller'],
            'action' => isset($route['action']) ? $route['action'] : 'index'
        ];
        unset($route['module'], $route['controller'], $route['action']);
        $params['pass'] = $route;
        return $params;
    }

    /**
     * Método que quita el módulo solicitado de la parte de la URL
     * @param url URL
     * @param module Nombre del módulo (ejemplo: Nombre.De.ModuloQueSeEjecuta)
     * @return URL sin el módulo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-01
     */
    private static function urlClean ($url, $module)
    {
        if ($module) {
            $url = substr(
                    Utility_String::replaceFirst(
                        str_replace('.', '/', Utility_Inflector::underscore($module)),
                        '',
                        $url
                    )
                    , 1
            );
        }
        return $url;
    }

    /**
     * Método que busca si existe una página estática para la URL solicitada
     * @param url URL
     * @param module Nombre del módulo (ejemplo: Nombre.De.ModuloQueSeEjecuta)
     * @return Parámetros para despachar la página estática o false si no se encontró una
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-14
     */
    private static function parseStaticPage ($url, $module = null) {
        $location = View::location('Pages'.$url, $module);
        if ($location) {
            return [
                'module' => $module,
                'controller' => 'pages',
                'action' => 'display',
                'pass' => [$url]
            ];
        }
        return false;
    }

}
