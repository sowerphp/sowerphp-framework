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
 * Clase para manejar modulos: cargarlos, rutas y bootstrap
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-22
 */
class Module
{

    private static $_modules = array(); ///< Listado de modulos cargados
    private static $_filesLoadeables = array( ///< Archivos que se buscarán para cargar de forma automática al cargar el módulo
        'basics',
        'bootstrap',
        'Config/core',
        'Config/routes',
    );

    /**
     * Método para indicar que se utilizará un módulo (no lo carga, solo
     * indica que se podrá usar). Se utiliza en Config/core.php para indicar
     * que se desea utilizar un módulo.
     * @param module Nombre del módulo (o un arreglo de módulos con sus configuraciones)
     * @param config Arreglo con configuración del módulo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-02-24
     */
    public static function uses ($module, $config = array())
    {
        // Si se paso un arreglo se procesa cada uno por separado
        if (is_array($module)) {
            // Cada elemento del arreglo será un modulo
            foreach ($module as $name => $conf) {
                // Si se paso solo el nombre del módulo y no su configuración
                // se convierte al formato requerido de 'Modulo'=>array()
                if (!is_array($conf)) {
                    $name = $conf;
                    $conf = array();
                }
                // Indicar que se usará el módulo
                self::uses($name, $conf);
            }
            // Una vez procesados todos los modulos pasados terminar el uses
                return;
        }
        // Asignar opciones por defecto
        $config = array_merge(
            array(
                // ruta real del módulo
                'path' => array(),
                // si se debe cargar el módulo automáticamente
                'autoLoad' => false,
                // el módulo no se encuentra cargado
                'loaded' => false,
            ), $config
        );
        // Guardar configuración del modulo y cargar si es necesario
        self::$_modules[$module] = $config;
        if ($config['autoLoad']) {
            self::load($module);
        }
    }

    /**
     * Cargar módulo e inicializarlo
     * @param module Nombre del módulo que se desea cargar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-22
     */
    public static function load ($module)
    {
        // si el módulo ya está cargado se retorna
        if (self::$_modules[$module]['loaded']) {
            return;
        }
        // Si no se indicó el path donde se encuentra el módulo se deberá
        // determinar, se buscarán todos los paths donde el módulo pueda
        // existir
        if (!isset(self::$_modules[$module]['path'][0])) {
            self::$_modules[$module]['path'] = array();
            $paths = App::paths();
            foreach ($paths as &$path) {
                // Verificar que el directorio exista (el reemplazo es para los
                // submódulos)
                $modulePath = $path.'/Module/'.str_replace('.', '/Module/', $module);
                if (is_dir($modulePath)) {
                    self::$_modules[$module]['path'][] = $modulePath;
                }
            }
        }
        // Si se indicó se verifica que exista y se agrega como único path para
        // el modulo al arreglo
        else {
            // Si el directorio existe se agrega
            if (is_dir(self::$_modules[$module]['path'])) {
                self::$_modules[$module]['path'] = array(self::$_modules[$module]['path']);
            }
            // Si no existe se elimina el path para generar error posteriormente
            else {
                self::$_modules[$module]['path'] = array();
            }
        }
        // Si el módulo no fue encontrado se crea una excepción
        if (!isset(self::$_modules[$module]['path'][0])) {
            throw new Exception_Module_Missing(array('module' => $module));
        }
        // verificar "archivos cargables", si existen se cargan
        foreach (self::$_filesLoadeables as &$file) {
            $path = self::fileLocation($module, $file);
            if ($path) {
                include $path;
            }
        }
        // Indicar que el módulo fue cargado
        self::$_modules[$module]['loaded'] = true;
    }

    /**
     * Entrega la ruta completa para un archivo
     * @param module Nombre del modulo
     * @param file Ruta hacia el archivo, sin .php
     * @return Ruta completa para el archivo solicitado o null si no existe
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-17
     */
    public static function fileLocation ($module, $file)
    {
        $paths = App::paths();
        foreach ($paths as &$path) {
            $fileLocation = $path.'/Module/'.str_replace('.', '/Module/', $module).'/'.$file.'.php';
            if (file_exists($fileLocation)) {
                return $fileLocation;
            }
        }
        return null;
    }

    /**
     * Entrega listado de modulos cargados o si se especifica un modulo
     * si ese esta cargado o no
     * @param module Módulo que se desea verificar si está cargado o null para pedir todos los módulos cargados
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-02-24
     */
    public static function loaded ($module = null)
    {
        // Si existe el nombre del modulo, se indica si esta o no cargado
        if ($module) {
            return isset(self::$_modules[$module]);
        }
        // Se retornan todos los modules cargados
        $return = array_keys(self::$_modules);
        sort($return);
        return $return;
    }

    /**
     * Determina a partir de una URL si esta corresponde o no a un módulo,
     * en caso que sea un módulo lo carga (aquí se hace la carga real del
     * módulo que se indicó con self::uses())
     * @param url Solicitud realizada (sin la base de la aplicación)
     * @return Nombre del módulo si es que existe uno en la url
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-22
     */
    public static function find ($url)
    {
        // Separar por "/"
        $partes = explode('/', $url);
        // Quitar primer elemento, ya que si parte con / entonces será vacío
        if (!strlen($partes[0]))
            array_shift($partes);
        // Determinar hasta que elemento de la url corresponde a parte de un modulo
        $npartes = count($partes);
        $hasta = -1;
        for ($i=0; $i<$npartes; ++$i) {
            // armar nombre del modulo
            $module = array();
            for ($j=0; $j<=$i; ++$j) {
                $module[] = Utility_Inflector::camelize($partes[$j]);
            }
            $module = implode('.', $module);
            // determinar si dicho modulo existe
            if (array_key_exists($module, self::$_modules)) {
                $hasta = $i;
            }
        }
        // Si $hasta es mayor a -1
        if ($hasta>=0) {
            // Armar nombre final del modulo (considerando hasta $hasta partes del arreglo de partes)
            $module = array();
            for($i=0; $i<=$hasta; ++$i) {
                $module[] = Utility_Inflector::camelize($partes[$i]);
            }
            // cargar módulo
            $module = implode('.', $module);
            // retornar nombre del modulo
            return $module;
        } else {
            return '';
        }
    }

    /**
     * Entrega la rutas donde se encuentra el módulo
     * @param module Nombre del módulo
     * @return Rutas donde el módulo existe
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2013-06-10
     */
    public static function paths ($module)
    {
        if(isset(self::$_modules[$module]))
            return self::$_modules[$module]['path'];
        else
            return null;
    }

    /**
     * Separa el nombre del módulo del nombre de la clase que se desea cargar
     * @param name Nombre a separar
     * @return Arreglo con el nombre del módulo y la clase
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-11-20
     */
    public static function split ($name)
    {
        $lastdot = strrpos($name, '.');
        if ($lastdot!==false) {
            $module = substr($name, 0, $lastdot);
            $name = substr($name, $lastdot+1);
        } else {
            $module = '';
        }
        return array($module, $name);
    }

}
