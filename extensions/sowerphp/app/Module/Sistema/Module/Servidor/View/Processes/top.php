<div class="page-header"><h1>Procesos ordenados por uso de <?=strtoupper($order)?></h1></div>
<?php

array_unshift($top, ['PID', 'USER', 'PR', 'NI', 'VIRT', 'RES', 'SHR', 'S', '%CPU', '%MEM', 'TIME', 'COMMAND']);
new \sowerphp\general\View_Helper_Table($top, 'procesos_'.$order, true);
