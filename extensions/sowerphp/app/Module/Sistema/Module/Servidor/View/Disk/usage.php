<div class="page-header"><h1>Sistemas de archivos y puntos de montaje</h1></div>
<?php
array_unshift($usage, ['Sistema de archivos', 'Tipo', 'Tamaño', 'Usados', 'Disponible', '% de uso', 'Montado en']);
new \sowerphp\general\View_Helper_Table($usage, 'disks', true);
