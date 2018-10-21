<div class="text-center mt-4 mb-4">
    <a href="<?=$_base?>/"><img src="<?=$_base?>/img/logo.png" alt="Logo" class="img-fluid" style="max-width: 200px" /></a>
</div>

<div id="ht-tm-jumbotron">
    <div class="jumbotron bg-info text-white mb-0 radius-0 ht-tm-jumbotron">
        <div class="container">
            <div class="ht-tm-header">
                <h1 class="display-1 text-light mb-4">¡Lo sentimos!</h1>
                <p class="lead">Encontramos un problema y la aplicación dejó de ejecutarse.</p>
                <p class="lead">Por favor, verifique que la dirección web que lo trajo a esta página o los datos ingresados sean válidos.</p>
                <div class="mt-4">
                    <a href="<?=$_base?>/" data-toggle="ht-special-modal" data-target="#theme-download-modal" class="btn btn-success btn-pill btn-wide btn-lg mr-2 my-2 btn-ht-dl-message">
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
