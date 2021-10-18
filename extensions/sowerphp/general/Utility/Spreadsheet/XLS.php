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
 * Manejar archivos en excel
 *
 * Esta clase permite leer y generar archivos en excel
 * @author DeLaF, esteban[at]delaf.cl
 * @version 2014-02-20
 */
final class Utility_Spreadsheet_XLS
{

    /**
     * Lee una planilla de cálculo
     * @param archivo archivo a leer (ejemplo celda tmp_name de un arreglo $_FILES)
     * @param hoja Hoja que se quiere devolver, comenzando por la 0
     * @author DeLaF, esteban[at]delaf.cl
     * @version 2014-02-23
     */
    public static function read ($archivo = null, $hoja = 0, $type = 'Excel5')
    {
        // Crear objeto para leer archivo
        $objReader = \PHPExcel_IOFactory::createReader($type);
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($archivo);
        // Seleccionar hoja a leer
        $objWorksheet = $objPHPExcel->getSheet($hoja);
        // Recuperar celdas
        $data = array();
        foreach ($objWorksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $fila = array();
            foreach ($cellIterator as $cell) {
                $fila[] = $cell->getValue();
            }
            $data[] = $fila;
        }
        return $data;
    }

    /**
     * Crea una planilla de cálculo a partir de un arreglo, requiere clase
     * Spreadsheet_Excel_Writer del repositorio de Pear
     * @param tabla Arreglo utilizado para generar la planilla
     * @param id Identificador de la planilla
     * @param horizontal Indica si la hoja estara horizontalmente (true) o verticalmente (false)
     * @author DeLaF, esteban[at]delaf.cl
     * @version 2014-07-10
     */
    public static function generate ($tabla, $id, $type = 'Excel5')
    {
        // Crear objeto PHPExcel
        $objPHPExcel = new \PHPExcel();
        // si las llaves de $table no son strings, entonces es solo una hoja
        if (!is_string(array_keys($tabla)[0])) {
            $tabla = array($id=>$tabla);
        }
        // generar hojas
        $hoja = 0;
        $n_hojas = count ($tabla);
        foreach ($tabla as $name => &$sheet) {
            // agregar hoja
            $objWorkSheet = $objPHPExcel->setActiveSheetIndex($hoja);
            // Colocar título a la hoja
            $objWorkSheet->setTitle(substr($name, 0, 30));
            // Colocar datos
            $y=1; // fila
            $x=0; // columna
            foreach ($sheet as &$fila) {
                foreach ($fila as &$celda) {
                    $objWorkSheet->setCellValue(
                        \PHPExcel_Cell::stringFromColumnIndex($x++).$y,
                        rtrim(str_replace('<br />', "\n", strip_tags($celda, '<br>')))
                    );
                }
                $x=0;
                ++$y;
            }
            ++$hoja;
            if ($hoja<$n_hojas)
                $objPHPExcel->createSheet($hoja);
        }
        $objPHPExcel->setActiveSheetIndex(0);
        // Generar archivo excel
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, $type);
        ob_end_clean();
        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="'.$id.'.xls"');
        $objWriter->save('php://output');
        exit(0);
    }

    /**
     * Método que retorna los nombres de las hojas
     * @param archivo Archivo que se procesará
     * @return Arreglo con los nombres de las hojas
     * @author DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-27
     */
    public static function sheets ($archivo, $type = 'Excel5')
    {
        // Crear objeto para leer archivo
        $objReader = \PHPExcel_IOFactory::createReader($type);
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($archivo);
        // Retornar hojas
        if ($type=='OOCalc')
            return array_slice($objPHPExcel->getSheetNames(), 0, -1);
        return $objPHPExcel->getSheetNames();
    }

}
