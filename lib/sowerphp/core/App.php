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
 * Clase para cargar otras clases y/o archivos
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-04-04
 */
class App
{

    private static $_layers = array(); ///< Mapa de capas y su ubicación
    private static $_paths = array(); ///< Rutas donde se buscarán los archivos de la aplicación

    /**
     * Método que agrega las capas de la aplicación
     * @param extensions Arreglo con las capas de la aplicación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-22
     */
    public static function createLayers ($extensions)
    {
        self::$_layers['website'] = DIR_PROJECT;
        self::$_paths[] = DIR_PROJECT.'/website';
        foreach ($extensions as &$extension) {
            if (is_dir(DIR_PROJECT.'/extensions/'.$extension)) {
                self::$_layers[$extension] = DIR_PROJECT.'/extensions';
                self::$_paths[] = DIR_PROJECT.'/extensions/'.$extension;
            } else {
                self::$_layers[$extension] = DIR_FRAMEWORK.'/extensions';
                self::$_paths[] = DIR_FRAMEWORK.'/extensions/'.$extension;
            }
        }
        self::$_layers['sowerphp/core'] = DIR_FRAMEWORK.'/lib';
        self::$_paths[] = DIR_FRAMEWORK.'/lib/sowerphp/core';
    }

    /**
     * Método que indica si una capa existe o no
     * @param layer Capa que se busca
     * @return =true si la capa existe
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-26
     */
    public static function layerExists ($layer)
    {
        return isset (self::$_layers[$layer]);
    }

    /**
     * Método para autocarga de clases
     * @param class Clase que se desea cargar
     * @param loadAlias =true si no se encuentra la clase se buscará un alias para la misma
     * @return =true si se encontró la clase
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-25
     */
    public static function loadClass ($class, $loadAlias = true)
    {
        if (($fs = strpos($class, '\\'))!==false) {
            $class = str_replace ('\\', '/', $class);
            $ns = explode ('/', $class);
            $file = str_replace('_', '/', array_pop($ns)).'.php';
            $layer = $ns[0]!='website' ? array_shift($ns).'/'.array_shift($ns) : array_shift($ns);
            $subns = isset($ns[0]) ? '/Module/'.implode('/Module/', $ns) : '';
            $fileLocation = self::$_layers[$layer].'/'.$layer.$subns.'/'.$file;
            if (isset(self::$_layers[$layer]) && file_exists($fileLocation)) {
                return include_once $fileLocation;
            }
        }
        if($loadAlias && self::loadClassAlias ($class)) {
            return true;
        }
        return false;
    }

    /**
     * Método que carga clases buscándola en las layers y creando un alias para
     * el namespace donde se encuentra la clase
     * @param class Clase que se quiere cargar a través de un alias
     * @return =true si se encontró la clase
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-25
     */
    public static function loadClassAlias ($class)
    {
        // si no se encontró o se solicitó una clase de forma directa (sin el
        // namespace) buscar si existe en alguna capa y crear un alias para la
        // clase
        $realClass = self::findClass ($class);
        if ($realClass) {
            if (self::loadClass ($realClass, false)) {
                class_alias ($realClass, $class);
                return true;
            }
        }
        // si definitivamente no se encontró retornar falso
        return false;
    }

    /**
     * Método que busca una clase en las posibles ubicaciones (capas y módulos)
     * y entrega el nombre de la clase con su namespace correspondiente
     * @param class Clase que se está buscando
     * @param module Módulo donde buscar la clase (si existe uno)
     * @return Entrega el FQN de la clase (o sea con el namespace completo)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-23
     */
    public static function findClass ($class, $module = null)
    {
        $file = str_replace ('_', '/', $class).'.php';
        if (!$module) {
            foreach (self::$_layers as $layer => $location) {
                if (file_exists($location.'/'.$layer.'/'.$file)) {
                    return str_replace ('/', '\\', $layer).'\\'.$class;
                }
            }
        } else {
            foreach (self::$_layers as $layer => &$location) {
                $fileLocation = $location.'/'.$layer.'/Module/'.str_replace('.', '/Module/', $module).'/'.$file;
                if (file_exists($fileLocation)) {
                    return str_replace (array('/', '.'), '\\', $layer.'\\'.$module).'\\'.$class;
                }
            }
        }
        return $class;
    }

    /**
     * Entrega las rutas registradas
     * @return Las rutas registradas
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-04
     */
    public static function paths ()
    {
        return self::$_paths;
    }

    /**
     * Entrega la ruta a la capa solicitada
     * @param layer Capa que se requiere su ruta
     * @return Ruta hacia la capa
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-04
     */
    public static function layer ($layer)
    {
        if (isset(self::$_layers[$layer]))
            return self::$_layers[$layer];
        return false;
    }

    /**
     * Método para importar (usando include)
     * Si se pasa una ruta absoluta se incluirá solo ese archivo si
     * existe, si es relativa se buscará en los posibles paths
     * @param archivo Archivo que se desea incluir (sin extensión .php)
     * @return Verdadero si se pudo incluir el archivo (falso si no)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-10-29
     */
    public static function import ($archivo)
    {
        // Si es una ruta absoluta
        if ($archivo[0]=='/' || strpos($archivo, ':\\')) {
            if (file_exists($archivo.'.php')) {
                return include_once $archivo.'.php';
            }
            return false;
        }
        // Buscar el archivo en las posibles rutas
        foreach (self::$_paths as $path) {
            $file = $path.'/'.$archivo.'.php';
            if (file_exists($file)) {
                return include_once $file;
            }
        }
        // Si no se encontró el archivo se retorna falso
        return false;
    }

    /**
     * Método que entrega la ubicación real de un archivo (busca en los
     * posibles paths)
     * @param archivo Archivo que se está buscando
     * @return Ruta del archivo si fue encontrado (falso si no)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-09-14
     */
    public static function location ($archivo)
    {
        foreach (self::$_paths as $path) {
            $file = $path.'/'.$archivo;
            if (file_exists($file)) {
                return $file;
            }
        }
        return false;
    }

}
