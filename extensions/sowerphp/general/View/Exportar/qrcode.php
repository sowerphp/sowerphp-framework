<?php
$barcodeobj = new TCPDF2DBarcode($string, 'QRCode');
$barcodeobj->getBarcodePNG($size, $size, $color);
exit (0);
