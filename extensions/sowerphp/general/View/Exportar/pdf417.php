<?php
$barcodeobj = new TCPDF2DBarcode($string, 'PDF417');
$barcodeobj->getBarcodePNG();
exit (0);
