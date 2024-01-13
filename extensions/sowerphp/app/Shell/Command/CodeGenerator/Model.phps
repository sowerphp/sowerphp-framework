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
namespace {namespace};

/**
 * Clase para mapear la tabla {table} de la base de datos
 * Comentario de la tabla: {comment}
 * Esta clase permite trabajar sobre un registro de la tabla {table}
 */
class Model_{class} extends \Model_App
{

    // Datos para la conexión a la base de datos
    protected $_database = '{database}'; ///< Base de datos del modelo
    protected $_table = '{table}'; ///< Tabla del modelo

    // Atributos de la clase (columnas en la base de datos)
    {columns}

    // Información de las columnas de la tabla en la base de datos
    public static $columnsInfo = array(
{columnsInfo}
    );

    // Comentario de la tabla en la base de datos
    public static $tableComment = '{comment}';

    public static $fkNamespace = array({fkNamespace}); ///< Namespaces que utiliza esta clase

}
