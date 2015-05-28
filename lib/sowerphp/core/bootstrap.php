<?php

/**
 * SowerPHP: Minimalist Framework for PHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

/**
 * @file bootstrap.php
 * Archivo de arranque de la aplicación
 * @version 2014-03-22
 */

// Asignar nivel de error máximo (para reportes previo a que se asigne el valor
// real en Configure::bootstrap())
ini_set('display_errors', true);
error_reporting(E_ALL);

// Definir el tiempo de inicio del script
define('TIME_START', microtime(true));

// Definir directorio DIR_WEBSITE
define('DIR_WEBSITE', DIR_PROJECT.'/website');

// Iniciar buffer
ob_start();

// Incluir archivo de funciones básicas y clase para autoload
include DIR_FRAMEWORK.'/lib/sowerphp/core/basics.php';
include DIR_FRAMEWORK.'/lib/sowerphp/core/App.php';

// Asociar el método que cargará las clases
spl_autoload_register ('\sowerphp\core\App::loadClass');

// Crear capas de la aplicación (se registrarán extensiones)
\sowerphp\core\App::createLayers ($_EXTENSIONS);
unset ($_EXTENSIONS);

// Definir si la aplicación se ejecuta en ambiente de desarrollo
// Si estamos en Apache se debe definir en /etc/httpd/conf/httpd.conf:
//   SetEnv APPLICATION_ENV "dev".
// Si estamos en una terminal se debe pasar el flas: --dev
global $argv;
if ((isset($_SERVER['APPLICATION_ENV']) and $_SERVER['APPLICATION_ENV']=='dev')) {
    define('ENVIRONMENT_DEV', true);
} else if ((is_array($argv) and in_array('--dev', $argv))) {
    define('ENVIRONMENT_DEV', true);
    // se quita flasg --dev de los argumentos
    unset($argv[array_search('--dev', $argv)]);
}

// Iniciar sesión y configurar el sitio
\sowerphp\core\Configure::bootstrap();
\sowerphp\core\Model_Datasource_Session::start(\sowerphp\core\Configure::read('session.expires'));
\sowerphp\core\Model_Datasource_Session::configure();
