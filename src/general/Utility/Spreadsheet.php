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
 * Manejar planillas en formato CSV, ODS y XLS
 *
 * Esta clase permite leer y generar planillas de cálculo
 */
class Utility_Spreadsheet
{

    public static $exts = array('ods', 'xls', 'xlsx', 'csv'); ///< extensions

    /**
     * Lee una planilla de cálculo (CSV, ODS o XLS)
     * @param archivo arreglo pasado el archivo (ejemplo $_FILES['archivo']) o bien la ruta hacia el archivo
     */
    public static function read($archivo, $hoja = 0)
    {
        $archivo = self::archivo($archivo);
        // en caso que sea archivo CSV
        if ($archivo['type'] == 'text/csv' || $archivo['type'] == 'text/plain') {
            return Utility_Spreadsheet_CSV::read($archivo['tmp_name']);
        }
        // en caso que sea archivo ODS
        else if ($archivo['type'] == 'application/vnd.oasis.opendocument.spreadsheet') {
            return Utility_Spreadsheet_ODS::read($archivo['tmp_name'], $hoja);
        }
        // en caso que sea archivo XLS
        else if ($archivo['type'] == 'application/vnd.ms-excel') {
            return Utility_Spreadsheet_XLS::read($archivo['tmp_name'], $hoja);
        }
        // en caso que sea archivo XLSX
        else if ($archivo['type'] == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            return Utility_Spreadsheet_XLS::read($archivo['tmp_name'], $hoja, 'Excel2007');
        }
        // en caso que sea archivo XLSM
        else if ($archivo['type'] == 'application/vnd.ms-excel.sheet.macroEnabled.12') {
            return Utility_Spreadsheet_XLS::read($archivo['tmp_name'], $hoja, 'Excel2007');
        }
    }

    /**
     * Crea una planilla de cálculo a partir de un arreglo
     * @param data Arreglo utilizado para generar la planilla
     * @param id Identificador de la planilla
     * @param formato extension de la planilla para definir formato
     */
    public static function generate($data, $id, $formato = 'ods')
    {
        // en caso que sea archivo CSV
        if ($formato == 'csv') {
            Utility_Spreadsheet_CSV::generate($data, $id);
        }
        // en caso que sea archivo ODS
        else if ($formato == 'ods') {
            Utility_Spreadsheet_ODS::generate($data, $id);
        }
        // en caso que sea archivo XLS
        else if ($formato == 'xls') {
            Utility_Spreadsheet_XLS::generate($data, $id);
        }
        else if ($formato == 'xml') {
            Utility_Spreadsheet_XML::generate($data, $id);
        }
        // en caso que sea archivo JSON
        else if ($formato == 'json') {
            Utility_Spreadsheet_JSON::generate($data, $id);
        }
        // terminar ejecucion del script
        exit();
    }

    /**
     * Cargar tabla desde un archivo HTML
     * @todo Revisar errores que se generán por loadHTML (quitar el @, no usarlo!!)
     */
    public static function readFromHTML($html, $tableId = 1, $withColsNames = true, $utf8decode = false)
    {
        // arreglo para ir guardando los datos de la tabla
        $data = [];
        // crear objeto DOM (Document Object Model)
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        // crear objeto para hacer querys al documento
        $xpath = new \DOMXPath($dom);
        // si se pidió una tabla por número se sacan todas las tablas y se
        // saca la de la posición (número) requerida
        if (is_numeric($tableId)) {
            $tables = $xpath->query('//table');
            $table = $tables->item($tableId-1);
        }
        // si se está buscando por id la tabla, se hace la búsqueda de forma
        // directa
        else {
            $table = $xpath->query('//div[@id="'.$tableId.'"]');
        }
        // procesar filas
        $rows = $table->getElementsByTagName('tr');
        $from = $withColsNames ? 0 : 1;
        for ($i=$from; $i<$rows->length; ++$i) {
            // procesar columnas de cada fila
            $cols = $rows->item($i)->getElementsByTagName('td');
            $row = [];
            foreach ($cols as $col) {
                $row[] = $utf8decode
                    ? trim(mb_convert_encoding($col->nodeValue, 'ISO-8859-1', 'UTF-8'))
                    : trim($col->nodeValue)
                ;
            }
            $data[] = $row;
        }
        // retornar datos de la tabla
        return $data;
    }

    /**
     * Método que retorna los nombres de las hojas.
     * @param archivo Archivo que se procesará.
     * @return array Arreglo con los nombres de las hojas.
     */
    public static function sheets($archivo)
    {
        $archivo = self::archivo ($archivo);
        // en caso que sea archivo CSV
        if ($archivo['type'] == 'text/csv' || $archivo['type'] == 'text/plain') {
            return [substr($archivo['name'], 0, -4)];
        }
        // en caso que sea archivo ODS
        else if ($archivo['type'] == 'application/vnd.oasis.opendocument.spreadsheet') {
            return Utility_Spreadsheet_ODS::sheets($archivo['tmp_name']);
        }
        // en caso que sea archivo XLS
        else if ($archivo['type'] == 'application/vnd.ms-excel') {
            return Utility_Spreadsheet_XLS::sheets($archivo['tmp_name']);
        }
        // en caso que sea archivo XLSX
        else if ($archivo['type'] == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            return Utility_Spreadsheet_XLS::sheets($archivo['tmp_name'], 'Excel2007');
        }
        throw new \Exception('No fue posible procesar el archivo ' . $archivo['name'] . ' de tipo ' . $archivo['type']);
    }

