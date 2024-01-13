<script type="text/javascript">
$(function() {
    var url = document.location.toString();
    if (url.match('#')) {
        $('#'+url.split('#')[1]+'-tab').tab('show');
        $('html,body').scrollTop(0);
    }
});
</script>
<div class="page-header"><h1>Mi perfil de usuario (<?=$_Auth->User->usuario?>)</h1></div>
<div role="tabpanel">
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item"><a href="#datos" aria-controls="datos" role="tab" data-bs-toggle="tab" id="datos-tab" class="nav-link active" aria-selected="true">Datos básicos</a></li>
        <li class="nav-item"><a href="#contrasenia" aria-controls="contrasenia" role="tab" data-bs-toggle="tab" id="contrasenia-tab" class="nav-link">Contraseña</a></li>
        <li class="nav-item"><a href="#auth" aria-controls="auth" role="tab" data-bs-toggle="tab" id="auth-tab" class="nav-link">Auth</a></li>
    </ul>
    <div class="tab-content pt-4">
        <div role="tabpanel" class="tab-pane active" id="datos" aria-labelledby="datos-tab">
            <div class="row">
                <div class="col-sm-9">
                    <p>Aquí puede modificar los datos de su usuario.</p>
<?php
$form = new \sowerphp\general\View_Helper_Form();
echo $form->begin(array(
    'id' => 'datosUsuario',
    'onsubmit' => 'Form.check(\'datosUsuario\')'
));
echo $form->input(array(
    'name' => 'nombre',
    'label' => 'Nombre',
    'value' => $_Auth->User->nombre,
    'help' => 'Nombre real del usuario',
    'check' => 'notempty',
    'attr' => 'maxlength="50"',
));
if ($changeUsername) {
    echo $form->input(array(
        'name' => 'usuario',
        'label' => 'Usuario',
        'value' => $_Auth->User->usuario,
        'help' => 'Nombre de usuario',
        'check' => 'notempty',
        'attr' => 'maxlength="30"',
    ));
}
echo $form->input(array(
    'name' => 'email',
    'label' => 'Email',
    'value' => $_Auth->User->email,
    'help' => 'Correo electrónico para uso dentro del sistema',
    'check' => 'notempty email',
    'attr' => 'maxlength="50"',
));
echo $form->input(array(
    'type' => 'password',
    'name' => 'hash',
    'label' => 'Hash',
    'value' => $_Auth->User->hash,
    'help' => 'Código único para identificar el usuario (32 caracteres).<br />Si desea uno nuevo, borrar este y automáticamente se generará uno nuevo al guardar los cambios',
    'attr' => 'maxlength="32" autocomplete="off" onclick="this.select()"',
));
echo $form->input(array(
    'type' => 'password',
    'name' => 'api_key',
    'label' => 'API key',
    'value' => base64_encode($_Auth->User->hash.':X'),
    'help' => 'Valor de la cabecera Authorization de HTTP para autenticar en la API usando sólo la API key, la cual está basada en el hash del usuario',
    'attr' => 'readonly="readonly" onclick="this.select()"',
));
if ($_Auth->User->getLdapPerson() && $_Auth->User->getLdapPerson()->uid != $_Auth->User->usuario) {
    echo $form->input(array(
        'type' => 'div',
        'label' => 'Usuario LDAP',
        'value' => $_Auth->User->getLdapPerson()->uid,
        'help' => 'Usuario LDAP asociado a la cuenta de usuario',
    ));
}
if ($_Auth->User->getEmailAccount() && $_Auth->User->getEmailAccount()->getEmail() != $_Auth->User->email) {
    echo $form->input(array(
        'type' => 'div',
        'label' => 'Email oficial',
        'value' => $_Auth->User->getEmailAccount()->getEmail(),
        'help' => 'Correo electrónico oficial del usuario',
    ));
}
echo $form->end(array(
    'name' => 'datosUsuario',
    'value' => 'Guardar cambios',
));
?>
                </div>
                <div class="col-sm-3 text-center">
                    <a href="https://gravatar.com" title="Cambiar imagen en Gravatar">
                        <img src="<?=$_Auth->User->getAvatar(200)?>" alt="Avatar" class="img-fluid img-thumbnail" />
                    </a>
                    <div class="small" style="margin-top:0.5em">
                        <a href="https://gravatar.com" title="Cambiar imagen en Gravatar">[cambiar imagen]</a>
                    </div>
                </div>
            </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="contrasenia" aria-labelledby="contrasenia-tab">
            <p>A través del siguiente formulario puede cambiar su contraseña.</p>
