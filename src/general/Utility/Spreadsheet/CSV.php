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

namespace sowerphp\general;

/**
 * Esta clase permite leer y generar archivos CSV.
 */
final class Utility_Spreadsheet_CSV
{

    /**
     * Lee un archivo CSV.
     *
     * @param string $archivo Archivo a leer (ejemplo índice tmp_name de un
     * arreglo $_FILES).
     * @param string|null $delimiter Separador a utilizar para diferenciar
     * entre una columna u otra.
     * @param string $enclosure Un caracter para rodear el dato.
     */
    public static function read(
        string $archivo,
        ?string $delimiter = null,
        string $enclosure = '"'
    ): array
    {
        $delimiter = self::setDelimiter($delimiter);
        if (($handle = fopen($archivo, 'r')) !== false) {
            $data = [];
            $i = 0;
            while (($row = fgetcsv($handle, 0, $delimiter, $enclosure)) !== false) {
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
     * Crea un archivo CSV a partir de un arreglo entregándolo vía HTTP.
     *
     * @param array $data Arreglo utilizado para generar la planilla.
     * @param string $id Identificador de la planilla.
     * @param string|null $delimiter separador a utilizar para diferenciar
     * entre una columna u otra.
     * @param string $enclosure Un caracter para rodear el dato
     * @param string $extension Extensión del archivo que se generará.
     * @param int $size_mib Tamaño máximo del archivo temporal en memoria que
     * se usará. Si excede, se escribe archivo real en sistema de archivos.
     * @return void
     */
    public static function generate(
        array $data,
        string $id,
        ?string $delimiter = null,
        string $enclosure = '"',
        string $extension = 'csv',
        int $size_mib = 2
    ): void
    {
        $csv = self::get($data, $delimiter, $enclosure, $size_mib);
        $filename = $id . '.' . $extension;
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $csv;
        exit(0);
    }

    /**
     * Crea un archivo CSV a partir de un arreglo retornando su contenido.
     *
     * @param array $data Arreglo utilizado para generar la planilla.
     * @param string|null $delimiter separador a utilizar para diferenciar
     * entre una columna u otra.
     * @param string $enclosure Un caracter para rodear el dato
     * @param int $size_mib Tamaño máximo del archivo temporal en memoria que
     * se usará. Si excede, se escribe archivo real en sistema de archivos.
     * @return string Contenido del archivo CSV que se creó.
     */
    public static function get(
        array $data,
        ?string $delimiter = null,
        string $enclosure = '"',
        int $size_mib = 2
    ): string
    {
        $fd = self::save(
            $data,
            'php://temp/maxmemory:' . (string)($size_mib*1024*2014),
            $delimiter,
            $enclosure,
            false
        );
        rewind($fd);
        $csv = stream_get_contents($fd);
        fclose($fd);
        return $csv;
    }

    /**
     * Crea un archivo CSV a partir de un arreglo guardándolo en el sistema de
     * archivos. Opcionalmente, si $archivo es 'php://temp/maxmemory' se creará
     * el archivo CSV en memoria en vez de en el sistema real de archivos.
     *
     * @param array $data Arreglo utilizado para generar la planilla.
     * @param string $archivo Nombre del archivo que se debe generar.
     * @param string|null $delimiter Separador a utilizar para diferenciar
     * entre una columna u otra.
     * @param string $enclosure Un caracter para rodear el dato.
     * @param bool $close =false permite obtener el descriptor de archivo para
     * ser usado en otro lado.
     */
    public static function save(
        array $data,
        string $archivo,
        ?string $delimiter = null,
        string $enclosure = '"',
        bool $close = true
    )
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
     * el archivo CSV.
     *
     * @param string|null $delimiter Delimitador en caso que se quiera tratar
     * de forzar uno.
     * @return string Delimitador que se debe usar, podría ser: el forzado, el
     * configurado en la APP o el por defecto (',').
     */
    protected static function setDelimiter(?string $delimiter = null): string
    {
        if ($delimiter !== null) {
            return $delimiter;
        }
        $delimiter = config('app.ui.spreadsheet.csv.delimiter');
        return $delimiter ? $delimiter : ',';
    }

}
