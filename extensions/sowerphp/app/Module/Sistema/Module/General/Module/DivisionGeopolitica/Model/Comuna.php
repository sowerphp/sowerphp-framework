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
 * Clase para mapear la tabla comuna de la base de datos
 * Comentario de la tabla: Comunas de cada provincia del país
 * Esta clase permite trabajar sobre un registro de la tabla comuna
 */
class Model_Comuna extends \Model_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'comuna'; ///< Tabla del modelo

    // Atributos de la clase (columnas en la base de datos)
    public $codigo; ///< Código de la comuna: character(5) NOT NULL DEFAULT '' PK
    public $comuna; ///< Nombre de la comuna: character varying(40) NOT NULL DEFAULT ''
    public $provincia; ///< Provincia a la que pertenece la comuna: character(3) NOT NULL DEFAULT '' FK:provincia.codigo

    // Información de las columnas de la tabla en la base de datos
    public static $columnsInfo = array(
        'codigo' => array(
            'name'      => 'Codigo',
            'comment'   => 'Código de la comuna',
            'type'      => 'character',
            'length'    => 5,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => true,
            'fk'        => null
        ),
        'comuna' => array(
            'name'      => 'Comuna',
            'comment'   => 'Nombre de la comuna',
            'type'      => 'character varying',
            'length'    => 40,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'provincia' => array(
            'name'      => 'Provincia',
            'comment'   => 'Provincia a la que pertenece la comuna',
            'type'      => 'character',
            'length'    => 3,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => false,
            'fk'        => array('table' => 'provincia', 'column' => 'codigo')
        ),

    );

    // Comentario de la tabla en la base de datos
    public static $tableComment = 'Comunas de cada provincia del país';

    public static $fkNamespace = array(
        'Model_Provincia' => 'sowerphp\app\Sistema\General\DivisionGeopolitica'
    ); ///< Namespaces que utiliza esta clase

}
