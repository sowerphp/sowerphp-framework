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
 * Clase para trabajar con una base de datos PostgreSQL
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-10-02
 */
class Model_Datasource_Database_PostgreSQL extends Model_Datasource_Database_Manager
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
        // definir configuración para el acceso a la base de datos
        $this->config = array_merge(array(
            'host' => 'localhost',
            'port' => '5432',
            'char' => 'utf8',
            'sche' => 'public',
        ), $config);
        // abrir conexión a la base de datos
        try {
            parent::__construct(
                'pgsql:host='.$this->config['host'].
                ';port='.$this->config['port'].
                ';dbname='.$this->config['name'],
                $this->config['user'],
                $this->config['pass']
            );
        } catch (\PDOException $e) {
            return;
        }
        // definir encoding a utilizar con la base de datos
        $this->query('SET CLIENT_ENCODING TO \''.$this->config['char'].'\'');
        // definir esquema que se utilizará (solo si es diferente a public)
        if ($this->config['sche'] != 'public') {
            $this->query(
                'SET SEARCH_PATH TO '.$this->config['sche']
            );
        }
    }

    /**
     * Asigna un límite para la obtención de filas en la consulta SQL
     * @param sql Consulta SQL a la que se le agrega el límite
     * @return String Consulta con el límite agregado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-09-09
     */
    public function setLimit ($sql, $records, $offset = 0)
    {
        return $sql.' LIMIT '.(int)$records.' OFFSET '.(int)$offset;
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
        return implode(' || ', $concat);
    }

    /**
     * Listado de tablas de la base de datos
     * @return Array Arreglo con las tablas (nombre y comentario)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-26
     */
    public function getTables ()
    {
        // obtener solo tablas del esquema indicado de la base de datos
        $tables = $this->getTable("
            SELECT t.table_name AS name
            FROM information_schema.tables AS t
            WHERE
                t.table_catalog = :database
                AND t.table_schema = :schema
                AND t.table_type = 'BASE TABLE'
            ORDER BY t.table_name
        ", [':database'=>$this->config['name'], ':schema'=>$this->config['sche']]);
        // buscar comentarios de las tablas
        foreach($tables as &$table) {
            $table['comment'] = $this->getCommentFromTable($table['name']);
        }
        // retornar tablas
        return $tables;
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
        return $this->getValue("
            SELECT d.description
            FROM information_schema.tables AS t, pg_catalog.pg_description AS d, pg_catalog.pg_class AS c
            WHERE
                t.table_catalog = :database
                AND t.table_schema = :schema
                AND c.relname = :table
                AND d.objoid = c.oid
                AND d.objsubid = 0
        ", [':database'=>$this->config['name'], ':schema'=>$this->config['sche'], ':table'=>$table]);
    }

    /**
     * Listado de columnas de una tabla (nombre, tipo, largo máximo, si
     * puede tener un valor nulo y su valor por defecto)
     * @param table Tabla a la que se quiere buscar las columnas
     * @return Array Arreglo con la información de las columnas
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-26
     */
    public function getColsFromTable ($table)
    {
        // buscar columnas
        $cols = $this->getTable("
            SELECT
                c.column_name AS name
                , data_type as type
                , (CASE (SELECT c.character_maximum_length is null)
                    WHEN true THEN c.numeric_precision
                    ELSE c.character_maximum_length
                END) AS length
                , c.is_nullable AS null
                , c.column_default AS default
            FROM
                information_schema.columns as c
                , pg_class as t
                , pg_namespace
            WHERE
                c.table_catalog = :database
                AND c.table_name = :table
                AND c.table_schema = :schema
                AND t.relname = :table
                AND pg_namespace.nspname = :schema
                AND pg_namespace.oid = t.relnamespace
            ORDER BY c.ordinal_position ASC
        ", [':database'=>$this->config['name'], ':schema'=>$this->config['sche'], ':table'=>$table]);
        // buscar comentarios para las columnas
        foreach($cols as &$col) {
            $col['comment'] = $this->getValue("
                SELECT
                    d.description
                FROM
                    information_schema.columns as c
                    , pg_description as d
                    , pg_class as t
                    , pg_namespace
                WHERE
                    c.table_catalog = :database
                    AND c.table_name = :table
                    AND c.column_name = :colname
                    AND t.relname = :table
                    AND pg_namespace.nspname = :schema
                    AND pg_namespace.oid = t.relnamespace
                    AND d.objoid = t.oid
                    AND d.objsubid = c.ordinal_position
            ", [':database'=>$this->config['name'], ':schema'=>$this->config['sche'], ':table'=>$table, ':colname'=>$col['name']]);
        }
        // retornar columnas
        return $cols;
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
        return $this->getCol("
            SELECT column_name
            FROM information_schema.constraint_column_usage
            WHERE constraint_name = (
                SELECT relname
                FROM pg_class
                WHERE oid = (
                    SELECT indexrelid
                    FROM pg_index, pg_class, pg_namespace
                    WHERE
                        pg_class.relname = :table
                        AND  pg_namespace.nspname = :schema
                        AND pg_namespace.oid = pg_class.relnamespace
                        AND pg_class.oid = pg_index.indrelid
                        AND indisprimary = 't'
                )
            ) AND table_catalog = :database AND table_name = :table
        ", [':database'=>$this->config['name'], ':schema'=>$this->config['sche'], ':table'=>$table]);
    }

    /**
     * Listado de claves foráneas de una tabla
     * @param table Tabla a buscar su o sus claves foráneas
     * @return Arreglo con la o las claves foráneas
     * @todo Claves foráneas de múltiples columnas dan problemas, constraint entre esquemas
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-26
     */
    public function getFksFromTable ($table)
    {
        $fks = $this->getTable("
            SELECT
                kcu.column_name AS name
                , ccu.table_name AS table
                , ccu.column_name AS column
            FROM information_schema.constraint_column_usage as ccu, information_schema.key_column_usage as kcu
            WHERE
                ccu.table_catalog = :database
                AND ccu.constraint_name = kcu.constraint_name
                AND ccu.constraint_name IN (
                    SELECT constraint_name
                    FROM information_schema.table_constraints
                    WHERE
                        table_name = :table
                        AND constraint_schema = :schema
                        AND constraint_type = 'FOREIGN KEY'
                )
        ", [':database'=>$this->config['name'], ':schema'=>$this->config['sche'], ':table'=>$table]);
        return is_array($fks) ? $fks : array();
    }

}
