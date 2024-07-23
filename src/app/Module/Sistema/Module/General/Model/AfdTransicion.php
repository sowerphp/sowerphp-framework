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

namespace sowerphp\app\Sistema\General;

/**
 * Clase para mapear la tabla afd_transicion de la base de datos
 * Comentario de la tabla:
 * Esta clase permite trabajar sobre un registro de la tabla afd_transicion
 */
class Model_AfdTransicion extends \sowerphp\autoload\Model
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'afd_transicion'; ///< Tabla del modelo

    // Atributos de la clase (columnas en la base de datos)
    public $afd; ///< varchar(10) NOT NULL DEFAULT '' PK FK:afd_estado.afd
    public $desde; ///< int(10) NOT NULL DEFAULT '0' PK FK:afd_estado.codigo
    public $valor; ///< varchar(5) NOT NULL DEFAULT '' PK
    public $hasta; ///< int(10) NOT NULL DEFAULT '' FK:afd_estado.codigo

    // Información de las columnas de la tabla en la base de datos
    public static $columnsInfo = array(
        'afd' => array(
            'name'      => 'Afd',
            'comment'   => '',
            'type'      => 'varchar',
            'length'    => 10,
            'null'      => false,
            'default'   => '',
            'auto'      => false,
            'pk'        => true,
            'fk'        => array('table' => 'afd_estado', 'column' => 'afd')
        ),
        'desde' => array(
            'name'      => 'Desde',
            'comment'   => '',
            'type'      => 'int',
            'length'    => 10,
            'null'      => false,
            'default'   => '0',
            'auto'      => false,
            'pk'        => true,
            'fk'        => array('table' => 'afd_estado', 'column' => 'codigo')
        ),
        'valor' => array(
            'name'      => 'Valor',
            'comment'   => '',
            'type'      => 'varchar',
            'length'    => 5,
            'null'      => false,
            'default'   => '',
            'auto'      => false,
            'pk'        => true,
            'fk'        => null
        ),
        'hasta' => array(
            'name'      => 'Hasta',
            'comment'   => '',
            'type'      => 'int',
            'length'    => 10,
            'null'      => false,
            'default'   => '',
            'auto'      => false,
            'pk'        => false,
            'fk'        => array('table' => 'afd_estado', 'column' => 'codigo')
        ),

    );

    // Comentario de la tabla en la base de datos
    public static $tableComment = '';

    public static $fkNamespace = array(
        'Model_AfdEstado' => 'website\Sistema',
        'Model_AfdEstado' => 'website\Sistema',
        'Model_AfdEstado' => 'website\Sistema'
    ); ///< Namespaces que utiliza esta clase

}
