<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
 * Copyright (C) SowerPHP <https://www.sowerphp.org>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

// namespace del modelo
namespace sowerphp\app\Sistema\Usuarios;

/**
 * Clase para mapear la tabla usuario_grupo de la base de datos
 * Comentario de la tabla: Relación entre usuarios y los grupos a los que pertenecen
 * Esta clase permite trabajar sobre un registro de la tabla usuario_grupo
 */
class Model_UsuarioGrupo extends \Model_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'usuario_grupo'; ///< Tabla del modelo

    public static $fkNamespace = array(
        'Model_Usuario' => 'sowerphp\app\Sistema\Usuarios',
        'Model_Grupo' => 'sowerphp\app\Sistema\Usuarios'
    ); ///< Namespaces que utiliza esta clase

    // Atributos de la clase (columnas en la base de datos)
    public $usuario; ///< Usuario de la aplicación: integer(32) NOT NULL DEFAULT '' PK FK:usuario.id
    public $grupo; ///< Grupo al que pertenece el usuario: integer(32) NOT NULL DEFAULT '' PK FK:grupo.id
    public $primario; ///< Indica si el grupo es el grupo primario del usuario: boolean() NOT NULL DEFAULT 'false'

    // Información de las columnas de la tabla en la base de datos
    public static $columnsInfo = array(
        'usuario' => array(
            'name'      => 'Usuario',
            'comment'   => 'Usuario de la aplicación',
            'type'      => 'integer',
            'length'    => 32,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => true,
            'fk'        => array('table' => 'usuario', 'column' => 'id')
        ),
        'grupo' => array(
            'name'      => 'Grupo',
            'comment'   => 'Grupo al que pertenece el usuario',
            'type'      => 'integer',
            'length'    => 32,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => true,
            'fk'        => array('table' => 'grupo', 'column' => 'id')
        ),
        'primario' => array(
            'name'      => 'Primario',
            'comment'   => 'Indica si el grupo es el grupo primario del usuario',
            'type'      => 'boolean',
            'length'    => null,
            'null'      => false,
            'default'   => "false",
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),

    );

    // Comentario de la tabla en la base de datos
    public static $tableComment = 'Relación entre usuarios y los grupos a los que pertenecen';

}
