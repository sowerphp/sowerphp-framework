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
 * Manejo de planillas de cálculo de OpenDocumment
 * @author DeLaF, esteban[at]delaf.cl
 * @version 2014-03-03
 */
final class Utility_Spreadsheet_ODS
{

    /**
     * Lee el contenido de una hoja y lo devuelve como arreglo
     * @param sheet Hoja a leer (0..n)
     * @return Arreglo con los datos de la hoja (indices parten en 1)
     * @author DeLaF, esteban[at]delaf.cl
     * @version 2012-06-11
     */
    public static function read ($archivo = null, $hoja = 0)
    {
        return Utility_Spreadsheet_XLS::read($archivo, $hoja, 'OOCalc');
    }

    /**
     * Crea una planilla de cálculo a partir de un arreglo
     * @param data Arreglo utilizado para generar la planilla
     * @param id Identificador de la planilla
     * @author DeLaF, esteban[at]delaf.cl
     * @version 2016-01-17
     */
    public static function generate ($data, $id, $type = 'Ods')
    {
        // Crear objeto PHPOffice
        $objPHPOffice = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        // si las llaves de $table no son strings, entonces es solo una hoja
        if (!is_string(array_keys($data)[0])) {
            $data = array($id=>$data);
        }
        // generar hojas
        $hoja = 0;
        $n_hojas = count ($data);
        foreach ($data as $name => &$sheet) {
            // agregar hoja
            $objWorkSheet = $objPHPOffice->setActiveSheetIndex($hoja);
            // Colocar título a la hoja
            $objWorkSheet->setTitle(substr($name, 0, 30));
            // Colocar datos
            $y=1; // fila
            $x=0; // columna
            foreach ($sheet as &$fila) {
                foreach ($fila as &$celda) {
                    $objWorkSheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($x++).$y)->setValue(rtrim(str_replace('<br />', "\n", strip_tags($celda, '<br>'))));
                }
                $x=0;
                ++$y;
            }
            ++$hoja;
            if ($hoja<$n_hojas)
                $objPHPOffice->createSheet($hoja);
        }
        $objPHPOffice->setActiveSheetIndex(0);
        // Generar archivo excel
        $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPOffice, $type);
        ob_end_clean();
        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="'.$id.'.ods"');
        $objWriter->save('php://output');
        exit(0);
    }

    /**
     * Método que retorna los nombres de las hojas
     * @param archivo Archivo que se procesará
     * @return Arreglo con los nombres de las hojas
     * @author DeLaF (esteban[at]delaf.cl)
     * @version 2013-04-04
     */
    public static function sheets ($archivo)
    {
        return Utility_Spreadsheet_XLS::sheets($archivo, 'OOCalc');
    }

}
