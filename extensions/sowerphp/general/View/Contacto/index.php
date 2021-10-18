<div class="page-header"><h1>Contacto</h1></div>
<p>Por favor enviar su mensaje a través del siguiente formulario, será contactado a la brevedad.</p>
<form method="post" class="form-horizontal" role="form" onsubmit="return Form.check()">
    <div class="form-group row required">
        <label for="nombre" class="col-sm-2 control-label text-right">Nombre</label>
        <div class="col-sm-10">
            <input type="text" name="nombre" class="form-control check notempty" id="nombre" required="required" value="<?=!empty($_POST['nombre'])?$_POST['nombre']:null?>" />
        </div>
    </div>
    <div class="form-group row required">
        <label for="correo" class="col-sm-2 control-label text-right">Correo electrónico</label>
        <div class="col-sm-10">
            <input type="email" name="correo" class="form-control check notempty email" id="correo" required="required" value="<?=!empty($_POST['correo'])?$_POST['correo']:null?>" />
        </div>
    </div>
    <div class="form-group row required">
        <label for="mensaje" class="col-sm-2 control-label text-right">Mensaje</label>
        <div class="col-sm-10">
            <textarea name="mensaje" class="form-control check notempty" id="mensaje" rows="5" required="required"><?=!empty($_POST['mensaje'])?$_POST['mensaje']:null?></textarea>
        </div>
    </div>
<?php if (!empty($captcha_public_key)) : ?>
    <div class="form-group row required">
        <label for="mensaje" class="col-sm-2 control-label text-right"></label>
        <div class="col-sm-10">
            <div class="g-recaptcha mb-3" data-sitekey="<?=$captcha_public_key?>"></div>
            <script src="https://www.google.com/recaptcha/api.js?hl=<?=$language?>"></script>
        </div>
    </div>
<?php endif; ?>
    <div class="form-group row">
        <div class="offset-sm-2 col-sm-10">
            <button type="submit" name="submit" class="btn btn-primary">Enviar mensaje</button>
        </div>
    </div>
</form>