<?php
echo $form->begin(array(
    'id' => 'cambiarContrasenia',
    'onsubmit' => 'Form.check(\'cambiarContrasenia\')'
));
echo $form->input(array(
    'type' => 'password',
    'name' => 'contrasenia',
    'label' => 'Contraseña actual',
    'help' => 'Contraseña actualmente usada por el usuario',
    'check' => 'notempty',
));
echo $form->input(array(
    'type' => 'password',
    'name' => 'contrasenia1',
    'label' => 'Contraseña nueva',
    'help' => 'Contraseña que se quiere utilizar',
    'check' => 'notempty',
));
echo $form->input(array(
    'type' => 'password',
    'name' => 'contrasenia2',
    'label' => 'Repetir contraseña',
    'help' => 'Repetir la contraseña que se haya indicado antes',
    'check' => 'notempty',
));
echo $form->end(array(
    'name' => 'cambiarContrasenia',
    'value'=>'Cambiar contraseña',
));
?>
        </div>
        <div role="tabpanel" class="tab-pane" id="auth" aria-labelledby="auth-tab">
<?php if ($auths2) : foreach ($auths2 as $Auth2) : ?>
            <div class="card mb-4">
                <div class="card-header"><?=$Auth2->getName()?></div>
                <div class="card-body">
<?php
$method = $Auth2->getName();
if (!$_Auth->User->{'config_auth2_'.$method}) {
    echo '<p>Aquí podrá activar <a href="',$Auth2->getUrl(),'" target="_blank">',$Auth2->getName(),'</a> para proteger el acceso a su cuenta.</p>',"\n";
    echo $form->begin([
        'id' => 'crearToken'.$Auth2->getName(),
        'onsubmit' => 'Form.check(\'crearToken'.$Auth2->getName().'\')'
    ]);
    echo $form->input(array(
        'type' => 'hidden',
        'name' => 'auth2',
        'value' => $Auth2->getName(),
    ));
    $secret = $Auth2->createSecret($_Auth->User->usuario);
    if ($secret) {
        echo $form->input(array(
            'type' => 'hidden',
            'name' => 'secret',
            'value' => $secret->text,
        ));
        echo $form->input(array(
            'type' => 'div',
            'name' => 'secretDisplay',
            'label' => 'Código pareo',
            'value' => '<img src="'.$secret->qr.'" class="img-fluid img-thumbnail" alt="QR para '.$Auth2->getName().'" />',
            'help' => 'Escanear el código QR o copiar el siguiente código en '.$Auth2->getName().': '.$secret->text,
        ));
    }
    echo $form->input(array(
        'name' => 'verification',
        'label' => 'Código verificación',
        'help' => 'Código de verificación para parear aplicación y proteger con '.$Auth2->getName(),
        'check' => 'notempty',
    ));
    echo $form->end([
        'name' => 'crearAuth2',
        'value' => 'Proteger cuenta con '.$Auth2->getName(),
    ]);
} else {
    echo '<p>Aquí podrá desasociar su cuenta de usuario con la protección entregada por <a href="',$Auth2->getUrl(),'" target="_blank">',$Auth2->getName(),'</a>.</p>',"\n";
    echo $form->begin([
        'onsubmit' => 'Form.confirm(this, \'¿Está seguro de querer eliminar la protección con '.$Auth2->getName().'?\')'
    ]);
    echo $form->input(array(
        'type' => 'hidden',
        'name' => 'auth2',
        'value' => $Auth2->getName(),
    ));
    echo $form->input(array(
        'type' => 'hidden',
        'name' => 'destruirAuth2',
        'value' => 1,
    ));
    echo $form->end('Eliminar protección con '.$Auth2->getName());
}
?>
                </div>
            </div>
<?php endforeach; endif; ?>
            <div class="card mb-4">
                <div class="card-header">Código QR autenticación</div>
                <div class="card-body">
                    <p>El siguiente código QR provee la dirección de la aplicación junto con su <em>hash</em> de usuario para autenticación.</p>
                    <a href="#" onclick="$('#auth_qr').show(); $('#auth_qr_show').hide(); $('#auth_qr_hide').show(); return false;" class="btn btn-primary" id="auth_qr_show">Ver código QR</a>
                    <a href="#" onclick="$('#auth_qr').hide(); $('#auth_qr_hide').hide(); $('#auth_qr_show').show(); return false;" class="btn btn-primary" style="display:none" id="auth_qr_hide">Ocultar código QR</a>
                    <br/><br/>
                    <img src="<?=$_base?>/exportar/qrcode/<?=$qrcode?>" alt="auth_qr" class="img-fluid img-thumbnail" style="display:none" id="auth_qr" />
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header">Grupos y permisos</div>
                <div class="card-body">
<?php
$grupos = $_Auth->User->groups();
if ($grupos) {
    echo '<p>Los siguientes son los grupos a los que usted pertenece:</p>',"\n";
    echo '<ul>',"\n";
    foreach ($grupos as &$grupo)
        echo '<li>',$grupo,'</li>';
    echo '</ul>',"\n";
    echo '<p>A través de estos grupos, tiene acceso a los siguientes recursos:</p>',"\n";
    echo '<ul>',"\n";
    foreach ($_Auth->User->auths() as &$auth)
        echo '<li>',$auth,'</li>';
    echo '</ul>',"\n";
} else {
    echo '<p>No pertenece a ningún grupo.</p>',"\n";
}
?>
                </div>
            </div>
        </div>

    </div>
</div>
