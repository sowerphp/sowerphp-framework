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
 * Clase base para todos los widgets de formulario.
 */
abstract class View_Form_Widget
{

    /**
     * Renderiza el widget del campo.
     *
     * @param string $name El nombre del campo.
     * @param mixed $value El valor del campo.
     * @param array $attributes Atributos adicionales para el widget.
     * @return string El HTML renderizado del widget.
     */
    abstract public function render(
        string $name,
        $value,
        array $attributes = []
    ): string;

    /**
     * Construye una cadena de atributos HTML a partir de un array.
     *
     * @param array $attributes Atributos adicionales para el widget.
     * @return string La cadena de atributos HTML.
     */
    protected function buildAttributes(array $attributes): string
    {
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= " {$key}='{$value}'";
        }
        return $attrString;
    }

}
