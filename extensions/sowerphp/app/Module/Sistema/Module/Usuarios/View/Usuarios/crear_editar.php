<?php if (isset($Obj)) : ?>
<ul class="nav nav-pills pull-end">
    <li class="nav-item">
        <a href="<?=$_base?>/sistema/usuarios/usuarios/salir_forzar/<?=$Obj->id?>" title="Forzar el cierre de la sesión del usuario" class="nav-link">
            <span class="fas fa-sign-out-alt"></span> Cerrar sesión
        </a>
    </li>
</ul>
<?php endif; ?>
<div class="page-header"><h1><?=$accion?> <?=$model?></h1></div>
<?php
// crear formulario
$form = new \sowerphp\general\View_Helper_Form();
echo $form->begin(array('onsubmit'=>'Form.check()'));

// atributos del usuario
echo $form->input([
    'name' => 'nombre',
    'label' => $columns['nombre']['name'],
    'value' => isset($Obj) ? $Obj->nombre : '',
    'help'  => $columns['nombre']['comment'],
    'check' => (!$columns['nombre']['null']?['notempty']:[]),
    'attr' => 'maxlength="'.$columns['nombre']['length'].'"',
]);
echo $form->input([
    'name' => 'usuario',
    'label' => $columns['usuario']['name'],
    'value' => isset($Obj) ? $Obj->usuario : '',
    'help'  => $columns['usuario']['comment'],
    'check' => (!$columns['usuario']['null']?['notempty']:[]),
    'attr' => 'maxlength="'.$columns['usuario']['length'].'"',
]);
if (is_array($ldap) and isset($ldap['person_uid']) and $ldap['person_uid']=='usuario_ldap') {
    echo $form->input([
        'name' => 'usuario_ldap',
        'label' => $columns['usuario_ldap']['name'],
        'value' => isset($Obj) ? $Obj->usuario_ldap : '',
        'help'  => $columns['usuario_ldap']['comment'],
        'attr' => 'maxlength="'.$columns['usuario_ldap']['length'].'"',
    ]);
} else {
    echo $form->input([
        'type' => 'hidden',
        'name' => 'usuario_ldap',
    ]);
}
echo $form->input([
    'name' => 'email',
    'label' => $columns['email']['name'],
    'value' => isset($Obj) ? $Obj->email : '',
    'help'  => $columns['email']['comment'],
    'check' => 'notempty email',
    'attr' => 'maxlength="'.$columns['email']['length'].'"',
]);
echo $form->input([
    'type' => 'password',
    'name' => 'contrasenia',
    'label' => $columns['contrasenia']['name'],
    'help'  => $columns['contrasenia']['comment'],
]);
echo $form->input([
    'type' => 'select',
    'name' => 'activo',
    'label' => $columns['activo']['name'],
    'options' => [
        '' => 'Seleccione una opción',
        '1' => 'Si',
        '0' => 'No'
    ],
    'value' => isset($Obj) ? $Obj->activo : '',
    'help'  => $columns['activo']['comment'],
    'check' => (!$columns['activo']['null']?['notempty']:[]),
]);

// agregar campo para los grupos a los que pertenece el usuario
echo $form->input([
    'type'=>'tablecheck',
    'name'=>'grupos',
    'label'=>'Grupos',
    'titles'=>['GID', 'Grupo'],
    'table'=>$grupos,
    'checked'=>$grupos_asignados,
]);

// terminar formulario
echo $form->end('Guardar');
?>
<div style="float:left;color:red">* campo es obligatorio</div>
<div style="float:right;margin-bottom:1em;font-size:0.8em">
    <a href="<?=$_base.$listarUrl?>">Volver al listado de registros</a>
</div>
