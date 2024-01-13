<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
 * Copyright (C) SowerPHP <https://www.sowerphp.org>
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
 * Archivo de arranque de la aplicación
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
\sowerphp\core\App::createLayers($_EXTENSIONS);
unset ($_EXTENSIONS);

// autocarga de composer del framework
App::import(DIR_FRAMEWORK.'/vendor/autoload');

// configurar la aplicación e iniciar la sesión del usuario
\sowerphp\core\Configure::bootstrap();
\sowerphp\core\Model_Datasource_Session::start(\sowerphp\core\Configure::read('session.expires'));
\sowerphp\core\Model_Datasource_Session::configure();
