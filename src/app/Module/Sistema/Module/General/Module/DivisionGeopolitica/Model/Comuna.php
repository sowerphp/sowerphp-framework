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

use \sowerphp\autoload\Model;

/**
 * Modelo singular de la tabla "comuna" de la base de datos.
 *
 * Permite interactuar con un registro de la tabla.
 */
class Model_Comuna extends Model
{

    /**
     * Metadatos del modelo.
     *
     * @var array
     */
    protected $metadata = [
        'model' => [
            'ordering' => ['codigo'],
            'default_permissions' => ['list', 'view'],
            'list_group_by' => 'provincia.region.region',
        ],
        'fields' => [
            'codigo' => [
                'type' => self::TYPE_CHAR,
                'primary_key' => true,
                'length' => 5,
                'verbose_name' => 'Código',
                'help_text' => 'Código asignado por el gobierno de Chile a la comuna.',
            ],
            'comuna' => [
                'type' => self::TYPE_STRING,
                'max_length' => 40,
                'verbose_name' => 'Comuna',
                'help_text' => 'Nombre de la comuna.',
            ],
            'provincia' => [
                'type' => self::TYPE_CHAR,
                'relation' => Model_Provincia::class,
                'belongs_to' => 'provincia',
                'related_field' => 'codigo',
                'length' => 3,
                'verbose_name' => 'Provincia',
                'help_text' => 'Provincia a la que pertenece la comuna.',
                'display' => '(provincia.provincia)',
                'searchable' => 'codigo:string|provincia:string',
            ],
        ],
    ];

}
