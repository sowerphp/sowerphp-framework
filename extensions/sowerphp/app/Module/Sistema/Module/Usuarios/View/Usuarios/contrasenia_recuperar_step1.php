<?php $__block_title = 'Recuperar Contraseña'; ?>
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
                    <h1 class="text-center mb-4">Reiniciar contraseña</h1>
                    <form action="<?=$_base?>/usuarios/contrasenia/recuperar" method="post" onsubmit="return Form.check()" class="mb-4">
                        <div class="form-group">
                            <label for="user" class="sr-only">Usuario</label>
                            <input type="text" name="id" id="user" class="form-control form-control-lg" required="required" placeholder="Usuario o correo electrónico">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-lg">Solicitar email nueva contraseña</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script> $(function() { $("#user").focus(); }); </script>
</div>
