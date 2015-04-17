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
 * Utilidad para validar datos
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2015-04-16
 */
class Utility_Data_Validation
{

    public static $regexs = [
        'email' => '/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/',
        'date' => '/^\d{4}[\-](0?[1-9]|1[012])[\-](0?[1-9]|[12][0-9]|3[01])$/',
        'telephone' => '/^\+\d{1,4}[ ]([1-9]{1}|\d{1,2})[ ]\d{7,8}$/',
    ]; ///< Expresiones regulares para validar campos

    /**
     * Método para realizar validaciones a cierto dato, por ejemplo si es o no
     * un email o un número entero
     * @param data Dato que se quiere validar
     * @param rules Reglas que se revisarán para validar el dato
     * @return =true si todo va ok, =false o =string si hubo un error al validar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-04-16
     */
    public static function check($data, $rules)
    {
        // probar cada una de las reglas
        foreach ($rules as &$rule) {
            // si existe una expresión regular para la regla se usa
            if (isset(self::$regexs[$rule])) {
                if (!preg_match(self::$regexs[$rule], $data)) {
                    return $rule;
                }
            }
            // si existe un método para la regla se usa
            else if (method_exists(__CLASS__, 'check_'.$rule)) {
                if (!call_user_func_array([__CLASS__, 'check_'.$rule], [$data])) {
                    return $rule;
                }
            }
            // si el tipo de chequeo no existe error
            else {
                throw new Exception(array(
                    sprintf ('Regla %s para validar datos no existe', $rule)
                ));
            }
        }
        // se pasaron todas las validaciones
        return true;
    }

    /**
     * Método que valida que el dato no sea vacío
     * @param data Dato que se quiere validar
     * @return =true si no es vacio
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-04-16
     */
    public function check_notempty($data)
    {
        return !empty($data);
    }

    /**
     * Método que valida que el dato sea una representación de un entero
     * @param data Dato que se quiere validar
     * @return =true si es un entero
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-04-16
     */
    public function check_integer($data)
    {
        return ctype_digit(strval($data));
    }

}
