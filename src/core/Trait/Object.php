<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
 * Copyright (C) SowerPHP <https://www.sowerphp.org>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero
 * de GNU publicada por la Fundación para el Software Libre, ya sea la
 * versión 3 de la Licencia, o (a su elección) cualquier versión
 * posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU
 * para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General
 * Affero de GNU junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace sowerphp\core;

/**
 * Trait genérico con métodos para trabajar con objetos (instancias) de
 * cualquier tipo.
 */
trait Trait_Object
{

    /**
     * Permite utilizar el objeto (instancia) como string y obtener el nombre
     * de la clase.
     *
     * @return string Nombre de la clase con que se instancio el objeto.
     */
    public function __toString(): string
    {
        return get_class($this);
    }

    /**
     * Asignación de atributos públicos a la instancia mediante un arreglo.
     *
     * @param array $attributes Arreglo con los datos que se deben asignar.
     * @return self Instancia para encadenamiento de llamadas a métodos.
     */
    public function fill(array $attributes): self
    {
        $publicAttributes = array_keys(get_object_vars($this));
        foreach ($attributes as $attribute => $value) {
            if (in_array($attribute, $publicAttributes)) {
                $this->$attribute = $value;
            }
        }
        return $this;
    }

    /**
     * Asignación de atributos públicos a la instancia mediante un arreglo.
     *
     * @param array $attributes Arreglo con los datos que se deben asignar.
     * @return self Instancia para encadenamiento de llamadas a métodos.
     * @deprecated Utilizar fill()
     */
    public function set(array $attributes)
    {
        $props = (new \ReflectionClass($this))->getProperties(
            \ReflectionProperty::IS_PUBLIC
        );
        foreach ($props as &$prop) {
            $name = $prop->getName();
            if (isset($attributes[$name])) {
                $this->$name = $attributes[$name];
            }
        }
        return $this;
    }

}
