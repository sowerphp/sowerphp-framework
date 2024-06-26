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

class Controller_Exportar extends \Controller_App
{

    public function boot()
    {
    }

    public function ods($id)
    {
        $data = $this->_getData($id);
        \sowerphp\general\Utility_Spreadsheet_ODS::generate($data, $id);
    }

    public function xls($id)
    {
        $data = $this->_getData($id);
        \sowerphp\general\Utility_Spreadsheet_XLS::generate($data, $id);
    }

    public function csv($id)
    {
        $data = $this->_getData($id);
        \sowerphp\general\Utility_Spreadsheet_CSV::generate($data, $id);
    }

    public function pdf($id)
    {
        $data = $this->_getData($id);
        error_reporting(false);
        $title = config('app.name');
        if (empty($title)) {
            $title = 'Listado en PDF';
        }
        $pdf = new \sowerphp\general\View_Helper_PDF();
        $pdf->setInfo (
            $title,
            'Tabla: '.$id
        );
        $pdf->setStandardHeaderFooter(
            app('layers')->getFilePath('/webroot/img/logo.png'),
            $title,
            'Tabla: '.$id
        );
        $pdf->AddPage();
        $pdf->addTable(array_shift($data), $data, [], true);
        $pdf->Output($id.'.pdf', 'D');
        exit(0);
    }

    public function xml($id)
    {
        $data = $this->_getData($id);
        \sowerphp\general\Utility_Spreadsheet_XML::generate($data, $id);
    }

    public function json($id)
    {
        $data = $this->_getData($id);
        \sowerphp\general\Utility_Spreadsheet_JSON::generate($data, $id);
    }

    private function _getData($id)
    {
        $data = (new \sowerphp\core\Cache())->get('session.'.session_id().'.export.'.$id);
        if (!$data) {
            throw new Exception_Data_Missing(['id' => $id]);
        }
        return $data;
    }

    public function barcode($string, $type = 'C128')
    {
        $barcodeobj = new \TCPDFBarcode(base64_decode($string), $type);
        $barcodeobj->getBarcodePNG();
        exit(0);
    }

    public function qrcode($string, $size = 3, $color = '0,0,0')
    {
        $barcodeobj = new \TCPDF2DBarcode(base64_decode($string), 'QRCode');
        $barcodeobj->getBarcodePNG($size, $size, explode(',', $color));
        exit(0);
    }

    public function pdf417($string)
    {
        $barcodeobj = new \TCPDF2DBarcode(base64_decode($string), 'PDF417');
        $barcodeobj->getBarcodePNG();
        exit(0);
    }

}
