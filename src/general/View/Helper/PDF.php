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

// Directorio para imagenes vació (no se asume nada)
define ('K_PATH_IMAGES', '');

/**
 * Clase para generar PDFs
 */
class View_Helper_PDF extends \TCPDF
{

    protected $margin_top; ///< Margen extra (al por defecto) para la parte de arriba de la página

    protected $defaultOptions = array(
        'font' => array ('family' => 'helvetica', 'size' => 10),
        'header' => array (
            'textcolor' => array (0,0,0),
            'linecolor' => array (136, 137, 140),
            'logoheight' => 20,
        ),
        'footer' => array (
            'textcolor' => array (35, 31, 32),
            'linecolor' => array (136, 137, 140),
        ),
        'table' => array (
            'fontsize' => 10,
            'width' => 186,
            'height' => 6,
            'align' => 'C',
            'bordercolor' => [0, 0, 0],
            'borderwidth' => 0.1,
            'headerbackground' => [238, 238, 238],
            'headercolor' => [102, 102, 102],
            'bodybackground' => array(224, 235, 255),
            'bodycolor' => array(0,0,0),
            'colorchange' => true,
        ),
    );

    /**
     * Constructor de la clase
     * @param o Orientación
     * @param u Unidad de medida
     * @param s Tipo de hoja
     * @param top Margen extra (al normal) para la parte de arriba del PDF
     */
    public function __construct($o = 'P', $u = 'mm', $s = 'LETTER', $top = 8)
    {
        parent::__construct($o, $u, $s);
        $this->margin_top = $top;
        $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP+$top, PDF_MARGIN_RIGHT);
        $this->SetHeaderMargin(PDF_MARGIN_HEADER+$top);
        $this->SetFooterMargin(PDF_MARGIN_FOOTER);
    }

    /**
     * Asignar información del PDF
     */
    public function setInfo($autor, $titulo, $creador = 'SowerPHP')
    {
        $this->SetCreator($creador);
        $this->SetAuthor($autor);
        $this->SetTitle($titulo);
    }

    /**
     * Asignar encabezado y pie de página
     */
    public function setStandardHeaderFooter($logo, $title, $subtitle = '')
    {
        $size = getimagesize($logo);
        $width = round(($size[0]*$this->defaultOptions['header']['logoheight'])/$size[1]);
        $this->SetHeaderData($logo, $width, $title, $subtitle,
            $this->defaultOptions['header']['textcolor'],
            $this->defaultOptions['header']['linecolor']
        );
        $this->setFooterData(
            $this->defaultOptions['footer']['textcolor'],
            $this->defaultOptions['footer']['linecolor']
        );
        $this->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $this->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    }

    /**
     * Método que sobreescribe la cabecera del PDF para agregar URL de la web
     */
    public function Header()
    {
        parent::Header();
        $this->SetFont('helvetica', 'B', 10);
        $link = 'http'.(isset($_SERVER['HTTPS'])?'s':null).'://'.$_SERVER['HTTP_HOST'];
        $this->Texto($link, null, 20+$this->margin_top, 'R', null, $link);
    }

    /**
     * Método que sobreescribe el pie de página del PDF
     */
    public function Footer()
    {
        $this->SetFont('helvetica', '', 8);
        parent::Footer();
        $this->SetY($this->GetY());
        $this->SetFont('helvetica', 'B', 6);
        $this->Texto('Documento generado el '. date('d/m/Y').' a las '.date('H:i'));
    }

    /**
     * Obtener el ancho de las columnas de una tabla
     */
    private function getTableCellWidth($total, $cells)
    {
        $widths = [];
        if (is_int($cells)) {
            $width = floor($total/$cells);
            for ($i=0; $i < $cells; ++$i) {
                $widths[] = $width;
            }
        }
        else if (is_array($cells)) {
            $width = floor($total/count($cells));
            foreach ($cells as $i) {
                $widths[$i] = $width;
            }
        }
        return $widths;
    }

    /**
     * Agregar una tabla al PDF removiendo aquellas columnas donde no existen
     * dantos en la columna para todas las filas
     */
    public function addTableWithoutEmptyCols($titles, $data, $options = [], $html = false)
    {
        $cols_empty = [];
        foreach ($data as $row) {
            foreach ($row as $col => $value) {
                if (((string)$value) == '') {
                    if (!array_key_exists($col, $cols_empty)) {
                        $cols_empty[$col] = 0;
                    }
                    $cols_empty[$col]++;
                }
            }
        }
        $n_rows = count($data);
        $titles_keys = array_flip(array_keys($titles));
        foreach ($cols_empty as $col => $rows) {
            if ($rows == $n_rows) {
                unset($titles[$col]);
                foreach ($data as &$row) {
                    unset($row[$col]);
                }
                if (isset($options['width'])) {
                    unset($options['width'][$titles_keys[$col]]);
                }
                if (isset($options['align'])) {
                    unset($options['align'][$titles_keys[$col]]);
                }
            }
        }
        if (isset($options['width'])) {
            $options['width'] = array_slice($options['width'], 0);
            $key_0 = null;
            $suma = 0;
            foreach ($options['width'] as $key => $val) {
                if ($val === 0) {
                    $key_0 = $key;
                }
                $suma += $val;
            }
            if ($key_0 !== null) {
                $options['width'][$key_0] = 190 - $suma;
            }
        }
        if (isset($options['align'])) {
            $options['align'] = array_slice($options['align'], 0);
        }
        $this->addTable($titles, $data, $options, $html);
    }

    /**
     * Agregar una tabla al PDF
     */
    public function addTable ($headers, $data, $options = [], $html = false)
    {
        // asignar opciones por defecto
        $options = array_merge($this->defaultOptions['table'],$options);
        // generar tabla
        if ($html) {
            $this->addHTMLTable ($headers, $data, $options);
        } else {
            $this->addNormalTable ($headers, $data, $options);
        }
    }

    /**
     * Agregar una tabla generada a través de código HTML al PDF
     * @todo Utilizar las opciones para definir estilo de la tabla HTML
     */
    private function addHTMLTable ($headers, $data, $options = [])
    {
        $w = (isset($options['width']) && is_array($options['width'])) ? $options['width'] : null;
        $a = (isset($options['align']) && is_array($options['align'])) ? $options['align'] : [];
        $buffer = '<table>';
        // Definir títulos de columnas
        $thead = isset($options['width']) && is_array($options['width']) && count($options['width']) == count($headers);
        if ($thead) {
            $buffer .= '<thead>';
        }
        $buffer .= '<tr>';
        $i = 0;
        foreach ($headers as &$col) {
            $width = ($w && isset($w[$i])) ? (';width:'.$w[$i].'mm') : '';
            $align = isset($a[$i]) ? $a[$i] : 'center';
            $buffer .= '<th style="background-color:#eee;color:#666;text-align:'.$align.$width.'"><strong>'.strip_tags($col).'</strong></th>';
            $i++;
        }
        $buffer .= '</tr>';
        if ($thead) {
            $buffer .= '</thead>';
        }
        // Definir datos de la tabla
        if ($thead) {
            $buffer .= '<tbody>';
        }
        foreach ($data as &$row) {
            $buffer .= '<tr>';
            $i = 0;
            foreach ($row as &$col) {
                $width = ($w && isset($w[$i])) ? (';width:'.$w[$i].'mm') : '';
                $align = isset($a[$i]) ? $a[$i] : 'center';
                $buffer .= '<td style="border-bottom:1px solid #ddd;text-align:'.$align.$width.'">'.$col.'</td>';
                $i++;
            }
            $buffer .= '</tr>';
        }
        if ($thead) {
            $buffer .= '</tbody>';
        }
        // Finalizar tabla
        $buffer .= '</table>';
        // generar tabla en HTML
        $this->writeHTML ($buffer, true, false, false, false, '');
    }

    /**
     * Agregar una tabla generada mediante el método Cell
     */
    private function addNormalTable(array $headers, array $data, array $options = [])
    {
        // Colors, line width and bold font
        $this->SetFillColor(
            $options['headerbackground'][0],
            $options['headerbackground'][1],
            $options['headerbackground'][2]
        );
        $this->SetTextColor(
            $options['headercolor'][0],
            $options['headercolor'][1],
            $options['headercolor'][2]
        );
        $this->SetDrawColor(
            $options['bordercolor'][0],
            $options['bordercolor'][1],
            $options['bordercolor'][2]
        );
        $this->SetLineWidth($options['borderwidth']);
        $this->SetFont($this->defaultOptions['font']['family'], 'B',  $options['fontsize']);
        // corregir indices
        $headers_keys = array_keys($headers);
        if (is_array($options['width'])) {
            $options['width'] = array_combine($headers_keys, $options['width']);
        } else {
            $options['width'] = $this->getTableCellWidth($options['width'], $headers_keys);
        }
        if (is_array($options['width'])) {
            if (is_string($options['align'])) {
                $options['align'] = array_fill(0, count($headers_keys), $options['align']);
            }
            $options['align'] = array_combine($headers_keys, $options['align']);
            foreach ($options['align'] as &$a) {
                $a = strtoupper($a[0]);
            }
        }
        // Header
        $x = $this->GetX();
        foreach ($headers as $i => $header) {
            $this->Cell($options['width'][$i], $options['height'], $headers[$i], 1, 0, $options['align'][$i], 1);
        }
        $this->Ln();
        // Color and font restoration
        $this->SetFillColor (
            $options['bodybackground'][0],
            $options['bodybackground'][1],
            $options['bodybackground'][2]
        );
        $this->SetTextColor(
            $options['bodycolor'][0],
            $options['bodycolor'][1],
            $options['bodycolor'][2]
        );
        $this->SetDrawColor(
            $options['bordercolor'][0],
            $options['bordercolor'][1],
            $options['bordercolor'][2]
        );
        $this->SetLineWidth($options['borderwidth']);
        $this->SetFont($this->defaultOptions['font']['family']);
        // Data
        $fill = false;
        foreach ($data as &$row) {
            $num_pages = $this->getNumPages();
            $this->startTransaction();
            $this->SetX($x);
            foreach ($headers as $i => $header) {
                $this->Cell($options['width'][$i], $options['height'], $row[$i], 'LR', 0, $options['align'][$i], $fill);
            }
            $this->Ln();
            if ($num_pages < $this->getNumPages()) {
                $this->rollbackTransaction(true);
                $this->AddPage();
                $this->SetX($x);
                foreach ($headers as $i => $header) {
                    $this->Cell($options['width'][$i], $options['height'], $headers[$i], 1, 0, $options['align'][$i], 1);
                }
                $this->Ln();
                $this->SetX($x);
                foreach ($headers as $i => $header) {
                    $this->Cell($options['width'][$i], $options['height'], $row[$i], 'LR', 0, $options['align'][$i], $fill);
                }
                $this->Ln();
            } else {
                $this->commitTransaction();
            }
            if ($options['colorchange']) {
                $fill = !$fill;
            }
        }
        $this->SetX($x);
        $this->Cell(array_sum($options['width']), 0, '', 'T');
        $this->Ln();
    }

    /**
     * Agregar texto al PDF, es una variación del método Text que permite
     * definir un ancho al texto. Además recibe menos parámetros para ser
     * más simple (parámetros comunes solamente).
     */
    public function Texto($txt, $x=null, $y=null, $align='', $w=0, $link='', $border=0, $fill=false)
    {
        if ($x === null) {
            $x = $this->GetX();
        }
        if ($y === null) {
            $y = $this->GetY();
        }
        $textrendermode = $this->textrendermode;
        $textstrokewidth = $this->textstrokewidth;
        $this->setTextRenderingMode(0, true, false);
        $this->SetXY($x, $y);
        $this->Cell($w, 0, $txt, $border, 0, $align, $fill, $link);
        // restore previous rendering mode
        $this->textrendermode = $textrendermode;
        $this->textstrokewidth = $textstrokewidth;
    }

    /**
     * Método idéntico a Texto, pero en vez de utilizar Cell utiliza
     * MultiCell. La principal diferencia es que este método no permite
     * agregar un enlace y Texto si.
     */
    public function MultiTexto($txt, $x=null, $y=null, $align='', $w=0, $border=0, $fill=false)
    {
        if ($x === null) {
            $x = $this->GetX();
        }
        if ($y === null) {
            $y = $this->GetY();
        }
        $textrendermode = $this->textrendermode;
        $textstrokewidth = $this->textstrokewidth;
        $this->setTextRenderingMode(0, true, false);
        $this->SetXY($x, $y);
        $this->MultiCell($w, 0, $txt, $border, $align, $fill);
        // restore previous rendering mode
        $this->textrendermode = $textrendermode;
        $this->textstrokewidth = $textstrokewidth;
    }

}
