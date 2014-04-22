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
 * Clase genérica con método para trabajar con cualquier objeto
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-04-19
 */
abstract class Object
{

    /**
     * Método para convertir el objeto a un string.
     * @return Nombre de la clase con que se instancio el objeto
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    public function __toString ()
    {
        return get_class($this);
    }

    /**
     * Método para obtener los atributos del objeto y sus valores como
     * un arreglo
     * @return Arreglo asociativo con los atributos y valores del objeto
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-10-07
     */
    public function toArray ()
    {
        $array = array();
        foreach ($this as $key => &$value) {
            $array[$key] = $value;
        }
        return $array;
    }

    /**
     * Método que asigna múltiples atributos de un objeto
     * @param properties Arreglo asociativo con los atributos a asignar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-10-29
     */
    protected function _set ($properties = array())
    {
        if (is_array($properties) && !empty($properties)) {
            $vars = get_object_vars($this);
            foreach ($properties as $key => $val) {
                if (array_key_exists($key, $vars)) {
                    $this->{$key} = $val;
                }
            }
        }
    }

}
