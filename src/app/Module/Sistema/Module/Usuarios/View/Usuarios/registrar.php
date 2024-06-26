<?php $__view_title = 'Registrar Nuevo Usuario'; ?>
<div class="container">
    <div class="text-center mt-4 mb-4">
        <a href="<?=$_base?>/"><img src="<?=$_base?>/img/logo.png" alt="Logo" class="img-fluid" style="max-width: 200px" /></a>
    </div>
    <div class="row">
        <div class="offset-md-3 col-md-6">
            <?=\sowerphp\core\Facade_Session_Message::getMessagesAsString()?>
            <div class="card">
                <div class="card-body">
                    <h1 class="text-center mb-4">Crear cuenta de usuario</h1>
                    <form action="<?=$_base?>/usuarios/registrar" method="post" onsubmit="return Form.check()" class="mb-4" id="registrarForm">
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
                        <label class="lead mb-4">
                            <input type="checkbox" name="terms_ok" required="required" onclick="this.value = this.checked ? 1 : 0" /> Acepto los <a href="<?=$terms?>" target="_blank">términos y condiciones de uso</a> del servicio.
                        </label>
<?php endif; ?>
                        <?=\sowerphp\general\Utility_Google_Recaptcha::form('registrarForm')?>
                        <button type="submit" class="btn btn-primary btn-lg col-12">Registrar usuario</button>
                    </form>
                    <p class="text-center lead">
                        <i class="fas fa-exclamation-circle text-muted"></i>
                        La contraseña para acceder será enviada a su correo
                        <i class="fas fa-exclamation-circle text-muted"></i>
                    </p>
                </div>
            </div>
            <p class="text-center mt-4"><a href="<?=$_base?>/usuarios/ingresar">Ingresar con una cuenta de usuario existente</a></p>
        </div>
    </div>
    <script> $(function() { $("#name").focus(); }); </script>
</div>
