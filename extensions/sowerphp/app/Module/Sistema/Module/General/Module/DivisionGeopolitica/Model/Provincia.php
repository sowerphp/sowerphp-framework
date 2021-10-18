<?php

/**
 * SowerPHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
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
 * Clase para mapear la tabla provincia de la base de datos
 * Comentario de la tabla: Provincias de cada región del país
 * Esta clase permite trabajar sobre un registro de la tabla provincia
 * @author SowerPHP Code Generator
 * @version 2014-04-26 01:36:28
 */
class Model_Provincia extends \Model_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'provincia'; ///< Tabla del modelo

    // Atributos de la clase (columnas en la base de datos)
    public $codigo; ///< Código de la provincia: character(3) NOT NULL DEFAULT '' PK
    public $provincia; ///< Nombre de la provincia: character varying(30) NOT NULL DEFAULT ''
    public $region; ///< Región a la que pertenece la provincia: character(2) NOT NULL DEFAULT '' FK:region.codigo

    // Información de las columnas de la tabla en la base de datos
    public static $columnsInfo = array(
        'codigo' => array(
            'name'      => 'Codigo',
            'comment'   => 'Código de la provincia',
            'type'      => 'character',
            'length'    => 3,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => true,
            'fk'        => null
        ),
        'provincia' => array(
            'name'      => 'Provincia',
            'comment'   => 'Nombre de la provincia',
            'type'      => 'character varying',
            'length'    => 30,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'region' => array(
            'name'      => 'Region',
            'comment'   => 'Región a la que pertenece la provincia',
            'type'      => 'character',
            'length'    => 2,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => false,
            'fk'        => array('table' => 'region', 'column' => 'codigo')
        ),

    );

    // Comentario de la tabla en la base de datos
    public static $tableComment = 'Provincias de cada región del país';

    public static $fkNamespace = array(
        'Model_Region' => 'sowerphp\app\Sistema\General\DivisionGeopolitica'
    ); ///< Namespaces que utiliza esta clase

}
