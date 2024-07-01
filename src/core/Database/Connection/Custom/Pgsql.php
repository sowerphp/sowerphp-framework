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

namespace sowerphp\core;

/**
 * Clase personalizada para gestionar conexiones a bases de datos PostgreSQL.
 * Extiende de Database_Connection_Custom para incluir funcionalidades
 * específicas de PostgreSQL, optimizando las interacciones y operaciones
 * específicas de este motor de base de datos.
 */
class Database_Connection_Custom_Pgsql extends Database_Connection_Custom
{

    /**
     * Asigna un límite para la obtención de filas en la consulta SQL.
     *
     * @param string $query Consulta SQL a la que se agregará el límite.
     * @param int $records Registros que se desean obtener.
     * @param int $offset Registro desde donde iniciar el límite.
     * @return string Consulta con el límite agregado.
     */
    public function setLimit(string $query, int $records, int $offset = 0): string
    {
        return $query . ' LIMIT ' . $records . ' OFFSET ' . $offset;
    }

    /**
     * Entrega el string SQL de una fecha en cierto formato.
     *
     * Se puede entregar a partir de cierta fecha y hora o bien con la fecha y
     * hora actual.
     */
    public function date(string $format, $datetime = null, $cast = null): string
    {
        if (!$datetime) {
            $datetime = 'NOW()';
        }
        $formats = [
            'Ym' => 'YYYYmm',
            'Y' => 'YYYY',
            'm' => 'mm',
            'd' => 'DD',
        ];
        return 'TO_CHAR(' . $datetime .', \'' . $formats[$format] . '\')'
            . ($cast ? ('::' . $cast) : '')
        ;
    }

    /**
     * Extrae un valor desde un nodo de un XML almacenado en una columna de la
     * base de datos.
     *
     * Este método es por compatibilidad, aquellas bases de datos que no
     * soportan este método entregarán NULL para cada PATH solicitado.
     *
     * @return array|string
     */
    public function xml(
        string $column,
        $path,
        $namespace = null,
        bool $trim =
        true,
        $data_format = 'base64_ISO8859-1'
    )
    {
        if (!is_array($path)) {
            $path = [$path];
        }
        $select = [];
        if ($data_format=='base64_ISO8859-1') {
            $column = 'CONVERT_FROM(DECODE('.$column.', \'base64\'), \'ISO8859-1\')::XML';
        }
        else if ($data_format=='ISO8859-1') {
            $column = 'CONVERT_FROM('.$column.', \'ISO8859-1\')::XML';
        }
        else if ($data_format=='base64') {
            $column = 'DECODE('.$column.', \'base64\')::XML';
        }
        else {
            $column = $column.'::XML';
        }
        foreach ($path as $k => $p) {
            if ($namespace) {
                $p = str_replace('|', '/text()|', str_replace('/', '/n:', $p)).'/text()';
                if (strpos($p, '/n:@')) {
                    $p = str_replace(['/n:@', '/text()'], ['/@', ''], $p);
                }
                $select[$k] = 'XPATH(\''.$p.'\', '.$column.', \'{{n,'.$namespace.'}}\')::TEXT';
                if ($trim) {
                    $select[$k] = 'BTRIM('.$select[$k].', \'{"}\')';
                }
            } else {
                $p = str_replace('|', '/text()|', $p).'/text()';
                if (strpos($p, '/@')) {
                    $p = str_replace('/text()', '', $p);
                }
                $select[$k] = 'XPATH(\''.$p.'\', '.$column.')::TEXT';
                if ($trim) {
                    $select[$k] = 'BTRIM('.$select[$k].', \'{}\')';
                }
            }
        }
        return count($select) > 1 ? $select : array_shift($select);
    }

