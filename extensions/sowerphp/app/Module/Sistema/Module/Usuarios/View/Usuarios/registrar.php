<?php $__block_title = 'Registrar Nuevo Usuario'; ?>
<div class="container">
    <div class="text-center mt-4 mb-4">
        <a href="<?=$_base?>/"><img src="<?=$_base?>/img/logo.png" alt="Logo" class="img-fluid" style="max-width: 200px" /></a>
    </div>
    <div class="row">
        <div class="offset-md-3 col-md-6">
<?php
$messages = \sowerphp\core\Model_Datasource_Session::message();
foreach ($messages as $message) {
    $icons = [
        'success' => 'ok',
        'info' => 'info-sign',
        'warning' => 'warning-sign',
        'danger' => 'exclamation-sign',
    ];
    echo '<div class="alert alert-',$message['type'],'" role="alert">',"\n";
    echo '    <span class="glyphicon glyphicon-',$icons[$message['type']],'" aria-hidden="true"></span>',"\n";
    echo '    <span class="sr-only">',$message['type'],': </span>',$message['text'],"\n";
    echo '    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="Cerrar">&times;</a>',"\n";
    echo '</div>'."\n";
}
?>
            <div class="card">
                <div class="card-body">
                    <h1 class="text-center mb-4">Crear cuenta</h1>
                    <form action="<?=$_base?>/usuarios/registrar" method="post" onsubmit="return Form.check()" class="mb-4">
                        <div class="form-group">
                            <label for="name" class="sr-only">Nombre</label>
                            <input type="text" name="nombre" id="name" class="form-control form-control-lg" required="required" placeholder="Nombre completo" maxlength="50" />
                        </div>
                        <div class="form-group">
                            <label for="user" class="sr-only">Usuario</label>
                            <input type="text" name="usuario" id="user" class="form-control form-control-lg" required="required" placeholder="Usuario" maxlength="30" />
                        </div>
                        <div class="form-group">
                            <label for="email" class="sr-only">Correo electrónico</label>
                            <input type="email" name="email" id="email" class="form-control form-control-lg" required="required" placeholder="Correo electrónico" maxlength="50" />
                        </div>
<?php if (!empty($terms)) : ?>
                        <label>
                            <input type="checkbox" name="terms_ok" required="required" onclick="this.value = this.checked ? 1 : 0" /> Acepto los <a href="<?=$terms?>" target="_blank">términos y condiciones de uso</a> del servicio
                        </label>
<?php endif; ?>
<?php if (!empty($public_key)) : ?>
                        <div class="g-recaptcha mb-3" data-sitekey="<?=$public_key?>" style="width:304px;margin:0 auto"></div>
                        <script type="text/javascript" src="https://www.google.com/recaptcha/api.js?hl=<?=$language?>"></script>
<?php endif; ?>
                        <button type="submit" class="btn btn-primary btn-block btn-lg">Registrar usuario</button>
                    </form>
                    <p class="text-center small">(la contraseña será enviada a su email)</p>
                </div>
            </div>
            <p class="text-center small mt-4">¿ya tiene una cuenta? <a href="<?=$_base?>/usuarios/ingresar">¡inicie sesión!</a></p>
        </div>
    </div>
    <script> $(function() { $("#name").focus(); }); </script>
</div>
