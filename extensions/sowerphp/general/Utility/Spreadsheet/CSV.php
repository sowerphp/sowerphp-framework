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

namespace sowerphp\general;

/**
 * Manejar archivos csv
 *
 * Esta clase permite leer y generar archivos csv
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2019-07-18
 */
final class Utility_Spreadsheet_CSV
{

    /**
     * Lee un archivo CSV
     * @param archivo archivo a leer (ejemplo índice tmp_name de un arreglo $_FILES)
     * @param delimiter separador a utilizar para diferenciar entre una columna u otra
     * @param enclosure Un caracter para rodear el dato
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-05-29
     */
    public static function read($archivo = null, $delimiter = null, $enclosure = '"')
    {
        $delimiter = self::setDelimiter($delimiter);
        if (($handle = fopen($archivo, 'r')) !== FALSE) {
            $data = array();
            $i = 0;
            while (($row = fgetcsv($handle, 0, $delimiter, $enclosure)) !== FALSE) {
                $j = 0;
                foreach ($row as &$col) {
                    $data[$i][$j++] = $col;
                }
                ++$i;
            }
            fclose($handle);
        }
        return $data;
    }

    /**
     * Crea un archivo CSV a partir de un arreglo entregándolo vía HTTP
     * @param data Arreglo utilizado para generar la planilla
     * @param id Identificador de la planilla
     * @param delimiter separador a utilizar para diferenciar entre una columna u otra
     * @param enclosure Un caracter para rodear el dato
     * @param size_mib Tamaño máximo del archivo temporal en memoria que se usará (si excede, se escribe archivo real en sistema de archivos)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-07-18
     */
    public static function generate($data, $id, $delimiter = null, $enclosure = '"', $extension = 'csv', $size_mib = 2)
    {
        $csv = self::get($data, $delimiter, $enclosure, $size_mib);
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename='.$id.'.'.$extension);
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $csv;
        exit(0);
    }

    /**
     * Crea un archivo CSV a partir de un arreglo retornando su contenido
     * @param data Arreglo utilizado para generar la planilla
     * @param delimiter separador a utilizar para diferenciar entre una columna u otra
     * @param enclosure Un caracter para rodear el dato
     * @param size_mib Tamaño máximo del archivo temporal en memoria que se usará (si excede, se escribe archivo real en sistema de archivos)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-07-18
     */
    public static function get($data, $delimiter = null, $enclosure = '"', $size_mib = 2)
    {
        $fd = self::save($data, 'php://temp/maxmemory:'.(string)($size_mib*1024*2014), $delimiter, $enclosure, false);
        rewind($fd);
        $csv = stream_get_contents($fd);
        fclose($fd);
        return $csv;
    }

    /**
     * Crea un archivo CSV a partir de un arreglo guardándolo en el sistema de archivos
     * @param data Arreglo utilizado para generar la planilla
     * @param archivo Nombre del archivo que se debe generar
     * @param delimiter separador a utilizar para diferenciar entre una columna u otra
     * @param enclosure Un caracter para rodear el dato
     * @param close =false permite obtener el descriptor de archivo para ser usado en otro lado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-07-18
     */
    public static function save($data, $archivo, $delimiter = null, $enclosure = '"', $close = true)
    {
        ob_clean();
        $delimiter = self::setDelimiter($delimiter);
        $fd = fopen($archivo, 'w');
        if ($fd === false) {
            throw new \Exception('No fue posible crear el archivo CSV');
        }
        foreach($data as &$row) {
            foreach($row as &$col) {
                $col = rtrim(str_replace(['<br />', '<br/>', '<br>'], ', ', strip_tags($col, '<br>')), " \t\n\r\0\x0B,");
            }
            fputcsv($fd, $row, $delimiter, $enclosure);
            unset($row);
        }
        if ($close) {
            fclose($fd);
        } else {
            return $fd;
        }
    }

    /**
     * Método que determina el delimitador que se deberá usar para trabajar con
     * el archivo CSV
     * @param delimiter Delimitador en caso que se quiera tratar de forzar uno
     * @return Delimitador que se debe usar, podría ser: el forzado, el configurado en la APP o el por defecto (',')
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-05-29
     */
    private static function setDelimiter($delimiter = null)
    {
        if ($delimiter !== null) {
            return $delimiter;
        }
        $delimiter = \sowerphp\core\Configure::read('spreadsheet.csv.delimiter');
        return $delimiter ? $delimiter : ',';
    }

}
