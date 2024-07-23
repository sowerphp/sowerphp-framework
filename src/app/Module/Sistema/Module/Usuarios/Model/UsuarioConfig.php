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
 * Clase para mapear la tabla usuario_config de la base de datos
 * Comentario de la tabla:
 * Esta clase permite trabajar sobre un registro de la tabla usuario_config
 */
class Model_UsuarioConfig extends \sowerphp\autoload\Model
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'usuario_config'; ///< Tabla del modelo

    // Atributos de la clase (columnas en la base de datos)
    public $usuario; ///< integer(32) NOT NULL DEFAULT '' PK FK:usuario.id
    public $configuracion; ///< character varying(32) NOT NULL DEFAULT '' PK
    public $variable; ///< character varying(64) NOT NULL DEFAULT '' PK
    public $valor; ///< text() NULL DEFAULT ''
    public $json; ///< boolean() NOT NULL DEFAULT 'false'

    // Información de las columnas de la tabla en la base de datos
    public static $columnsInfo = array(
        'usuario' => array(
            'name'      => 'Usuario',
            'comment'   => '',
            'type'      => 'integer',
            'length'    => 32,
            'null'      => false,
            'default'   => '',
            'auto'      => false,
            'pk'        => true,
            'fk'        => array('table' => 'usuario', 'column' => 'id')
        ),
        'configuracion' => array(
            'name'      => 'Configuracion',
            'comment'   => '',
            'type'      => 'character varying',
            'length'    => 32,
            'null'      => false,
            'default'   => '',
            'auto'      => false,
            'pk'        => true,
            'fk'        => null
        ),
        'variable' => array(
            'name'      => 'Variable',
            'comment'   => '',
            'type'      => 'character varying',
            'length'    => 64,
            'null'      => false,
            'default'   => '',
            'auto'      => false,
            'pk'        => true,
            'fk'        => null
        ),
        'valor' => array(
            'name'      => 'Valor',
            'comment'   => '',
            'type'      => 'text',
            'length'    => null,
            'null'      => true,
            'default'   => '',
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'json' => array(
            'name'      => 'Json',
            'comment'   => '',
            'type'      => 'boolean',
            'length'    => null,
            'null'      => false,
            'default'   => 'false',
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),

    );

    // Comentario de la tabla en la base de datos
    public static $tableComment = '';

    public static $fkNamespace = array(
        'Model_Usuario' => 'sowerphp\app\Sistema\Usuarios'
    ); ///< Namespaces que utiliza esta clase

}
