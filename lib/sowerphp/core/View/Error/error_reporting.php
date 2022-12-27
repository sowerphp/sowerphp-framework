<div class="text-center mt-4 mb-4">
    <a href="<?=$_base?>/"><img src="<?=$_base?>/img/logo.png" alt="Logo" class="img-fluid" style="max-width: 200px" /></a>
</div>

<div id="ht-tm-jumbotron">
    <div class="bg-info text-white mb-0 radius-0">
        <div class="container">
            <div class="ht-tm-header">
                <h1 class="display-1 text-light mb-4">¡Lo sentimos!</h1>
                <p class="lead">Encontramos un problema y la aplicación dejó de ejecutarse.</p>
                <div class="card mb-4">
                    <div class="card-header text-muted"><?=$exception?></div>
                    <div class="card-body"><pre><?=$message?></pre></div>
                </div>
                <div class="card mb-4">
                    <div class="card-header text-muted">Traza del error</div>
                    <div class="card-body"><pre><?=$trace?></pre></div>
                </div>
                <p class="lead">Por favor, verifique que la dirección web que lo trajo a esta página o los datos ingresados sean válidos.</p>
                <div class="mt-4">
                    <a href="<?=$_base?>/" data-bs-toggle="ht-special-modal" data-bs-target="#theme-download-modal" class="btn btn-success btn-pill btn-wide btn-lg me-2 my-2 btn-ht-dl-message">
                        <span>Volver a la página principal</span>
                    </a>
<?php if ($soporte) : ?>
                    <a href="<?=$_base?>/contacto" class="btn btn-warning text-white btn-pill btn-wide btn-lg my-2">
                        <span>¡Solicitar ayuda!</span>
                    </a>
<?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
