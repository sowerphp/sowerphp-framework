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
 * Clase para escribir y recuperar datos desde una sesión
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-04-22
 */
class Model_Datasource_Session
{

    public static $id = null; ///< Identificador de la sesión
    public static $time = false; ///< Tiempo de inicio de la sesión
    public static $config; ///< Configuración de la sesión del sitio
    private static $_prefix = null; ///< Prefijo para la sesión

    /**
     * Método que inicia la sesión
     * @param expires Indica el tiempo en segundos en que expirará la cookie de la sesión
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-10
     */
    public static function start ($expires = 3600)
    {
        // Prefijo para los datos en la sesión (esto permite múltiples sesiones
        // en una misma sesión (confuso, lo sé...)
        self::$_prefix = str_replace (
            array('/', '.'), array('_', '-'), DIR_WEBSITE
        ).'.';
        // parámetros para la cookie de la sesión
        session_set_cookie_params ($expires, (new Network_Request(false))->base());
        // Si ya estaba iniciada no se hace nada
        if (self::started())
            return true;
        // Tiempo de inicio de la sesión
        self::$time = time();
        // Obtener ID de la sesión
        self::$id = self::id();
        // Iniciar sesión
        session_start();
        // Retornar si la sesión fue iniciada y es válida
        if(self::started() && self::valid())
            return true;
        return false;
    }

    /**
     * Carga configuración del inicio de la sesión
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-22
     */
    public static function configure ()
    {
        // idioma
        if (!self::read('config.language')) {
            $defaultLang = Configure::read('language');
            $userLang = (new Network_Request(false))->header('Accept-Language');
            if ($userLang) {
                $userLang = explode(',', explode('-', $userLang)[0])[0];
                if ($userLang === explode('_', $defaultLang)[0] || I18n::localeExists($userLang)) {
                    self::write('config.language', $userLang);
                } else {
                    self::write('config.language', $defaultLang);
                }
            } else {
                self::write('config.language', $defaultLang);
            }
        }
        // layout
        if (!self::read('config.page.layout')) {
            self::write('config.page.layout', Configure::read('page.layout'));
        }
    }

    /**
     * Asigna u obtiene el valor de id de la sesión
     * @param id Nuevo identificador para la sesión
     * @return Identificador de la sesión
     * @author CakePHP
     */
    public static function id ($id = null)
    {
        // Si se paso un id se utiliza como nuevo
        if ($id) {
            session_id(self::$id);
            self::$id = $id;
        }
        // Si la sesión ya se inició se recupera su id
        if (self::started()) {
            self::$id = session_id();
        }
        // Devolver el id de la sesión
        return self::$id;
    }

    /**
     * Determinar si existe una sesión iniciada
     * @return Verdadero si existe una sesión iniciada
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-11-08
     */
    public static function started()
    {
        return isset($_SESSION) && session_id();
    }

    /**
     * Retorna si la sesión es o no válida
     * @return Verdadero si la sesión es válida
     * @todo Verificar que la sesión sea válida y no hayan "Session Highjacking Attempted!!!" (AHORA NO SE ESTA VALIDANDO NADA!!!)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-11-08
     */
    public static function valid()
    {
        return true;
    }

    /**
     * Entrega true si la variable esta creada en la sesión
     * @param name Nombre de la variable que se quiere buscar
     * @return Verdadero si la variable existe en la sesión
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2013-06-12
     */
    public static function check($name)
    {
        // Verificar que la sesión este iniciada o se pueda iniciar si no lo esta
        if (!self::started() && !self::start()) {
            return false;
        }
        // Retornar si existe o no la variable
        $result = Utility_Set::classicExtract(
            $_SESSION,
            self::$_prefix.$name
        );
        return isset($result);
    }

    /**
     * Recuperar el valor de una variable de sesión
     * @param name Nombre de la variable que se desea leer
     * @return Valor de la variable o falso en caso que no exista o la sesión no este iniciada
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2013-06-12
     */
    public static function read ($name = null)
    {
        // Verificar que la sesión este iniciada o se pueda iniciar si no lo esta
        if (!self::started() && !self::start()) {
            return false;
        }
        // Si no se indico un nombre, se entrega todo el arreglo de la sesión
        if ($name==null) {
            if (self::$_prefix) {
                return $_SESSION[substr(self::$_prefix, 0, -1)];
            } else {
                return $_SESSION;
            }
        }
        // Verificar que lo solicitado existe
        if(!self::check($name)) {
            return false;
        }
        // Extraer los datos que se están solicitando
        $result = Utility_Set::classicExtract(
            $_SESSION,
            self::$_prefix.$name
        );
        // Retornar lo solicitado (ya se reviso si existía, por lo cual si es null es válido el valor)
        return $result;
    }

    /**
     * Quitar una variable de la sesión
     * @param name Nombre de la variable que se desea eliminar
     * @return Verdadero si se logro eliminar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2013-06-12
     */
    public static function delete($name)
    {
        // Si la variable existe se quita
        if (self::check($name)) {
            self::_overwrite($_SESSION, Utility_Set::remove(
                $_SESSION,
                self::$_prefix.$name
            ));
            return (self::check($name) == false);
        }
        // En caso que no se encontrara la variable se retornará falso
        return false;
    }

    /**
     * Escribir un valor de una variable de sesión
     * @param name Nombre de la variable
     * @param value Valor que se desea asignar a la variable
     * @return Verdadero si se logró escribir la variable de sesión
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2013-06-12
     */
    public static function write($name, $value = null)
    {
        // Verificar que la sesión este iniciada o se pueda iniciar si no lo esta
        if (!self::started() && !self::start()) {
            return false;
        }
        // Armar el arreglo necesario para realizar la escritura
        $name = self::$_prefix.$name;
        $write = $name;
        if (!is_array($name)) {
            $write = array($name => $value);
        }
        // Por cada elemento del arreglo escribir los datos de la sesión
        foreach ($write as $key => $val) {
            self::_overwrite($_SESSION, Utility_Set::insert($_SESSION, $key, $val));
            if (Utility_Set::classicExtract($_SESSION, $key) !== $val) {
                return false;
            }
        }
        return true;
    }

    /**
     * Used to write new data to _SESSION, since PHP doesn't like us setting the _SESSION var itself
     * @param old Antiguo conjunto de datos
     * @param new Nuevo conjunto de datos
     * @author CakePHP
     */
    protected static function _overwrite(&$old, $new)
    {
        if (!empty($old)) {
            foreach ($old as $key => $var) {
                if (!isset($new[$key])) {
                    unset($old[$key]);
                }
            }
        }
        foreach ($new as $key => $var) {
            $old[$key] = $var;
        }
    }

    /**
     * Método para destruir e invalidar una sesión
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2013-06-12
     */
    public static function destroy()
    {
        // Verificar que la sesión este iniciada o se pueda iniciar si no lo esta
        if (!self::started() && !self::start()) {
            return false;
        }
        // "destruir" la sesión
        unset($_SESSION[substr(self::$_prefix, 0, -1)]);
    }

    /**
     * Método para escribir un mensaje de sesión y recuperarlo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-11-08
     */
    public static function message ($message = null)
    {
        // si se indicó un mensaje se asigna
        if($message) {
            self::write('session.message', $message);
        }
        // si no se indico un mensaje se recupera y limpia
        else {
            $message = self::read('session.message');
            self::delete('session.message');
            return $message;
        }
    }

}