    /**
     * Método que lee una o varias hojas de cálculo y dibuja una tabla en HTML
     * por cada una de ellas
     * @param file Archivo con la planilla que se desea renderizar
     * @param options Opciones que se desean utilizar en el renderizado
     * @return string Código HTML con las hojas del archivo
     */
    public static function file2html($file, $options = [])
    {
        // opciones
        $options = array_merge(array(
            'id' => 'file2html',
            'sheet' => -1,
            'export' => true,
        ), $options);
        // helper
        $table = new View_Helper_Table();
        $table->setExport($options['export']);
        // obtener las hojas del archivo
        $sheets = self::sheets($file);
        if ($options['sheet'] > -1) {
            $sheets = array($options['sheet']=>$sheets[$options['sheet']]);
        }
        // agregar títulos de la pestaña
        $buffer = '<script type="text/javascript"> $(function(){ var url = document.location.toString(); if (url.match(\'#\')) $(\'.nav-tabs a[href=#\'+url.split(\'#\')[1]+\']\').tab(\'show\'); else $(\'.nav-tabs > li:first-child > a\').tab(\'show\'); }); </script>'."\n";
        $buffer .= '<div role="tabpanel">'."\n";
        $buffer .= '<ul class="nav nav-tabs" role="tablist">'."\n";
        foreach ($sheets as $id => &$name) {
            $id = $options['id'].'_'.\sowerphp\core\Utility_String::normalize($name);
            $buffer .= '<li role="presentation"><a href="#'.$id.'" aria-controls="'.$id.'" role="tab" data-bs-toggle="tab">'.$name.'</a></li>'."\n";
        }
        $buffer .= '</ul>'."\n";
        // agregar hojas
        $buffer .= '<div class="tab-content">'."\n";
        foreach ($sheets as $id => &$name) {
            $nameN = \sowerphp\core\Utility_String::normalize($name);
            $buffer .= '<div role="tabpanel" class="tab-pane" id="'.$options['id'].'_'.$nameN.'">'."\n";
            if ($options['export']) {
                $table->setId($nameN);
            }
            $buffer .= $table->generate(self::read($file, $id));
            $buffer .= '</div>'."\n";
        }
        // finalizar
        $buffer .= '</div>'."\n";
        $buffer .= '</div>'."\n";
        // entregar buffer
        return $buffer;
    }

    /**
     * Método que tranforma una hoja de calculo (un arreglo donde
     * la primera fila son los nombres de las columnas) a un
     * arreglo asociativo (donde las claves de las columnas en
     * las filas es el nombre de la columna).
     */
    public static function sheet2array($data)
    {
        $colNames = array_shift($data);
        $cols = count($colNames);
        $aux = [];
        foreach ($data as &$row) {
            $auxRow = [];
            for ($i=0; $i<$cols; ++$i) {
                $auxRow[$colNames[$i]] = $row[$i];
            }
            $aux[] = $auxRow;
        }
        return $aux;
    }

    /**
     * Método que normaliza los datos del archivo en un arreglo (igual a arreglo en $_FILES)
     * @param archivo Nombre del archivo
     */
    private static function archivo($archivo)
    {
        // si lo que se paso fue la ruta del archivo se debe construir el arreglo con los datos del mismo (igual a arreglo $_FILES)
        if (!is_array($archivo)) {
            // parche: al hacer $archivo['tmp_name'] = $archivo no queda como array, por lo que uso un auxiliar para resetear a archivo
            $aux = $archivo;
            $archivo = null;
            $archivo['tmp_name'] = $aux;
            unset($aux);
            $archivo['name'] = basename($archivo['tmp_name']);
            $archivo['size'] = filesize($archivo['tmp_name']);
        }
        // extensión siempre se chequea y reemplaza (ya que PHP puede cambiar
        // esto entre distintas versiones)
        switch (strtolower(substr($archivo['name'], strrpos($archivo['name'], '.')+1))) {
            case 'csv': {
                $archivo['type'] = 'text/csv';
                break;
            }
            case 'txt': {
                $archivo['type'] = 'text/plain';
                break;
            }
            case 'ods': {
                $archivo['type'] = 'application/vnd.oasis.opendocument.spreadsheet';
                break;
            }
            case 'xls': {
                $archivo['type'] = 'application/vnd.ms-excel';
                break;
            }
            case 'xlsx': {
                $archivo['type'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                break;
            }
        }
        // entregar archivo normalizado
        return $archivo;
    }

}
