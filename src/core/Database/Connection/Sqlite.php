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
 * Clase personalizada para gestionar conexiones a bases de datos SQLite.
 * Extiende de Database_Connection, adaptando y optimizando la conexión para el
 * manejo eficiente de bases de datos SQLite. Esta clase facilita la
 * implementación de funcionalidades específicas para SQLite.
 */
class Database_Connection_Sqlite extends Database_Connection
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

}
