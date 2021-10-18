<div class="page-header"><h1>Sistema &raquo; Notificaciones</h1></div>
<?php
foreach($notificaciones as &$n) {
    $n['icono'] = '<i class="'.$n['icono'].' fa-fw bg-'.$n['tipo'].' text-white rounded" style="padding:5px"></i>';
    $n['fechahora'] = \sowerphp\general\Utility_Date::ago($n['fechahora']);
    $n[] = $n['enlace'] ? ('<a href="'.$_base.'/sistema/notificaciones/notificaciones/abrir/'.$n['id'].'" title="Se abrirá y marcará como leída la notificación" class="btn btn-default"><i class="fa fa-search"></i></a>') : '';
    $n['leida'] = $n['leida'] ? 'Si' : '<input type="checkbox" value="'.$n['id'].'" onchange="notificacion_leida_checkbox(this)" />';
    unset($n['id'], $n['tipo'], $n['enlace']);
}
array_unshift($notificaciones, ['', 'Cuándo', 'Usuario', 'Mensaje', 'Leída', 'Enlace']);
new \sowerphp\general\View_Helper_Table($notificaciones);
