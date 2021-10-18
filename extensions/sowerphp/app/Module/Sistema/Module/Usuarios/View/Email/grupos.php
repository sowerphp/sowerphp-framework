<div class="page-header"><h1>Sistema &raquo; Usuarios &raquo; Enviar email a grupos</h1></div>
<?php
$f = new \sowerphp\general\View_Helper_Form();
echo $f->begin(['onsubmit'=>'Form.check() && Form.confirm(this, \'¿Enviar email?\')']);
echo $f->input([
    'type' => 'tablecheck',
    'id' => 'grupos',
    'label' => 'Destinatarios',
    'titles' => ['Grupo'],
    'table' => $grupos,
    'display-key' => false,
    'check' => 'notempty',
]);
echo $f->input([
    'type' => 'select',
    'name' => 'enviar_como',
    'label' => 'Enviar como',
    'options' => ['bcc'=>'BCC: copia oculta', 'cc'=>'CC: copia'],
    'check' => 'notempty',
]);
echo $f->input([
    'name' => 'asunto',
    'label' => 'Asunto',
    'help' => 'Se incluirá automáticamente "['.$page_title.'] " al inicio del asunto',
    'check' => 'notempty',
]);
echo $f->input([
    'type' => 'textarea',
    'name' => 'mensaje',
    'label' => 'Mensaje',
    'check' => 'notempty',
    'help' => 'Se adjuntará listado de grupos y firma (con nombre y correo del usuario más la URL de la aplicación) de forma automática al final del mensaje',
]);
echo $f->input([
    'type' => 'js',
    'name' => 'adjuntos',
    'label' => 'Adjuntos',
    'titles' => ['Archivo adjunto'],
    'inputs' => [['type'=>'file', 'name'=>'adjuntos']],
    'help' => 'Archivos adjuntos que se enviarán con el correo a los usuarios',
]);
echo $f->input([
    'name' => 'agrupar',
    'label' => 'Agrupar',
    'value' => 0,
    'check' => 'notempty integer',
    'help' => 'Si el valor es diferente a 0 se agruparán los mensajes por esta cantidad de usuarios',
]);
echo $f->end('Enviar email a usuarios de grupos seleccionados');
