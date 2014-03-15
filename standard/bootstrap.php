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
 * @version 2014-03-15
 */

// Asignar nivel de error máximo (para reportes previo a qugeneralese se asigne
// el valor real en Configure::bootstrap())
ini_set('display_errors', true);
error_reporting(E_ALL);

// Definir el tiempo de inicio del script
define('TIME_START', microtime(true));

// Definir directorios DIR_STANDARD y DIR_WEBSITE
define ('DIR_STANDARD', DIR_FRAMEWORK.'/standard');
define ('DIR_WEBSITE', DIR_PROJECT.'/website');

// iniciar buffer
ob_start();

// Incluir archivos genéricos 
include DIR_STANDARD.'/basics.php';
include DIR_STANDARD.'/Core/App.php';

// Asignar rutas/paths donde se buscarán las clases (en este mismo orden)
$_DIRS = array(DIR_WEBSITE);
foreach($_EXTENSIONS as &$_extension) {
	if ($_extension[0]!='/') {
		if (is_dir(DIR_PROJECT.'/extensions/'.$_extension)) {
			$_DIRS[] = DIR_PROJECT.'/extensions/'.$_extension;
		} else {
			$_DIRS[] = DIR_FRAMEWORK.'/extensions/'.$_extension;
		}
	} else {
		$_DIRS[] = $_extension;
	}
}
$_DIRS[] = DIR_STANDARD;
App::paths($_DIRS);
unset($_EXTENSIONS, $_DIRS, $_EXTENSIONS_DIR, $_extension);

// Asociar App::load como la función que cargará todas las clases
spl_autoload_register(__NAMESPACE__ .'\App::load');

// Agregar clases genéricas (usadas casi siempre)
App::uses('Configure', 'Core');
App::uses('Router', 'Routing');
App::uses('Inflector', 'Utility');
App::uses('Object', 'Core');
App::uses('Module', 'Core');
App::uses('I18n', 'I18n');

// Clases para manejar errores y excepciones
App::uses('MiException', 'Error');
App::uses('ExceptionHandler', 'Error');
App::uses('ErrorHandler', 'Error');
include DIR_STANDARD.'/Error/exceptions.php';

// Iniciar sesión y configurar el sitio
App::uses('Session', 'Model/Datasource');
Session::start ();
Configure::bootstrap ();
Session::configure ();
