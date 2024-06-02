<?php $__block_title = 'Recuperar Contraseña'; ?>
<div class="container">
    <div class="text-center mt-4 mb-4">
        <a href="<?=$_base?>/"><img src="<?=$_base?>/img/logo.png" alt="Logo" class="img-fluid" style="max-width: 200px" /></a>
    </div>
    <div class="row">
        <div class="offset-md-3 col-md-6">
<?php
$messages = \sowerphp\core\SessionMessage::flush();
foreach ($messages as $message) {
    $icons = [
        'success' => 'ok',
        'info' => 'info-sign',
        'warning' => 'warning-sign',
        'danger' => 'exclamation-sign',
    ];
    echo '<div class="alert alert-',$message['type'],'" role="alert">',"\n";
    echo '    <span class="glyphicon glyphicon-',$icons[$message['type']],'" aria-hidden="true"></span>',"\n";
    echo '    <span class="visually-hidden">',$message['type'],': </span>',$message['text'],"\n";
    echo '    <a href="#" class="btn-close" data-bs-dismiss="alert" aria-label="close" title="Cerrar">&times;</a>',"\n";
    echo '</div>'."\n";
}
?>
            <div class="card">
                <div class="card-body">
                    <h1 class="text-center mb-4">Recuperar contraseña</h1>
                    <form action="<?=$_base?>/usuarios/contrasenia/recuperar" method="post" onsubmit="return Form.check()" class="mb-4" id="recuperarForm">
                        <div class="form-group">
                            <label for="user" class="visually-hidden">Usuario</label>
                            <input type="text" name="id" id="user" class="form-control form-control-lg mb-3" required="required" placeholder="Usuario o correo electrónico">
                        </div>
                        <?=\sowerphp\general\Utility_Google_Recaptcha::form('recuperarForm')?>
                        <button type="submit" class="btn btn-primary btn-lg col-12">Solicitar email para recuperar la contraseña</button>
                    </form>
                </div>
            </div>
            <p class="text-center mt-4">Si la cuenta se encuentra bloqueada, esta opción permite desbloquear la cuenta.</p>
        </div>
    </div>
    <script> $(function() { $("#user").focus(); }); </script>
</div>
