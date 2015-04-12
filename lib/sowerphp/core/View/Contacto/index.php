<h1>Contacto</h1>
<p>Por favor enviar su mensaje a través del siguiente formulario, será contactado a la brevedad.</p>
<form method="post" class="form-horizontal" role="form" onsubmit="return Form.check()">
    <div class="form-group required">
        <label for="nombre" class="col-sm-2 control-label">Nombre</label>
        <div class="col-sm-10">
            <input type="text" name="nombre" class="form-control check notempty" id="nombre" />
        </div>
    </div>
    <div class="form-group required">
        <label for="correo" class="col-sm-2 control-label">Correo electrónico</label>
        <div class="col-sm-10">
            <input type="text" name="correo" class="form-control check notempty email" id="correo" />
        </div>
    </div>
    <div class="form-group required">
        <label for="mensaje" class="col-sm-2 control-label">Mensaje</label>
        <div class="col-sm-10">
            <textarea name="mensaje" class="form-control check notempty" id="mensaje"></textarea>
        </div>
    </div>
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
            <button type="submit" name="submit" class="btn btn-default">Enviar mensaje</button>
        </div>
    </div>
</form>
