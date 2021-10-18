<?php
$barcodeobj = new TCPDFBarcode($string, $type);
$barcodeobj->getBarcodePNG();
exit (0);
