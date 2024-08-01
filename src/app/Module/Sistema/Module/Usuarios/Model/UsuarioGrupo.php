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
use \sowerphp\app\Sistema\Usuarios\Model_Usuario;
use \sowerphp\app\Sistema\Usuarios\Model_Grupo;

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
    protected $meta = [
        'model' => [
            'db_table_comment' => 'Relación entre usuarios y los grupos a los que pertenecen',
        ],
        'fields' => [
            'usuario' => [
                'type' => self::TYPE_INTEGER,
                'primary_key' => true,
                'foreign_key' => Model_Usuario::class,
                'to_table' => 'usuario',
                'to_field' => 'id',
                'max_length' => 32,
                'verbose_name' => 'Usuario',
                'help_text' => 'Usuario de la aplicación',
            ],
            'grupo' => [
                'type' => self::TYPE_INTEGER,
                'primary_key' => true,
                'foreign_key' => Model_Grupo::class,
                'to_table' => 'grupo',
                'to_field' => 'id',
                'max_length' => 32,
                'verbose_name' => 'Grupo',
                'help_text' => 'Grupo al que pertenece el usuario',
            ],
            'primario' => [
                'type' => self::TYPE_BOOLEAN,
                'default' => "false",
                'verbose_name' => 'Primario',
                'help_text' => 'Indica si el grupo es el grupo primario del usuario',
            ],
        ],
    ];

}
