#!/usr/bin/php -q
<?php

/**
 * SowerPHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

/**
 * @file shell.php
 * Dispatcher para la shell
 * @version 2014-12-17
 */

// Directorio de instalación de SowerPHP, En caso de una instalación compartida
// se debe modificar esta definición indicando el directorio donde está
// instalado el framework, ejemplo: /usr/share/sowerphp
define ('DIR_FRAMEWORK', dirname(dirname(dirname(dirname(__FILE__)))));

// Directorio que contiene el proyecto (directorio project) ¡no modificar!
define ('DIR_PROJECT', dirname(dirname(dirname(__FILE__))));

// Extensiones que se utilizarán. Deberá ser vendor/extensión dentro de
// DIR_FRAMEWORK/extensions o bien dentro de DIR_PROJECT/extensions, ejemplo:
// $_EXTENSIONS = ['sowerphp/app', 'sowerphp/general'];
$_EXTENSIONS = [];

// Iniciar bootstrap (proceso que prepara e inicia el proyecto)
if (!@include(DIR_FRAMEWORK.'/lib/sowerphp/core/bootstrap.php')) {
    echo 'Bootstrap no ha podido ser ejecutado, verificar DIR_FRAMEWORK ',
        'en ',DIR_PROJECT,'/website/Shell/shell.php',"\n"
    ;
    exit(1);
}

// Despachar/ejecutar la shell
exit(\sowerphp\core\Shell_Exec::run($argv));
