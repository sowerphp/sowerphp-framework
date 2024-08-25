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

namespace sowerphp\general;

use \sowerphp\core\View_Form;

class View_Form_Contacto extends View_Form
{

    protected static function buildForm(array $options): array
    {
        return [
            'data' => $options['data'] ?? [],
            'files' => $options['files'] ?? [],
            'initial' => $options['initial'] ?? [],
            'attributes' => [
                'id' => 'contactusForm',
                'action' => url('/contacto/send'),
            ],
            'fields' => [
                'name' => [
                    'verbose_name' => __('Nombre'),
                    'required' => true,
                    'max_length' => 255,
                ],
                'email' => [
                    'input_type' => 'email',
                    'verbose_name' => __('Correo electrónico'),
                    'required' => true,
                    'max_length' => 255,
                ],
                'message' => [
                    'widget' => [
                        'name' => 'textarea',
                        'attributes' => [
                            'rows' => 5,
                            'class' => 'form-control summernote',
                            'style' => 'height: auto',
                        ],
                    ],
                    'verbose_name' => __('Mensaje'),
                    'required' => true,
                    'min_length' => 100,
                    'max_length' => 1000,
                ],
            ],
            'layout' => [
                [
                    'icon' => 'fa-regular fa-comments',
                    'footer' => 'Envíanos tu mensaje y te contactaremos lo antes posible.',
                    'rows' => [
                        ['name', 'email'],
                        'message'
                    ],
                ],
            ],
        ];
    }

}
