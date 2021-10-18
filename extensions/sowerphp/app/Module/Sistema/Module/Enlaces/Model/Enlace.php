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
namespace sowerphp\app\Sistema\Enlaces;

/**
 * Clase para mapear la tabla enlace de la base de datos
 * Comentario de la tabla: Enlaces de la aplicación
 * Esta clase permite trabajar sobre un registro de la tabla enlace
 * @author SowerPHP Code Generator
 * @version 2014-05-04 11:25:25
 */
class Model_Enlace extends \Model_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'enlace'; ///< Tabla del modelo

    // Atributos de la clase (columnas en la base de datos)
    public $id; ///< Identificador (serial): integer(32) NOT NULL DEFAULT 'nextval('enlace_id_seq'::regclass)' AUTO PK
    public $enlace; ///< Nombre del enlace: character varying(50) NOT NULL DEFAULT ''
    public $url; ///< URL (dirección web): character varying(300) NULL DEFAULT ''
    public $categoria; ///< Categoría a la que pertenece el enlace: integer(32) NOT NULL DEFAULT '' FK:enlace_categoria.id

    // Información de las columnas de la tabla en la base de datos
    public static $columnsInfo = array(
        'id' => array(
            'name'      => 'ID',
            'comment'   => 'Identificador (serial)',
            'type'      => 'integer',
            'length'    => 32,
            'null'      => false,
            'default'   => "nextval('enlace_id_seq'::regclass)",
            'auto'      => true,
            'pk'        => true,
            'fk'        => null
        ),
        'enlace' => array(
            'name'      => 'Enlace',
            'comment'   => 'Nombre del enlace',
            'type'      => 'character varying',
            'length'    => 50,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'url' => array(
            'name'      => 'Url',
            'comment'   => 'URL (dirección web)',
            'type'      => 'character varying',
            'length'    => 300,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'categoria' => array(
            'name'      => 'Categoría',
            'comment'   => 'Categoría a la que pertenece el enlace',
            'type'      => 'integer',
            'length'    => 32,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => false,
            'fk'        => array('table' => 'enlace_categoria', 'column' => 'id')
        ),

    );

    // Comentario de la tabla en la base de datos
    public static $tableComment = 'Enlaces de la aplicación';

    public static $fkNamespace = array(
        'Model_EnlaceCategoria' => '\sowerphp\app\Sistema\Enlaces'
    ); ///< Namespaces que utiliza esta clase

}
