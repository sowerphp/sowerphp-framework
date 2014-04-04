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
 * @todo Se deben completar los métodos para la clase
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-29
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
     * @version 2014-03-20
     */
    public function __construct ($config)
    {
        // verificar que existe el soporte para MySQL en PHP
        if (!class_exists('\SQLite3')) {
            $this->error ('No se encontró la extensión de PHP para SQLite3');
        }
        // definir configuración para el acceso a la base de datos
        $this->config = array_merge(array(
            'flag' => SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE,
        ), $config);
        // abrir la base de datos
        if (isset($this->config['pass'])) {
            $this->link = new \SQLite3 (
                $this->config['file'], $this->config['flag'], $this->config['pass']
            );
        } else {
            $this->link = new \SQLite3 (
                $this->config['file'], $this->config['flag']
            );
        }
    }

    /**
     * Destructor de la clase
     * 
     * Cierra la conexión con la base de datos.
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-20
     */
    public function __destruct ()
    {
        // si el identificador es un recurso de SQLite3 se cierra
        if (get_class($this->link)=='SQLite3') {
                $this->link->close ();
        }
    }

    /**
     * Realizar consulta en la base de datos
     * @param sql Consulta SQL que se desea realizar
     * @return Resource Identificador de la consulta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-20
     */
    public function query ($sql)
    {
        // verificar que exista una consulta
        if(empty($sql)) {
            $this->error('¡Consulta no puede estar vacía!');
        }
        // realizar consulta
        $queryId = $this->link->query ($sql);
        // si hubo error al realizar la consulta se muestra y termina el
        // script
        if(!$queryId) {
            $this->error(
                $sql."\n".$this->link->lastErrorMsg()
            );
        }
        // retornar identificador de la consulta
        return $queryId;
    }

    /**
     * Obtener una tabla (como arreglo) desde la base de datos
     * @param sql Consulta SQL que se desea realizar
     * @return Array Arreglo bidimensional con la tabla y sus datos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2013-10-22
     */
    public function getTable ($sql)
    {
        // realizar consulta
        $queryId = $this->query($sql);
        // procesar resultado de la consulta
        $table = array();
        while($row = $queryId->fetchArray(SQLITE3_ASSOC)) {
            array_push($table, $row);
        }
        // retornar tabla
        return $table;
    }
    
    /**
     * Obtener una sola fila desde la base de datos
     * @param sql Consulta SQL que se desea realizar
     * @return Array Arreglo unidimensional con la fila
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-20
     */
    public function getRow ($sql)
    {
        $row = $queryId = $this->query($sql)->fetchArray(SQLITE3_ASSOC);
        return is_array($row) ? $row : array();
    }

    /**
     * Obtener una sola columna desde la base de datos
     * @param sql Consulta SQL que se desea realizar
     * @return Array Arreglo unidimensional con la columna
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-20
     */
    public function getCol ($sql)
    {
        // realizar consulta
        $queryId = $this->query($sql);
        // procesar resultado de la consulta
        $cols = array();
        while($row = $queryId->fetchArray(SQLITE3_ASSOC)) {
            array_push($cols, array_pop($row));
        }
        // retornar columnas
        return $cols;
    }

    /**
     * Obtener un solo valor desde la base de datos
     * @param sql Consulta SQL que se desea realizar
     * @return Mixed Valor devuelto
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-20
     */
    public function getValue ($sql)
    {
        $row = $queryId = $this->query($sql)->fetchArray(SQLITE3_ASSOC);
        return is_array($row) ? array_pop($row) : '';
    }

    /**
     * Método que limpia el string recibido para hacer la consulta en la
     * base de datos de forma segura
     * @param string String que se desea limpiar
     * @param trim Indica si se deben o no quitar los espacios
     * @return String String limpiado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-20
     */
    public function sanitize ($string, $trim = true)
    {
        if ($trim)
            $string = trim($string);
        return $this->link->escapeString ($string);
    }

    /**
     * Iniciar transacción
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-20
     */
    public function transaction ()
    {
        $this->query ('BEGIN TRANSACTION');
    }

    /**
     * Confirmar transacción
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2013-10-22
     */
    public function commit ()
    {
        $this->query ('COMMIT');
    }

    /**
     * Cancelar transacción
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2013-10-22
     */
    public function rollback ()
    {
        $this->query ('ROLLBACK');
    }

    /**
     * Ejecutar un procedimiento almacenado
     * @param procedure Procedimiento almacenado que se quiere ejecutar
     * @return Mixed Valor que retorna el procedimeinto almacenado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-20
     */
    public function exec ($procedure)
    {
    }

    /**
     * Obtener una tabla mediante un procedimiento almacenado
     * @param procedure Procedimiento almacenado que se desea ejecutar
     * @return Array Arreglo bidimensional con la tabla y sus datos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-20
     */
    public function getTableFromSP ($procedure)
    {
    }

    /**
     * Obtener una sola fila mediante un procedimiento almacenado
     * @param procedure Procedimiento almacenado que se desea ejecutar
     * @return Array Arreglo unidimensional con la fila
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-20
     */
    public function getRowFromSP ($procedure)
    {
    }

    /**
     * Obtener una sola columna mediante un procedimiento almacenado
     * @param procedure Procedimiento almacenado que se desea ejecutar
     * @return Array Arreglo unidimensional con la columna
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-20
     */
    public function getColFromSP ($procedure)
    {
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
     * Genera filtro para utilizar like en la consulta SQL
     * @param colum Columna por la que se filtrará (se sanitiza)
     * @param value Valor a buscar mediante like (se sanitiza)
     * @return String Filtro utilizando like
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2013-10-22
     */
    public function like ($column, $value)
    {
        return "$colum LIKE '%".$this->sanitize($value)."%'";
    }

    /**
     * Concatena los parámetros pasados al método
     *
     * El método acepta n parámetros, pero dos como mínimo deben ser
     * pasados.
     * @param par1 Parámetro 1 que se quiere concatenar
     * @param par2 Parámetro 2 que se quiere concatenar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-20
     */
    public function concat ($par1, $par2)
    {
        $separators = array(' ', ',', ', ', '-', ' - ', '|');
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
     * @version 2014-03-20
     */
    public function getTables ()
    {
    }

    /**
     * Obtener comentario de una tabla
     * @param table Nombre de la tabla
     * @return String Comentario de la tabla
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-20
     */
    public function getCommentFromTable ($table)
    {
    }

    /**
     * Listado de columnas de una tabla (nombre, tipo, largo máximo, si
     * puede tener un valor nulo y su valor por defecto)
     * @param table Tabla a la que se quiere buscar las columnas
     * @return Array Arreglo con la información de las columnas
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-20
     */
    public function getColsFromTable ($table)
    {
    }

    /**
     * Listado de claves primarias de una tabla
     * @param table Tabla a buscar su o sus claves primarias
     * @return Arreglo con la o las claves primarias
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-20
     */
    public function getPksFromTable ($table)
    {
    }
    
    /**
     * Listado de claves foráneas de una tabla
     * @param table Tabla a buscar su o sus claves foráneas
     * @return Arreglo con la o las claves foráneas
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2013-10-22
     */
    public function getFksFromTable ($table)
    {
    }

    /**
     * Seleccionar una tabla con los nombres de las columnas
     * @param sql Consulta SQL que se desea realizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2013-10-22
     */
    public function getTableWithColsNames ($sql)
    {
        $data = array();
        $keys = array();
        $queryId = $this->query($sql);
        $ncolumnas = $queryId->numColumns ();
        for($i=0; $i<$ncolumnas; ++$i) {
            array_push($keys, $queryId->columnName($i));
        }
        array_push($data, $keys);
        unset($keys);
         while($rows = $queryId->fetchArray(SQLITE3_ASSOC)) {
            $row = array();
            for($i=0; $i<$ncolumnas; ++$i) {
                if(preg_match('/blob/i', $queryId->columnType($i))) // si es un blob no se muestra el contenido en la web
                    array_push($row, '['.$queryId->columnType($i).']');
                else
                    array_push($row, array_shift($rows));
            }
            array_push($data, $row);
        }
        unset($sql, $nfilas, $i, $value, $row);
        return $data;
    }

    /**
     * Exportar una consulta a un archivo CSV y descargar
     *
     * La cantidad de campos seleccionados en la query debe ser igual
     * al largo del arreglo de columnas
     *
     * @param sql Consulta SQL
     * @param file Nombre para el archivo que se descargará
     * @param cols Arreglo con los nombres de las columnas a utilizar en la tabla
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-20
     */
    public function toCSV ($sql, $file, $cols)
    {
    }

}
