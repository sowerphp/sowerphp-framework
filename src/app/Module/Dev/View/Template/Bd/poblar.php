<div class="page-header"><h1>Poblar tablas de la Base de Datos</h1></div>
<?php
$f = new \sowerphp\general\View_Helper_Form ();
echo $f->begin(array('onsubmit'=>'Form.check()'));
echo $f->input (array(
    'type'=>'select',
    'name'=>'database',
    'label'=>'Base de datos',
    'options'=>$databases,
    'check'=>'notempty',
    'help'=>'Nombre de la base de datos definida dentro de la configuración.',
));
echo $f->input (array(
    'type'=>'checkbox',
    'name'=>'delete',
    'label'=>'Eliminar datos',
    'help'=>'Eliminar datos de cada tabla antes de agregar. Si no se elimina se actualizarán los registros de la BD que coincidan con las PKs del archivo.',
));
echo $f->input (array(
    'type'=>'file',
    'name'=>'file',
    'label'=>'Datos para poblar',
    'check'=>'notempty',
));
echo $f->end('Poblar tablas');
