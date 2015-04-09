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
 * Clase para configurar la aplicación
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-04-21
 */
class Configure
{

    protected static $_values = array(); ///< Valores de la configuración

    /**
     * Realizar configuración al inicio de la aplicación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-04-09
     */
    public static function bootstrap()
    {
        // Incluir configuraciones para el sitio web
        $paths = array_reverse(App::paths());
        foreach ($paths as &$path) {
            App::import($path.'/Config/core');
        }
        // Incluir rutas (se debe hacer por separado, primero la
        // configuración y luego rutas)
        foreach ($paths as &$path) {
            App::import($path.'/Config/routes');
        }
        // Setear parámetros de errores
        ini_set('display_errors', self::$_values['debug']);
        error_reporting(self::$_values['error']['level']);
        set_exception_handler('sowerphp\core\Exception::handler');
        // Definir la zona horaria
        date_default_timezone_set(self::$_values['time']['zone']);
        // definir directorio temporal
        if (is_writable(DIR_PROJECT.'/tmp'))
            define('TMP', DIR_PROJECT.'/tmp');
        else
            define('TMP', sys_get_temp_dir());
        // cargar reglas de Inflector para el idioma de la aplicación
        App::import ('Config/Inflector/'.self::$_values['language']);
        // procesar cada capa (excepto SowerPHP/core) buscando funciones y bootstrap
        $paths = array_reverse(App::paths());
        array_shift($paths);
        foreach ($paths as &$path) {
            // Cargar funciones
            if (file_exists($path.'/basics.php')) {
                include $path.'/basics.php';
            }
            // Cargar bootstrap
            if (file_exists($path.'/bootstrap.php')) {
                include $path.'/bootstrap.php';
            }
        }
        unset($paths, $path);
    }

    /**
     * Escribir un valor en el arreglo de configuración
     * Se puede pasar un arreglo con la configuración como un solo
     * parámetro.
     * @param config Parámetro
     * @param value Valor
     * @author CakePHP
     */
    public static function write ($config, $value = null)
    {
        // Si config no es arreglo se crea como arreglo
        if (!is_array($config)) {
            $config = array($config => $value);
            unset($value);
        }
        // Guardar cada una de las variables pasadas
        foreach ($config as $name => $value) {
            // Si el nombre no tiene punto, entonces se crea directamente la variable
            if (strpos($name, '.') === false) {
                self::$_values[$name] = $value;
            }
            // En caso que tuviese punto se asume que se debe dividir en niveles
            else {
                $names = explode('.', $name, 4);
                switch (count($names)) {
                    case 2:
                        self::$_values[$names[0]][$names[1]] = $value;
                        break;
                    case 3:
                        self::$_values[$names[0]][$names[1]][$names[2]] = $value;
                        break;
                    case 4:
                        self::$_values[$names[0]][$names[1]][$names[2]][$names[3]] = $value;
                        break;
                }
            }
        }
    }

    /**
     * Leer el valor de una variable desde el arreglo de configuraciones
     * @param var Variable / parámetro que se desea leer
     * @return Valor de la variable o nulo si no se encontró
     * @author CakePHP
     */
    public static function read ($var = null)
    {
        // Si var no se especificó se devuelven todas las configuraciones
        if ($var === null) {
            return self::$_values;
        }
        // Si var coincide con una clave del arreglo se devuelve
        if (isset(self::$_values[$var])) {
            return self::$_values[$var];
        }
        // En caso que existan puntos se obtiene el nombre de la primera clave
        if (strpos($var, '.') !== false) {
            $names = explode('.', $var, 4);
            $var = $names[0];
        }
        // Si la variable no existe se retorna nulo
        if (!isset(self::$_values[$var])) {
            return null;
        }
        // Si se llegó aquí es porque la variable (primera clave del arreglo $_values existe)
        switch (count($names)) {
            case 2:
                if (isset(self::$_values[$names[0]][$names[1]])) {
                    return self::$_values[$names[0]][$names[1]];
                }
                break;
            case 3:
                if (isset(self::$_values[$names[0]][$names[1]][$names[2]])) {
                    return self::$_values[$names[0]][$names[1]][$names[2]];
                }
                break;
            case 4:
                if (isset(self::$_values[$names[0]][$names[1]][$names[2]][$names[3]])) {
                    return self::$_values[$names[0]][$names[1]][$names[2]][$names[3]];
                }
                break;
        }
        // Si no se encontró definida la variable
        return null;
    }

}
