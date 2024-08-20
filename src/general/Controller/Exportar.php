<?php

/**
 * SowerPHP: Simple and Open Web Ecosystem Reimagined for PHP.
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
 * Controlador con acciones para exportar datos en diferentes formatos.
 *
 * Se definen 2 formas de pasar los datos que se exportarán:
 *   - Tablas mediante la sesión: ods, xls, csv, xml, json y pdf.
 *   - Códigos como imágenes PNG: barcode, qrcode y pdf417.
 *
 * Son métodos estándares que entregan los datos de manera estándar. No es la
 * manera más "linda" de exportar los datos, pero es una manera rápida que
 * permite pasar fácilmente los datos a diferentes formatos. En muchos casos
 * suele ser suficiente con estos métodos para diversas aplicaciones.
 *
 */
class Controller_Exportar extends \sowerphp\autoload\Controller
{

    public function boot(): void
    {
    }

    public function ods(string $id)
    {
        $data = $this->getExportDataFromSession($id);
        \sowerphp\general\Utility_Spreadsheet_ODS::generate($data, $id);
    }

    public function xls(string $id)
    {
        $data = $this->getExportDataFromSession($id);
        \sowerphp\general\Utility_Spreadsheet_XLS::generate($data, $id);
    }

    public function csv(string $id)
    {
        $data = $this->getExportDataFromSession($id);
        \sowerphp\general\Utility_Spreadsheet_CSV::generate($data, $id);
    }

    public function xml(string $id)
    {
        $data = $this->getExportDataFromSession($id);
        \sowerphp\general\Utility_Spreadsheet_XML::generate($data, $id);
    }

    public function json(string $id)
    {
        $data = $this->getExportDataFromSession($id);
        \sowerphp\general\Utility_Spreadsheet_JSON::generate($data, $id);
    }

    public function pdf(string $id)
    {
        $data = $this->getExportDataFromSession($id);
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
        ob_start();
        $pdf->Output($id.'.pdf', 'D'); // WARNING: esto asigna cabeceras.
        $content = ob_get_clean();
        return $content;
    }

    /**
     * Obtener datos que se deben exportar desde la sesión.
     *
     * Los datos se guardan en la sesión por la funcionalidad que los desee
     * exportar, de esta forma se pueden pasar a este controlador y ser
     * descargados por la misma sesión (usuario) que los haya solicitado.
     *
     * @param string $id Identificador de los datos que se desean exportar.
     * @return mixed Datos que estaban en la sesión para ser exportados.
     * @throws \Exception Si no existen datos para exportar en la sesión.
     */
    protected function getExportDataFromSession(string $id)
    {
        $key = 'session.' . session()->getId() . '.export.' . $id;
        $data = cache()->get($key);
        if (!$data) {
            throw new \Exception(__(
                'No hay datos que exportar con el id "%s".',
                $id
            ));
        }
        return $data;
    }

    /**
     * Acción que genera una imagen PNG de un código de barras a partir de un
     * texto en un string codificado en base64.
     *
     * @param string $string Texto codificado en base64.
     * @param string $type Tipo de código de barras que se generará.
     * @return string Contenido de la imagen PNG del código de barras.
     */
    public function barcode(string $string, string $type = 'C128'): string
    {
        $barcodeobj = new \TCPDFBarcode(base64_decode($string), $type);
        ob_start();
        $barcodeobj->getBarcodePNG();
        $content = ob_get_clean();
        return $content;
    }

    /**
     * Acción que genera una imagen PNG de un código QR a partir de un
     * texto en un string codificado en base64.
     *
     * @param string $string Texto codificado en base64.
     * @param int $size Tamaño del código QR.
     * @param string $color Color del código QR. Formato: R,G,B.
     * @return string Contenido de la imagen PNG del código QR.
     */
    public function qrcode(string $string, int $size = 5, string $color = '0,0,0'): string
    {
        $barcodeobj = new \TCPDF2DBarcode(base64_decode($string), 'QRCode');
        ob_start();
        $barcodeobj->getBarcodePNG($size, $size, explode(',', $color));
        $content = ob_get_clean();
        return $content;
    }

    /**
     * Acción que genera una imagen PNG de un código PDF417 a partir de un
     * texto en un string codificado en base64.
     *
     * @param string $string Texto codificado en base64.
     * @return string Contenido de la imagen PNG del código PDF417.
     */
    public function pdf417(string $string): string
    {
        $barcodeobj = new \TCPDF2DBarcode(base64_decode($string), 'PDF417');
        ob_start();
        $barcodeobj->getBarcodePNG();
        $content = ob_get_clean();
        return $content;
    }

}
