<?php

/**
 * SowerPHP: Simple and Open Web Ecosystem Reimagined for PHP.
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
 * Formulario de un modelo de base de datos.
 */
class View_Form_Model extends View_Form
{
    /**
     * Entrega los datos que se utilizarán para la construcción de la instancia
     * del formulario del modelo a partir de las opciones pasadas al método.
     *
     * Este método estandariza las opciones del modelo apra que se puedan usar
     * en una instancia de formulario.
     *
     * @param array $options Opciones para construir el formulario.
     * @return array
     */
    public static function buildForm(array $options): array
    {
        // Determinar los campos del formulario, instancias de View_Form_Field.
        $fields = [];
        foreach ($options['fields'] as $name => $config) {
            $field = new View_Form_Field($config);
            $fields[$name] = $field;
        }

        // Obtener los valores iniciales de los campos (desde cada campo).
        $initial = array_filter(
            array_map(
                function ($field) {
                    return $field->initial;
                },
                $fields
            ),
            function ($initial) {
                return $initial !== null;
            }
        );

        // Entregar los datos normalizados que se deben usar para construir el
        // formulario del modelo.
        return [
            'data' => $options['form']['data'] ?? [],
            'files' => $options['form']['files'] ?? [],
            'initial' => $initial,
            'attributes' => $options['form']['attributes'] ?? [],
            'fields' => $fields,
            'submit_button' => $options['form']['submit_button'] ?? [],
            'layout' => $options['form']['layout']
                ?? $options['model']['layout']
                ?? null
            ,
        ];
    }
}
