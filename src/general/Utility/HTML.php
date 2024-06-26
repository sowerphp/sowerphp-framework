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

namespace sowerphp\general;

/**
 * Clase para trabajar con temas asociados al HTML
 */
class Utility_HTML
{

    /**
     * Método que entrega los tamaños de columnas para ser usados en un grid
     * de bootstrap
     * @param items Cantidad total de items a ubicar en lña grilla
     * @param max Cantida máxima de elementos en la grilla
     */
    public static function getBootstrapCols($items, $max_items = 4, $grid_width = 12)
    {
        $cols = [];
        // llenar filas completas primero
        $filas_completas = floor($items/$max_items);
        for ($i=0; $i<$filas_completas; $i++) {
            for ($j=0; $j<$max_items; $j++) {
                $cols[] = $grid_width / $max_items;
            }
        }
        // agregar ultima fila
        $items_ultima_fila = $items - $filas_completas * $max_items;
        if ($items_ultima_fila) {
            $tam_cols_ultima_fila = $grid_width / $items_ultima_fila;
            for ($i=0; $i<$items_ultima_fila; $i++) {
                $cols[] = $tam_cols_ultima_fila;
            }
        }
        // entregar columnas que se deben usar
        return $cols;
    }

}
