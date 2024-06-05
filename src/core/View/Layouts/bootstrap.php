<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Proyecto con SowerPHP</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <?=$_header_extra?>
    </head>
    <body>
        <div class="container">
            <header class="d-flex flex-wrap justify-content-center py-3 mb-4 border-bottom">
                <a href="<?=$_base?>/inicio" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none">
                    <i class="bi bi-house me-2" width="40" height="32"></i>
                    <span class="fs-4">SowerPHP</span>
                </a>
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a href="<?=$_base?>/inicio" class="nav-link active" aria-current="page">
                            Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?=$_base?>/contacto" class="nav-link">
                            Contacto
                        </a>
                    </li>
                </ul>
            </header>
        </div>
        <div class="container">
            <?=\sowerphp\core\Facade_Session_Message::getMessagesAsString()?>
            <?=$_content;?>
        </div>
        <div class="container">
            <footer class="d-flex flex-wrap justify-content-between align-items-center py-3 my-4 border-top">
                <p class="col-md-4 mb-0 text-body-secondary">
                    &copy; <a href="https://www.sowerphp.org">SowerPHP</a>
                    2014 - <?=date('Y')?>
                </p>
                <a href="/" class="col-md-4 d-flex align-items-center justify-content-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none">
                    <svg class="bi me-2" width="40" height="32"><use xlink:href="#bootstrap"/></svg>
                </a>
                <ul class="nav col-md-4 justify-content-end">
                    <li class="nav-item">
                        <a href="<?=$_base?>/inicio" class="nav-link px-2 text-body-secondary">
                            Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?=$_base?>/contacto" class="nav-link px-2 text-body-secondary">
                            Contacto
                        </a>
                    </li>
                </ul>
            </footer>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    </body>
</html>
