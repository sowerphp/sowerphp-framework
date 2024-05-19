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
namespace sowerphp\app\Sistema\General\DivisionGeopolitica;

/**
 * Clase para mapear la tabla region de la base de datos
 * Comentario de la tabla: Regiones del país
 * Esta clase permite trabajar sobre un registro de la tabla region
 */
class Model_Region extends \Model_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'region'; ///< Tabla del modelo

    // Atributos de la clase (columnas en la base de datos)
    public $codigo; ///< Código de la región: character(2) NOT NULL DEFAULT '' PK
    public $region; ///< Nombre de la región: character varying(60) NOT NULL DEFAULT ''
    public $orden; ///< smallint(16) NOT NULL DEFAULT '0'

    // Información de las columnas de la tabla en la base de datos
    public static $columnsInfo = array(
        'codigo' => array(
            'name'      => 'Codigo',
            'comment'   => 'Código de la región',
            'type'      => 'character',
            'length'    => 2,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => true,
            'fk'        => null
        ),
        'region' => array(
            'name'      => 'Region',
            'comment'   => 'Nombre de la región',
            'type'      => 'character varying',
            'length'    => 60,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'orden' => array(
            'name'      => 'Orden',
            'comment'   => '',
            'type'      => 'smallint',
            'length'    => 16,
            'null'      => false,
            'default'   => '0',
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),

    );

    // Comentario de la tabla en la base de datos
    public static $tableComment = 'Regiones del país';

    public static $fkNamespace = []; ///< Namespaces que utiliza esta clase

}
