<?php

// se desactivarán errores
error_reporting(false);

// crear objeto para poder generar el pdf
$pdf = new \sowerphp\general\View_Helper_PDF ();

// propiedades del documento
$pdf->setInfo (
    Configure::read('page.body.title'),
    'Tabla: '.$id
);

// encabezado y pie de página
$pdf->setStandardHeaderFooter (
    DIR_WEBSITE.'/webroot/img/logo.png',
    Configure::read('page.body.title'),
    'Tabla: '.$id
);

// agregar datos
$pdf->AddPage();
$pdf->addTable (array_shift($data), $data, array(), true);

// generar pdf y terminar
$pdf->Output($id.'.pdf', 'D');
exit(0);
