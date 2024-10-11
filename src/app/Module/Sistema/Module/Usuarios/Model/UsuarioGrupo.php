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

namespace sowerphp\app\Sistema\Usuarios;

use sowerphp\autoload\Model;
use sowerphp\app\Sistema\Usuarios\Model_Usuario;
use sowerphp\app\Sistema\Usuarios\Model_Grupo;

/**
 * Modelo singular de la tabla "usuario_grupo" de la base de datos.
 *
 * Permite interactuar con un registro de la tabla.
 */
class Model_UsuarioGrupo extends Model
{

    /**
     * Metadatos del modelo.
     *
     * @var array
     */
    protected $metadata = [
        'model' => [
            'verbose_name' => 'Relación usuario y grupo',
            'verbose_name_plural' => 'Relación usuarios y grupos',
            'db_table_comment' => 'Relación entre usuarios y los grupos a los que pertenecen.',
            'ordering' => ['grupo', 'usuario'],
            'list_group_by' => 'grupo.grupo',
        ],
        'fields' => [
            'usuario' => [
                'type' => self::TYPE_INTEGER,
                'primary_key' => true,
                'relation' => Model_Usuario::class,
                'belongs_to' => 'usuario',
                'related_field' => 'id',
                'verbose_name' => 'Usuario',
                'help_text' => 'Usuario de la aplicación.',
                'display' => '(usuario.nombre)" ("(usuario.usuario)")"',
                'searchable' => 'id:integer|usuario:string|nombre:string|email:string',
            ],
            'grupo' => [
                'type' => self::TYPE_INTEGER,
                'primary_key' => true,
                'relation' => Model_Grupo::class,
                'belongs_to' => 'grupo',
                'related_field' => 'id',
                'verbose_name' => 'Grupo',
                'help_text' => 'Grupo al que pertenece el usuario.',
                'display' => '(grupo.grupo)',
                'searchable' => 'id:integer|grupo:string',
            ],
            'primario' => [
                'type' => self::TYPE_BOOLEAN,
                'default' => false,
                'verbose_name' => 'Primario',
                'help_text' => 'Indica si el grupo es el grupo primario del usuario.',
            ],
        ],
    ];

}
