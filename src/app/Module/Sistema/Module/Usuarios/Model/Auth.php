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

/**
 * Clase para mapear la tabla auth de la base de datos
 * Comentario de la tabla: Permisos de grupos para acceder a recursos
 * Esta clase permite trabajar sobre un registro de la tabla auth
 */
class Model_Auth extends \Model_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'auth'; ///< Tabla del modelo

    public static $fkNamespace = array(
        'Model_Grupo' => 'sowerphp\app\Sistema\Usuarios'
    ); ///< Namespaces que utiliza esta clase

    // Atributos de la clase (columnas en la base de datos)
    public $id; ///< Identificador (serial): integer(32) NOT NULL DEFAULT 'nextval('auth_id_seq'::regclass)' AUTO PK
    public $grupo; ///< Grupo al que se le condede el permiso: integer(32) NOT NULL DEFAULT '' FK:grupo.id
    public $recurso; ///< Recurso al que el grupo tiene acceso: character varying(300) NULL DEFAULT ''

    // Información de las columnas de la tabla en la base de datos
    public static $columnsInfo = array(
        'id' => array(
            'name'      => 'ID',
            'comment'   => 'Identificador (serial)',
            'type'      => 'integer',
            'length'    => 32,
            'null'      => false,
            'default'   => "nextval('auth_id_seq'::regclass)",
            'auto'      => true,
            'pk'        => true,
            'fk'        => null
        ),
        'grupo' => array(
            'name'      => 'Grupo',
            'comment'   => 'Grupo al que se le concede el permiso',
            'type'      => 'integer',
            'length'    => 32,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => false,
            'fk'        => array('table' => 'grupo', 'column' => 'id')
        ),
        'recurso' => array(
            'name'      => 'Recurso',
            'comment'   => 'Recurso al que el grupo tiene acceso',
            'type'      => 'character varying',
            'length'    => 300,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),

    );

    // Comentario de la tabla en la base de datos
    public static $tableComment = 'Permisos de grupos para acceder a recursos';

}
