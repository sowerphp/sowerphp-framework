<?php $__block_title = 'Ingresar a la Aplicación'; ?>
<div class="container">
    <div class="text-center mt-4 mb-4">
        <a href="<?=$_base?>/"><img src="<?=$_base?>/img/logo.png" alt="Logo" class="img-fluid" style="max-width: 200px" /></a>
    </div>
    <div class="row">
        <div class="offset-md-3 col-md-6">
            <?=\sowerphp\core\Facade_Session_Message::getMessagesAsString()?>
            <div class="card">
                <div class="card-body">
                    <h1 class="text-center mb-4">Ingresar</h1>
                    <form action="<?=$_base?>/usuarios/ingresar" method="post" onsubmit="return Form.check()" class="mb-4" id="ingresarForm">
                        <div class="form-group">
                            <label for="user" class="visually-hidden">Usuario</label>
                            <input type="text" name="usuario" id="user" class="form-control form-control-lg" required="required" placeholder="Usuario o correo electrónico" />
                        </div>
                        <div class="form-group">
                            <label for="pass" class="visually-hidden">Contraseña</label>
                            <input type="password" name="contrasenia" id="pass" class="form-control form-control-lg mt-3 mb-3" required="required" placeholder="Contraseña" />
                        </div>
<?php if ($auth2_token_enabled) : ?>
                        <div class="form-group">
                            <label for="auth2" class="visually-hidden">Token 2FA</label>
                            <input type="text" name="auth2_token" id="auth2" class="form-control form-control-lg" placeholder="Token 2FA si es necesario" autocomplete="off" />
                        </div>
<?php endif; ?>
                        <?=\sowerphp\general\Utility_Google_Recaptcha::form('ingresarForm')?>
                        <input type="hidden" name="redirect" value="<?=$redirect?>" />
                        <button type="submit" class="btn btn-primary btn-lg col-12">Iniciar sesión</button>
                    </form>
                    <p class="text-center small"><a href="<?=$_base?>/usuarios/contrasenia/recuperar">¿perdió su contraseña?</a></p>
                </div>
            </div>
<?php if ($self_register) : ?>
            <p class="text-center small mt-4">¿no tiene cuenta? <a href="<?=$_base?>/usuarios/registrar">¡regístrese!</a></p>
<?php endif; ?>
        </div>
    </div>
    <script> $(function() { $("#user").focus(); }); </script>
</div>
