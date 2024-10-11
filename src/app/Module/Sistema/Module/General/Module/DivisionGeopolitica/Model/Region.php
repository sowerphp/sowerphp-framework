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

namespace sowerphp\app\Sistema\General\DivisionGeopolitica;

use sowerphp\autoload\Model;

/**
 * Modelo singular de la tabla "region" de la base de datos.
 *
 * Permite interactuar con un registro de la tabla.
 */
class Model_Region extends Model
{

    /**
     * Metadatos del modelo.
     *
     * @var array
     */
    protected $metadata = [
        'model' => [
            'ordering' => ['orden'],
            'default_permissions' => ['list', 'view'],
        ],
        'fields' => [
            'codigo' => [
                'type' => self::TYPE_STRING,
                'primary_key' => true,
                'length' => 2,
                'verbose_name' => 'Código',
                'help_text' => 'Código asignado por el gobierno de Chile a la región.',
            ],
            'region' => [
                'type' => self::TYPE_STRING,
                'max_length' => 60,
                'verbose_name' => 'Región',
                'help_text' => 'Nombre de la región.',
            ],
            'orden' => [
                'type' => self::TYPE_SMALL_INTEGER,
                'verbose_name' => 'Orden',
                'help_text' => 'Orden en el que se deben listar las regiones.',
            ],
        ],
        'relations' => [
            'provincias' => [
                'relation' => Model_Provincia::class,
                'has_many' => 'provincia',
                'related_field' => [
                    'codigo' => 'region',
                ],

            ],
        ]
    ];

}
