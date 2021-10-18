<?php

/**
 * SowerPHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
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

namespace sowerphp\app;

/**
 * Modelo general para trabajar con autenticación secundaria
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2017-12-22
 */
class Model_Datasource_Auth2 extends Model_Datasource_Auth2_Base
{

    /**
     * Método que permite obtener un objeto de autenticación secundaria
     * @param name Nombre del método de autenticación secundaria
     * @param config Arreglo con la configuración
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-12-23
     */
    public static function get($name = '2FA', array $config = [])
    {
        $class = '\Model_Datasource_Auth2_'.$name;
        if (!class_exists($class)) {
            throw new \Exception('Autenticación secundaria usando '.$name.' no está disponible');
        }
        if (!$config) {
            $config = \sowerphp\core\Configure::read('auth2.'.$name);
        }
        return new $class($config);
    }

    /**
     * Método que entrega todas las autenticaciones secundarias disponibles en la aplicación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-12-22
     */
    public static function getAll()
    {
        $auths2 = [];
        $auth2 = (array)\sowerphp\core\Configure::read('auth2');
        foreach ($auth2 as $name => $config) {
            $auths2[] = self::get($name, $config);
        }
        return $auths2;
    }

    /**
     * Método que indica si hay alguna auth2 que use token
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-12-23
     */
    public static function tokenEnabled()
    {
        $auths2 = self::getAll();
        foreach ($auths2 as $Auth2) {
            if ($Auth2->needToken()) {
                return true;
            }
        }
        return false;
    }

}
