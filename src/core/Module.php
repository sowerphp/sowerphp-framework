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
 * Clase para manejar modulos: cargarlos, rutas y bootstrap.
 */
class Module
{

    // Listado de modulos cargados.
    private static $_modules = [];

    // Archivos que se buscarán para cargar de forma automática al
    // cargar el módulo.
    private static $_filesLoadeables = [
        'App/functions',
        'App/bootstrap',
        'App/config',
        'App/routes',
    ];

    /**
     * Método para indicar que se utilizará un módulo (no lo carga,
     * solo indica que se podrá usar). Se utiliza en App/config.php para
     * indicar que se desea utilizar un módulo usando este método.
     * @param string|array $module Nombre del módulo o un arreglo de
     * módulos con sus configuraciones.
     * @param array $config Arreglo con configuración del módulo.
     */
    public static function uses($module, array $config = []): void
    {
        // Si se paso un arreglo se procesa cada uno por separado.
        if (is_array($module)) {
            // Cada elemento del arreglo será un módulo
            foreach ($module as $name => $conf) {
                // Si se paso solo el nombre del módulo y no su
                // configuración se convierte al formato requerido de
                // 'Modulo' => []
                if (!is_array($conf)) {
                    $name = $conf;
                    $conf = [];
                }
                // Indicar que se usará el módulo
                self::uses($name, $conf);
            }
        }
        // Procesar un módulo con su configuración.
        else {
            // Asignar opciones por defecto
            $config = array_merge([
                // Ruta real del módulo.
                'path' => [],
                // Si se debe cargar el módulo automáticamente.
                'autoLoad' => false,
                // El módulo no se encuentra cargado.
                'loaded' => false,
            ], $config);
            // Guardar configuración del modulo y cargar si es necesario.
            self::$_modules[$module] = $config;
            if ($config['autoLoad']) {
                self::load($module);
            }
        }
    }

    /**
     * Método para indicar que se debe descartar un módulo previamente
     * cargado.
     * @param string|array $module Nombre del módulo a descartar.
     */
    public static function drop($module): void
    {
        if (!is_array($module)) {
            $module = [$module];
        }
        foreach ($module as $name) {
            unset(self::$_modules[$name]);
        }
    }

    /**
     * Cargar módulo e inicializarlo.
     * @param string $module Nombre del módulo que se desea cargar.
     */
    public static function load(string $module)
    {
        // si el módulo ya está cargado se retorna
        if (self::$_modules[$module]['loaded']) {
            return;
        }
        // Si no se indicó el path donde se encuentra el módulo se
        // deberá determinar, se buscarán todos los paths donde el
        // módulo pueda existir.
        if (!isset(self::$_modules[$module]['path'][0])) {
            self::$_modules[$module]['path'] = [];
            $paths = app()->paths();
            foreach ($paths as &$path) {
                // Verificar que el directorio exista (el reemplazo es
                // para los submódulos)
                $modulePath = $path . '/Module/'
                    . str_replace('.', '/Module/', $module)
                ;
                if (is_dir($modulePath)) {
                    self::$_modules[$module]['path'][] = $modulePath;
                }
            }
        }
        // Si se indicó se verifica que exista y se agrega como único
        // path para el modulo al arreglo.
        else {
            // Si el directorio existe se agrega
            if (is_dir(self::$_modules[$module]['path'])) {
                self::$_modules[$module]['path'] = [
                    self::$_modules[$module]['path'],
                ];
            }
            // Si no existe se elimina el path para generar error
            // posteriormente.
            else {
                self::$_modules[$module]['path'] = [];
            }
        }
        // Si el módulo no fue encontrado se crea una excepción.
        if (!isset(self::$_modules[$module]['path'][0])) {
            throw new Exception_Module_Missing(['module' => $module]);
        }
        // Verificar "archivos cargables", si existen se cargan.
        foreach (self::$_filesLoadeables as &$file) {
            $location = self::fileLocation($module, $file);
            if ($location) {
                include $location;
            }
        }
        // Indicar que el módulo fue cargado.
        self::$_modules[$module]['loaded'] = true;
    }

    /**
     * Entrega la ruta completa para un archivo.
     * @param string $module Nombre del modulo.
     * @param string $file Ruta hacia el archivo, sin .php
     * @return string Ruta completa para el archivo solicitado o null
     * si no existe.
     */
    public static function fileLocation(string $module, string $file): ?string
    {
        $filename = '/Module/' . str_replace('.', '/Module/', $module)
            . '/' . $file . '.php'
        ;
        $location = app()->location($filename);
        if ($location) {
            return $location;
        }
        return null;
    }

    /**
     * Entrega listado de modulos cargados o si se especifica un modulo
     * si ese esta cargado o no.
     * @param string $module Módulo que se desea verificar si está
     * cargado o null para pedir todos los módulos cargados.
     * @return bool|array
     */
    public static function loaded(?string $module = null)
    {
        // Si existe el nombre del modulo, se indica si esta o no cargado.
        if ($module) {
            return isset(self::$_modules[$module]);
        }
        // Se retornan todos los modules cargados
        $modules = array_keys(self::$_modules);
        sort($modules);
        return $modules;
    }

    /**
     * Determina a partir de una URL si esta corresponde o no a un
     * módulo, en caso que sea un módulo lo carga (aquí se hace la carga
     * real del módulo que se indicó con self::uses()).
     * @param string $url Solicitud realizada (sin la base de la aplicación).
     * @return string Nombre del módulo si es que existe uno en la URL.
     */
    public static function find(string $url): string
    {
        // Separar por "/".
        $partes = explode('/', $url);
        // Quitar primer elemento, ya que si parte con / entonces será
        // vacío.
        if (!strlen($partes[0])) {
            array_shift($partes);
        }
        // Determinar hasta que elemento de la url corresponde a parte
        // de un módulo.
        $npartes = count($partes);
        $hasta = -1;
        for ($i=0; $i<$npartes; ++$i) {
            // Armar nombre del modulo.
            $module = [];
            for ($j=0; $j<=$i; ++$j) {
                $module[] = Utility_Inflector::camelize($partes[$j]);
            }
            $module = implode('.', $module);
            // Determinar si dicho modulo existe.
            if (array_key_exists($module, self::$_modules)) {
                $hasta = $i;
            }
        }
        // Si $hasta es mayor a -1.
        if ($hasta >= 0) {
            // Armar nombre final del modulo (considerando hasta $hasta
            // partes del arreglo de partes).
            $module = [];
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
     * Entrega la rutas donde se encuentra el módulo.
     * @param string $module Nombre del módulo.
     * @return array Rutas donde el módulo existe.
     */
    public static function paths($module)
    {
        if(isset(self::$_modules[$module])) {
            return self::$_modules[$module]['path'];
        } else {
            return null;
        }
    }

    /**
     * Separa el nombre del módulo del nombre de la clase que se desea
     * cargar.
     * @param string $name Nombre a separar
     * @return array Arreglo con el nombre del módulo y la clase.
     */
    public static function split(string $name): array
    {
        $lastdot = strrpos($name, '.');
        if ($lastdot !== false) {
            $module = substr($name, 0, $lastdot);
            $name = substr($name, $lastdot + 1);
        } else {
            $module = '';
        }
        return array($module, $name);
    }

}
