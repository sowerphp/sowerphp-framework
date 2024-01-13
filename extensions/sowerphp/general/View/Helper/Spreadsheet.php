<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
 * Copyright (C) SowerPHP <https://www.sowerphp.org>
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
 * Helper para generar planillas de cálculo
 */
class View_Helper_Spreadsheet extends \PhpOffice\PhpSpreadsheet\Spreadsheet
{

    protected $y; ///< Para la fila actual (parte en 1)
    protected $x; ///< Para la columna ctual (parte en 0)

    /**
     * Método que guarda la planilla en el sistema de archivos
     * @param file Ruta completa donde guardar la planilla en el sistema de archivos
     * @param type Tipo de archivo que se generará (formato de la planilla)
     */
    public function save($file, $type = 'Xls')
    {
        $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($this, $type);
        $objWriter->save($file);
    }

    /**
     * Método que descarga la planilla a través el navegador
     * @param file Nombre del archivo que se descargará
     * @param type Tipo de archivo que se generará (formato de la planilla)
     */
    public function download($file, $type = 'Xls')
    {
        $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($this, $type);
        ob_end_clean();
        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="'.$file.'"');
        $objWriter->save('php://output');
        exit(0);
    }

    /**
     * Asignar margenes a la vista de impresión de la planilla
     * @param margins Arreglo de margenes que se desean asignar o bien solo un margen que será igual a todos lados (en centímetros)
     */
    public function setMargins($margins)
    {
        if (!is_array($margins)) {
            $margins = [
                'top' => $margins,
                'bottom' => $margins,
                'left' => $margins,
                'right' => $margins,
            ];
        }
        foreach ($margins as $position => $margin) {
            $method = 'set'.ucfirst($position);
            $this->getActiveSheet()->getPageMargins()->$method($margin / 2.54);
        }
    }

    /**
     * Método que traduce un índice de columna a su representación en letra(s).
     * Este método es un wrapper de \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex()
     * @param col Índice de la columna que se desea obtener
     * @return string Letra de la columna
     */
    public function getCol($col)
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    }

    /**
     * Método que aplica un formato de negrita y centrado a una celda
     * @param cell Celda (o rango de celdas) que se quiere modificar
     * @param sheet Índice de la hoja donde está la celda que se quiere modificar
     */
    public function setFormatCenterBold($cell, $sheet = null)
    {
        if ($sheet === null) {
            $sheet = $this->getActiveSheetIndex();
        }
        $this->setActiveSheetIndex($sheet)->getStyle($cell)->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'font' => [
                'bold' => true,
            ],
        ]);
    }

    /**
     * Método que aplica un borde a una celda
     * @param cell Celda (o rango de celdas) que se quiere modificar
     * @param sheet Índice de la hoja donde está la celda que se quiere modificar
     */
    public function setFormatBorder($cell, $sheet = null)
    {
        if ($sheet === null) {
            $sheet = $this->getActiveSheetIndex();
        }
        $this->setActiveSheetIndex($sheet)->getStyle($cell)->applyFromArray([
            'borders' => [
                'allborders' => [
                    'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ]
        ]);
    }

    /**
     * Método que aplica un formato de número a una celda
     * @param cell Celda (o rango de celdas) que se quiere modificar
     * @param sheet Índice de la hoja donde está la celda que se quiere modificar
     * @param format Formato de número que se desea aplicar a la celda
     */
    public function setFormatNumber($cell, $sheet = null, $format = '#,##0')
    {
        if ($sheet === null) {
            $sheet = $this->getActiveSheetIndex();
        }
        $this->setActiveSheetIndex($sheet)->getStyle($cell)->getNumberFormat()->setFormatCode($format);
    }

    /**
     * Método que asigna un valor a una celda unida
     */
    public function setMergeCellValue($value, $start, $end)
    {
        $this->getActiveSheet()->mergeCells($start.$this->y.':'.$end.$this->y);
        $this->setFormatCenterBold($start.$this->y);
        $this->getActiveSheet()->getCell($start.$this->y)->setValue($value);

    }

    /**
     * Método que asigna un valor a una celda unida y la rota
     */
    public function setRotateCellValue($value, $col, $end)
    {
        $this->getActiveSheet()->mergeCells($col.$this->y.':'.$col.($this->y+$end));
        $this->getActiveSheet()->getStyle($col.$this->y)->getAlignment()->setTextRotation(90);
        $this->setFormatCenterBold($col.$this->y);
        $this->getActiveSheet()->getStyle($col.$this->y)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $this->getActiveSheet()->getCell($col.$this->y)->setValue($value);
    }

    /**
     * Método que activa el tamaño automático de columnas
     * @param cell Celda (o rango de celdas) que se quiere modificar
     * @param sheet Índice de la hoja donde está la celda que se quiere modificar
     */
    public function setAutoSize($to, $from = 'A', $sheet = null)
    {
        if ($sheet === null) {
            $sheet = $this->getActiveSheetIndex();
        }
        foreach(range($from, $to) as $columnID) {
            $this->setActiveSheetIndex($sheet)->getColumnDimension($columnID)->setAutoSize(true);
        }
    }

}
