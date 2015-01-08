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
 * Clase para trabajar con una base de datos MySQL
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-10-02
 */
class Model_Datasource_Database_MySQL extends Model_Datasource_Database_Manager
{

    /**
     * Constructor de la clase
     *
     * Realiza conexión a la base de datos, recibe parámetros para la
     * conexión
     * @param config Arreglo con los parámetros de la conexión
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-02
     */
    public function __construct ($config)
    {
        // definir configuración para el acceso a la base de datos
        $this->config = array_merge(array(
            'host' => 'localhost',
            'port' => '3306',
            'char' => 'utf8',
        ), $config);
        // realizar conexión a la base de datos
        try {
            parent::__construct(
                'mysql:host='.$this->config['host'].
                ';port='.$this->config['port'].
                ';dbname='.$this->config['name'].
                ';charset='.$this->config['char'],
                $this->config['user'],
                $this->config['pass'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_SILENT,
                    \PDO::ATTR_PERSISTENT => true,
                    \PDO::MYSQL_ATTR_COMPRESS => true
                ]
            );
        } catch (\PDOException $e) {
        }
    }

    /**
     * Asigna un límite para la obtención de filas en la consulta SQL
     * @param sql Consulta SQL a la que se le agrega el límite
     * @return String Consulta con el límite agregado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2013-10-22
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
    public function concat ($par1, $par2)
    {
        $separators = array(' ', ',', ', ', '-', ' - ', '|', ':', ': ');
        $concat = array();
        $parameters = func_get_args();
        foreach($parameters as &$parameter) {
            if(in_array($parameter, $separators))
                $parameter = "'".$parameter."'";
            array_push($concat, $parameter);
        }
        return 'CONCAT('.implode(', ', $concat).')';
    }

    /**
     * Listado de tablas de la base de datos
     * @return Array Arreglo con las tablas (nombre y comentario)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-26
     */
    public function getTables ()
    {
        return $this->getTable ('
            SELECT table_name AS name, table_comment AS comment
            FROM information_schema.tables
            WHERE
                table_schema = :database
                AND table_type != \'VIEW\'
            ORDER BY table_name
        ', [':database'=>$this->config['name']]);
    }

    /**
     * Obtener comentario de una tabla
     * @param table Nombre de la tabla
     * @return String Comentario de la tabla
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-26
     */
    public function getCommentFromTable ($table)
    {
        return $this->getValue ('
            SELECT table_comment
            FROM information_schema.tables
            WHERE
                table_schema = :database
                AND table_name = :table
        ', [':database'=>$this->config['name'], ':table'=>$table]);
    }

    /**
     * Listado de columnas de una tabla (nombre, tipo, largo máximo, si
     * puede tener un valor nulo y su valor por defecto)
     * @param table Tabla a la que se quiere buscar las columnas
     * @return Array Arreglo con la información de las columnas
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-22
     */
    public function getColsFromTable ($table)
    {
        return $this->getTable ('
            SELECT
                column_name AS name
                , SUBSTRING_INDEX(column_type, \'(\', 1) AS type
                , IFNULL(character_maximum_length, numeric_precision) AS length
                , IF(STRCMP(is_nullable,"NO"),"YES","NO") AS `null`
                , column_default AS `default`
                , column_comment AS comment
                , extra
            FROM information_schema.columns
            WHERE
                table_schema = :database
                AND table_name = :table
            ORDER BY ordinal_position ASC
        ', [':database'=>$this->config['name'], ':table'=>$table]);
    }

    /**
     * Listado de claves primarias de una tabla
     * @param table Tabla a buscar su o sus claves primarias
     * @return Arreglo con la o las claves primarias
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-26
     */
    public function getPksFromTable ($table)
    {
        return $this->getCol ('
            SELECT column_name
            FROM information_schema.key_column_usage
            WHERE
                constraint_schema = :database
                AND table_name = :table
                AND constraint_name = "PRIMARY"
        ', [':database'=>$this->config['name'], ':table'=>$table]);
    }

    /**
     * Listado de claves foráneas de una tabla
     * @param table Tabla a buscar su o sus claves foráneas
     * @return Arreglo con la o las claves foráneas
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-26
     */
    public function getFksFromTable ($table)
    {
        $fks =  $this->getTable ('
            SELECT
                column_name AS name
                , referenced_table_name AS `table`
                , referenced_column_name AS `column`
            FROM
                information_schema.key_column_usage
            WHERE
                constraint_schema = :database
                AND table_name = :table
                AND constraint_name in (
                    SELECT constraint_name
                    FROM information_schema.table_constraints
                    WHERE
                        constraint_schema = :database
                        AND table_name = :table
                        AND constraint_type = "FOREIGN KEY")
        ', [':database'=>$this->config['name'], ':table'=>$table]);
        return is_array($fks) ? $fks : array();
    }

}
