<div class="text-center mt-4 mb-4">
    <a href="<?=$_base?>/"><img src="<?=$_base?>/img/logo.png" alt="Logo" class="img-fluid" style="max-width: 200px" /></a>
</div>
<div class="h-100 p-5 bg-body-tertiary border rounded-3 mb-4">
    <h2 class="mb-4">¡Lo sentimos!</h2>
    <p>Encontramos un problema y la aplicación dejó de ejecutarse.</p>
    <?php if (!empty($exception) and !empty($message)): ?>
        <div class="card mb-4">
            <div class="card-header text-muted"><?=$exception?></div>
            <div class="card-body"><pre class="text-dark"><?=$message?></pre></div>
        </div>
    <?php endif; ?>
    <?php if (!empty($trace)): ?>
        <div class="card mb-4">
            <div class="card-header text-muted">Traza del error</div>
            <div class="card-body"><pre class="text-dark"><?=$trace?></pre></div>
        </div>
    <?php endif; ?>
    <p>Por favor, verifique que la dirección web que lo trajo a esta página o los datos ingresados sean válidos.</p>
    <a href="<?=$_base?>/" class="btn btn-primary" type="button">Volver a la página principal</a>
    <?php if ($soporte): ?>
        <a href="<?=$_base?>/contacto" class="btn btn-outline-primary">¡Solicitar ayuda!</a>
    <?php endif; ?>
</div>
