<?php

/**
 * SowerPHP: Simple and Open Web Ecosystem Reimagined for PHP.
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
 * Clase personalizada para gestionar conexiones a bases de datos MySQL.
 * Extiende de Database_Connection para aplicar configuraciones y
 * optimizaciones específicas del motor MySQL. Permite la personalización de la
 * conexión y ejecución de comandos específicos de MySQL.
 */
class Database_Connection_Mysql extends Database_Connection
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
        return $query . ' LIMIT ' . $offset . ',' . $records;
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
            'Ym' => '%Y%m',
            'Y' => '%Y',
            'm' => '%m',
            'd' => '%e',
        ];
        return 'DATE_FORMAT(' . $datetime . ', "' . $formats[$format] . '")';
    }

    /**
     * Listado de tablas de la base de datos.
     *
     * @return array Arreglo con las tablas (su nombre y comentario).
     */
    public function getTablesFromDatabase(): array
    {
        return $this->getTable ('
            SELECT
                table_name AS name,
                table_comment AS comment
            FROM information_schema.tables
            WHERE
                table_schema = :database
                AND table_type != \'VIEW\'
            ORDER BY table_name
        ', [
            ':database' => $this->getConfig()['database'],
        ]);
    }

    /**
     * Obtener comentario de una tabla.
     *
     * @param string $table Nombre de la tabla.
     * @return string Comentario de la tabla.
     */
    public function getCommentFromTable(string $table): string
    {
        return $this->getValue ('
            SELECT
                table_comment
            FROM
                information_schema.tables
            WHERE
                table_schema = :database
                AND table_name = :table
        ', [
            ':database' => $this->getConfig()['database'],
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
        return $this->getTable ('
            SELECT
                column_name AS name,
                SUBSTRING_INDEX(column_type, \'(\', 1) AS type,
                IFNULL(character_maximum_length, numeric_precision) AS length,
                IF(STRCMP(is_nullable, "NO"), "YES", "NO") AS `null`,
                column_default AS `default`,
                column_comment AS comment,
                extra
            FROM
                information_schema.columns
            WHERE
                table_schema = :database
                AND table_name = :table
            ORDER BY
                ordinal_position ASC
        ', [
            ':database' => $this->getConfig()['database'],
            ':table' => $table,
        ]);
    }

    /**
     * Listado de claves primarias de una tabla.
     *
     * @param string $table Tabla a buscar su claves primarias.
     * @return array Arreglo con las claves primarias.
     */
    public function getPksFromTable(string $table): array
    {
        return $this->getCol ('
            SELECT
                column_name
            FROM
                information_schema.key_column_usage
            WHERE
                constraint_schema = :database
                AND table_name = :table
                AND constraint_name = "PRIMARY"
        ', [
            ':database' => $this->getConfig()['database'],
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
        $fks =  $this->getTable ('
            SELECT
                column_name AS name,
                referenced_table_name AS `table`,
                referenced_column_name AS `column`
            FROM
                information_schema.key_column_usage
            WHERE
                constraint_schema = :database
                AND table_name = :table
                AND constraint_name in (
                    SELECT
                        constraint_name
                    FROM
                        information_schema.table_constraints
                    WHERE
                        constraint_schema = :database
                        AND table_name = :table
                        AND constraint_type = "FOREIGN KEY"
                )
        ', [
            ':database' => $this->getConfig()['database'],
            ':table' => $table,
        ]);
        return is_array($fks) ? $fks : [];
    }

}
