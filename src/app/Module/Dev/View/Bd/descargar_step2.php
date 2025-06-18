<div class="page-header"><h1>Descargar tablas de la Base de Datos (paso 2)</h1></div>
<p>Seleccione las tablas que desea descargar.</p>
<?php
$f = new \sowerphp\general\View_Helper_Form (null);
echo $f->begin(['onsubmit'=>'Form.check()']);
echo $f->input ([
    'type'=>'hidden',
    'name'=>'database',
    'value'=>$database,
]);
echo $f->input ([
    'type'=>'hidden',
    'name'=>'type',
    'value'=>$type,
]);
echo $f->input ([
    'type'=>'tablecheck',
    'name'=>'tables',
    'label'=>'Tablas',
    'titles'=>['Tabla', 'Comentario'],
    'table'=>$tables,
]);
echo $f->end([
    'name'=>'step2',
    'value'=>'Generar archivo',
    'align'=>'center',
]);
