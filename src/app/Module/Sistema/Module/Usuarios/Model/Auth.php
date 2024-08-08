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

namespace sowerphp\app\Sistema\Usuarios;

use \sowerphp\autoload\Model;
use sowerphp\app\Sistema\Usuarios\Model_Grupo;

/**
 * Modelo singular de la tabla "auth" de la base de datos.
 *
 * Permite interactuar con un registro de la tabla.
 */
class Model_Auth extends Model
{
    /**
     * Metadatos del modelo.
     *
     * @var array
     */
    protected $meta = [
        'model' => [
            'verbose_name' => 'Permiso de grupo',
            'verbose_name_plural' => 'Permisos de grupos',
            'db_table_comment' => 'Permisos de grupos para acceder a recursos.',
            'ordering' => ['id'],
        ],
        'fields' => [
            'id' => [
                'type' => self::TYPE_INCREMENTS,
                'verbose_name' => 'ID',
                'help_text' => 'Identificador (serial).',
            ],
            'grupo' => [
                'type' => self::TYPE_INTEGER,
                'foreign_key' => Model_Grupo::class,
                'to_table' => 'grupo',
                'to_field' => 'id',
                'verbose_name' => 'Grupo',
                'help_text' => 'Grupo al que se le concede el permiso.',
                'display' => '(grupo.grupo)',
            ],
            'recurso' => [
                'type' => self::TYPE_STRING,
                'max_length' => 300,
                'verbose_name' => 'Recurso',
                'help_text' => 'Recurso al que el grupo tiene acceso.',
            ],
        ],
    ];

}
