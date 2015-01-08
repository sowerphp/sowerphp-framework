<?php

/**
 * SowerPHP: Minimalist Framework for PHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

namespace sowerphp\core;

/**
 * Clase para trabajar con una base de datos SQLite3
 * Se require: php5-sqlite
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-04-20
 */
class Model_Datasource_Database_SQLite extends Model_Datasource_Database_Manager
{

    /**
     * Constructor de la clase
     *
     * Realiza conexión a la base de datos, recibe parámetros para la
     * conexión
     * @param config Arreglo con los parámetros de la conexión
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-20
     */
    public function __construct ($config)
    {
        // verificar que existe el soporte para SQLite en PHP
        if (!class_exists('\SQLite3')) {
            $this->error ('No se encontró la extensión de PHP para SQLite3');
        }
        // definir configuración para el acceso a la base de datos
        $this->config = $config;
        // abrir conexión a la base de datos
        try {
            parent::__construct('sqlite:'.$this->config['file']);
        } catch (\PDOException $e) {
        }
    }

    /**
     * Asigna un límite para la obtención de filas en la consulta SQL
     * @param sql Consulta SQL a la que se le agrega el límite
     * @return String Consulta con el límite agregado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-11-23
     */
    public function setLimit ($sql, $records, $offset = 0)
    {
        return $sql.' LIMIT '.(int)$offset.','.(int)$records;
    }

    /**
     * Concatena los parámetros pasados al método
     *
     * El método acepta n parámetros, pero dos como mínimo deben ser
     * pasados.
     * @param par1 Parámetro 1 que se quiere concatenar
     * @param par2 Parámetro 2 que se quiere concatenar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-01-07
     */
    public function concat($par1, $par2)
    {
        $separators = array(' ', ',', ', ', '-', ' - ', '|', ':', ': ');
        $concat = array();
        $parameters = func_get_args();
        foreach($parameters as &$parameter) {
            if(in_array($parameter, $separators))
                $parameter = "'".$parameter."'";
            array_push($concat, $parameter);
        }
        return implode(' || ', $concat);
    }

}
