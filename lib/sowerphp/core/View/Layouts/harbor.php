<!--
Framework: SowerPHP (https://sowerphp.org)
Layout: harbor (https://hackerthemes.com)
-->
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title><?=$_header_title?></title>
        <link rel="shortcut icon" href="<?=$_base?>/img/favicon.png" />
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.2.0/css/all.css">
        <link href="https://fonts.googleapis.com/css?family=Oswald|Raleway" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
        <link rel="stylesheet" href="<?=$_base?>/css/style.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>
        <script>
            var _url = "<?=$_url?>",
                _base = "<?=$_base?>",
                _request = "<?=$_request?>"
            ;
        </script>
        <script src="https://cdn.sasco.cl/js/__.min.js"></script>
        <script src="https://cdn.sasco.cl/js/form.min.js"></script>
<?php if (\sowerphp\core\App::layerExists('sowerphp/general')) : ?>
        <script src="https://cdn.sasco.cl/js/datepicker/bootstrap-datepicker.js"></script>
        <script src="https://cdn.sasco.cl/js/datepicker/bootstrap-datepicker.es.js"></script>
        <link rel="stylesheet" href="https://cdn.sasco.cl/js/datepicker/datepicker3.css" />
<?php endif; ?>
<?php if (\sowerphp\core\App::layerExists('sowerphp/app')) : ?>
        <script src="<?=$_base?>/js/app.js"></script>
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css">
        <script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css" />
        <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootbox@5.1.3/dist/bootbox.all.min.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<?php endif; ?>
<?php if (\sowerphp\core\Module::loaded('Sistema.Notificaciones')) : ?>
        <link rel="stylesheet" href="<?=$_base?>/sistema/notificaciones/css/style.css">
        <script src="<?=$_base?>/sistema/notificaciones/js/js.js"></script>
<?php endif; ?>
<?=$_header_extra?>
    </head>
    <body>
        <div class="bg-light">
        <nav class="container navbar navbar-expand-lg navbar-light">
            <a class="navbar-brand" href="<?=$_base?>/"><?=$_body_title?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto">
<?php
$dropdown_id_count = 1;
foreach ($_nav_website as $link=>$name) {
    $active = $_page == $link ? ' active' : '';
    if ($link[0]=='/') $link = $_base.$link;
    if (isset($name['nav'])) {
        $dropdown_id = 'dropdown_'.$dropdown_id_count++;
        $title = isset($name['desc']) ? $name['desc'] : (isset($name['title']) ? $name['title'] : '');
        $icon = isset($name['icon']) ? '<span class="'.$name['icon'].'"></span> ' : '';
        echo '                        <li class="nav-item dropdown',$active,'">',"\n";
        echo '                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-expanded="false" id="',$dropdown_id,'" title="',$title,'">',$icon,$name['name'],' <span class="caret"></span></a>',"\n";
        echo '                            <div class="dropdown-menu" aria-labelledby="',$dropdown_id,'">',"\n";
        foreach($name['nav'] as $l=>$n) {
            if ($l[0]=='/') $l = $link.$l;
            echo '                                <a href="',$l,'" class="dropdown-item">',$n,'</a>',"\n";
        }
        echo '                            </div>',"\n";
        echo '                        </li>',"\n";
    } else {
        if (is_array($name)) {
            $title = isset($name['desc']) ? $name['desc'] : (isset($name['title']) ? $name['title'] : '');
            $icon = isset($name['icon']) ? '<span class="'.$name['icon'].'"></span> ' : '';
            $name = $name['name'];
        } else $title = $icon = '';
        echo '                        <li class="nav-item'.$active.'"><a href="',$link,'" title="',$title,'" class="nav-link">',$icon,$name,'</a></li>',"\n";
    }
}
?>
                </ul>
<?php if (\sowerphp\core\App::layerExists('sowerphp/app')) : ?>
                    <ul class="nav navbar-nav navbar-end">
<?php if (!$_Auth->logged()) : ?>
                        <li class="nav-item"><a href="<?=$_base?>/usuarios/ingresar" class="nav-link"><span class="fas fa-sign-in-alt" aria-hidden="true"></span> Iniciar sesión</a></li>
<?php else : ?>
<?php if (\sowerphp\core\Module::loaded('Sistema.Notificaciones')) : ?>
<?php
$Notficaciones = new \sowerphp\app\Sistema\Notificaciones\Model_Notificaciones();
$notificaciones = $Notficaciones->getUnreadByUser($_Auth->User->id);
$n_notificaciones = $Notficaciones->getCountUnreadByUser($_Auth->User->id);;
?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-expanded="false" id="dropdown_notifications">
                                <i class="far fa-bell"></i><?=($n_notificaciones?' <span class="badge bg-info" id="n_notifications">'.num($n_notificaciones).'</span>':'')?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end widget-notifications no-padding" aria-labelledby="dropdown_notifications" style="width: 300px">
                                <div class="notifications-list" id="main-navbar-notifications">
<?php foreach ($notificaciones as $n) : ?>
                                    <div class="notification" id="notification_<?=$n['id']?>">
                                        <div class="notification-title">
                                            <a href="#" onclick="notificacion_leida(<?=$n['id']?>); return false" title="Marcar como leída"><i class="fas fa-check-circle"></i></a>
                                            <?=$n['usuario']?>
                                        </div>
                                        <div class="notification-description">
                                            <?=$n['descripcion']?>
<?php if ($n['enlace']) : ?>
                                            <br />
                                            <a href="#" onclick="notificacion_abrir(<?=$n['id']?>); return false" title="Se abrirá y marcará como leída la notificación">Abrir enlace de la notificación</a>
<?php endif; ?>
                                        </div>
                                        <div class="notification-ago"><?=\sowerphp\general\Utility_Date::ago($n['fechahora'])?></div>
                                        <div class="notification-icon <?=$n['icono']?> bg-<?=$n['tipo']?> text-white rounded"></div>
                                    </div>
<?php endforeach; ?>
                                </div>
                                <a href="<?=$_base?>/sistema/notificaciones/notificaciones" class="notifications-link">Ver todas las notificaciones</a>
                            </div>
                        </li>
<?php endif; ?>
<?php
$Account = $_Auth->User->getEmailAccount();
if ($Account) {
    $emails = $Account->countUnreadMessages();
    echo '                        <li class="nav-item"><a href="'.$Account->getUserUrl().'" class="nav-link"><i class="far fa-envelope"></i> '.($emails?' <span class="badge bg-primary">'.num($emails).'</span>':'').'</a></li>',"\n";
}
?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-expanded="false" id="dropdown_menu"><strong>Menú <span class="caret"></span></strong></a>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown_menu">
<?php
foreach ($_nav_app as $link=>&$info) {
    if ($_Auth->check($link)) {
        if(!is_array($info)) $info = ['name'=>$info];
        echo '                                <a href="',$_base,$link,'" class="dropdown-item">',$info['name'],'</a>',"\n";
    }
}
?>
                                <div class="dropdown-divider"></div>
                                <?php /*<a href="<?=$_base?>/enlaces" class="dropdown-item"><span class="fa fa-bookmark" aria-hidden="true"></span> Enlaces</a>
                                <a href="<?=$_base?>/documentacion" class="dropdown-item"><span class="fa fa-book" aria-hidden="true"></span> Documentación</a>
                                <div class="dropdown-divider"></div>*/ ?>
                                <a href="<?=$_base?>/usuarios/perfil" class="dropdown-item"><span class="fa fa-user fa-fw" aria-hidden="true"></span> Perfil de usuario</a>
                                <a href="<?=$_base?>/usuarios/salir" class="dropdown-item"><span class="fas fa-sign-out-alt fa-fw" aria-hidden="true"></span> Cerrar sesión</a>
                            </div>
                        </li>
<?php endif; ?>
                    </ul>
<?php endif; ?>
            </div>
        </nav>
        </div>

        <div class="container main-container">
<!-- BEGIN MAIN CONTENT -->
<?php
// menú de módulos si hay sesión iniciada
if (\sowerphp\core\App::layerExists('sowerphp/app') and $_Auth->logged() and $_module_breadcrumb) {
    echo '<ol class="breadcrumb d-print-none">',"\n";
    $url = '/';
    foreach ($_module_breadcrumb as $link => &$name) {
        if (is_string($link)) {
            echo '    <li class="breadcrumb-item"><a href="',$_base,$url,$link,'">',$name,'</a></li>',"\n";
            $url .= $link.'/';
        } else {
            echo '    <li class="breadcrumb-item active">',$name,'</li>';
        }
    }
    echo '</ol>',"\n";
}
// mensaje de sesión
$messages = \sowerphp\core\Model_Datasource_Session::message();
foreach ($messages as $message) {
    $icons = [
        'success' => 'ok',
        'info' => 'info-sign',
        'warning' => 'warning-sign',
        'danger' => 'exclamation-sign',
    ];
    echo '<div class="alert alert-',$message['type'],' alert-dismissible fade show mt-4" role="alert">',"\n";
    echo '    <span class="glyphicon glyphicon-',$icons[$message['type']],'" aria-hidden="true"></span>',"\n";
    echo '    <span class="sr-only">',$message['type'],': </span>',$message['text'],"\n";
    echo '    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',"\n";
    echo '</div>'."\n";
}
// contenido de la página
echo $_content;
?>
<!-- END MAIN CONTENT -->
            <div class="clearfix"></div>
            <br/>
        </div>
        <footer class="footer d-print-none">
            <div class="container">
                <div class="float-start">
                    <?=(is_array($_footer)?$_footer['left']:$_footer)."\n"?>
                </div>
                <div class="float-end text-end">
<?=!empty($_footer['right'])?'                    '.$_footer['right'].'<br/>'."\n":''?>
<?php
if (isset($_Auth) and $_Auth->logged()) {
    echo '                    <span class="small">';
    echo 'time: ',round(microtime(true)-TIME_START, 2),' [s] - ';
    echo 'memory: ',round(memory_get_usage()/1024/1024,2),' [MiB] - ';
    echo 'querys: ',\sowerphp\core\Model_Datasource_Database_Manager::$querysCount,' - ';
    echo 'cache: ',\sowerphp\core\Cache::$setCount,'/',\sowerphp\core\Cache::$getCount,'</span>',"\n";
}
?>
                </div>
                <div class="clearfix"></div>
            </div>
        </footer>
    </body>
</html>
