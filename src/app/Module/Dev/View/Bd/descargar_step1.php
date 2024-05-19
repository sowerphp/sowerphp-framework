<div class="page-header"><h1>Descargar tablas de la Base de Datos</h1></div>
<p>Seleccione la base de datos desde donde elegir las tablas, en la siguiente página podrá seleccionar que tablas descargar.</p>
<?php
$f = new \sowerphp\general\View_Helper_Form ();
echo $f->begin(array('onsubmit'=>'Form.check()'));
echo $f->input (array(
    'type'=>'select',
    'name'=>'database',
    'label'=>'Base de datos',
    'options'=>$databases,
    'check'=>'notempty',
    'help'=>'Nombre de la base de datos definida dentro de la configuración en Config/core.php',
));
echo $f->input(array(
    'type'=>'select',
    'name'=>'type',
    'label'=>'Tipo de archivo a generar',
    'options'=>array('ods'=>'ODS', 'xls'=>'XLS'),
    'check'=>'notempty',
    'help'=>'Formato en el que se exportarán los datos',
));
echo $f->end(array('name'=>'step1', 'value'=>'Siguiente >>'));
