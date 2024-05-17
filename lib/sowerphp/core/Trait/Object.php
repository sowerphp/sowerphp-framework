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
 * Clase genérica con método para trabajar con cualquier objeto.
 */
trait Trait_Object
{

    /**
     * Método para convertir el objeto a un string.
     * @return string Nombre de la clase con que se instancio el objeto.
     */
    public function __toString(): string
    {
        return get_class($this);
    }

    /**
     * Método para setear los atributos de la clase.
     * @param array Arreglo con los datos que se deben asignar.
     */
    public function set(array $array)
    {
        $props = (new \ReflectionClass($this))->getProperties(
            \ReflectionProperty::IS_PUBLIC
        );
        foreach ($props as &$prop) {
            $name = $prop->getName();
            if (isset($array[$name])) {
                $this->$name = $array[$name];
            }
        }
        return $this;
    }

}
