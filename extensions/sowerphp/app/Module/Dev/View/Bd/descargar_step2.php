<div class="page-header"><h1>Descargar tablas de la Base de Datos (paso 2)</h1></div>
<p>Seleccione las tablas que desea descargar.</p>
<?php
$f = new \sowerphp\general\View_Helper_Form (null);
echo $f->begin(array('onsubmit'=>'Form.check()'));
echo $f->input (array(
    'type'=>'hidden',
    'name'=>'database',
    'value'=>$database
));
echo $f->input (array(
    'type'=>'hidden',
    'name'=>'type',
    'value'=>$type
));
echo $f->input (array(
    'type'=>'tablecheck',
    'name'=>'tables',
    'label'=>'Tablas',
    'titles'=>array('Tabla', 'Comentario'),
    'table'=>$tables,
));
echo $f->end(array(
    'name'=>'step2',
    'value'=>'Generar archivo',
    'align'=>'center',
));