    /**
     * Listado de tablas de la base de datos.
     *
     * @return array Arreglo con las tablas (su nombre y comentario).
     */
    public function getTablesFromDatabase(): array
    {
        // Obtener solo tablas del esquema indicado de la base de datos.
        $tables = $this->getTable('
            SELECT t.table_name AS name
            FROM information_schema.tables AS t
            WHERE
                t.table_catalog = :database
                AND t.table_schema = :schema
                AND t.table_type = \'BASE TABLE\'
            ORDER BY t.table_name
        ', [
            ':database' => $this->getConfig()['database'],
            ':schema' => $this->getConfig()['schema'],
        ]);
        // Buscar comentarios de las tablas.
        foreach($tables as &$table) {
            $table['comment'] = $this->getCommentFromTable($table['name']);
        }
        // Retornar tablas.
        return $tables;
    }

    /**
     * Obtener comentario de una tabla.
     *
     * @param string $table Nombre de la tabla.
     * @return string Comentario de la tabla.
     */
    public function getCommentFromTable(string $table): string
    {
        return $this->getValue('
            SELECT d.description
            FROM
                information_schema.tables AS t,
                pg_catalog.pg_description AS d,
                pg_catalog.pg_class AS c
            WHERE
                t.table_catalog = :database
                AND t.table_schema = :schema
                AND c.relname = :table
                AND d.objoid = c.oid
                AND d.objsubid = 0
        ', [
            ':database' => $this->getConfig()['database'],
            ':schema' => $this->getConfig()['schema'],
            ':table' => $table,
        ]);
    }

    /**
     * Listado de columnas de una tabla (nombre, tipo, largo máximo, si
     * puede tener un valor nulo y su valor por defecto).
     *
     * @param string $table Tabla a la que se quiere buscar las columnas.
     * @return array Arreglo con la información de las columnas.
     */
    public function getColsFromTable(string $table): array
    {
        // Buscar las columnas de la tabla.
        $cols = $this->getTable('
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
        ', [
            ':database' => $this->getConfig()['database'],
            ':schema' => $this->getConfig()['schema'],
            ':table' => $table,
        ]);
        // Buscar los comentarios para las columnas.
        foreach($cols as &$col) {
            $col['comment'] = $this->getValue('
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
            ', [
                ':database' => $this->getConfig()['database'],
                ':schema' => $this->getConfig()['schema'],
                ':table' => $table,
                ':colname' => $col['name'],
            ]);
        }
        // Retornar columnas de la tabla.
        return $cols;
    }

    /**
     * Listado de claves primarias de una tabla.
     *
     * @param string $table Tabla a buscar su claves primarias.
     * @return array Arreglo con las claves primarias.
     */
    public function getPksFromTable(string $table): array
    {
        $database = $this->getConfig()['database'];
        $schema = $this->getConfig()['schema'];
        return $this->getCol('
            SELECT
                column_name
            FROM
                information_schema.constraint_column_usage
            WHERE
                constraint_name = (
                    SELECT relname
                    FROM pg_class
                    WHERE oid = (
                        SELECT indexrelid
                        FROM pg_index, pg_class, pg_namespace
                        WHERE
                            pg_class.relname = :table
                            AND pg_namespace.nspname = :schema
                            AND pg_namespace.oid = pg_class.relnamespace
                            AND pg_class.oid = pg_index.indrelid
                            AND indisprimary = \'t\'
                    )
                )
                AND table_catalog = :database
                AND table_name = :table
        ', [
            ':database' => $database,
            ':schema' => $schema,
            ':table' => $table,
        ]);
    }

    /**
     * Listado de claves foráneas de una tabla.
     *
     * @param string $table Tabla a buscar sus claves foráneas.
     * @return array Arreglo con las claves foráneas.
     */
    public function getFksFromTable(string $table): array
    {
        $fks = $this->getTable('
            SELECT
                kcu.column_name AS name,
                ccu.table_name AS table,
                ccu.column_name AS column
            FROM
                information_schema.constraint_column_usage as ccu,
                information_schema.key_column_usage as kcu
            WHERE
                ccu.table_catalog = :database
                AND ccu.constraint_name = kcu.constraint_name
                AND ccu.constraint_name IN (
                    SELECT
                        constraint_name
                    FROM
                        information_schema.table_constraints
                    WHERE
                        table_name = :table
                        AND constraint_schema = :schema
                        AND constraint_type = \'FOREIGN KEY\'
                )
        ', [
            ':database' => $this->getConfig()['database'],
            ':schema' => $this->getConfig()['schema'],
            ':table' => $table,
        ]);
        return is_array($fks) ? $fks : [];
    }

}
