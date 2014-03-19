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

// se utilizará el controlador de errores
App::uses('ErrorController', 'Controller');

/**
 * Clase que permite manejar las excepciones que aparecen durante
 * la ejecución de la aplicación.
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-02-20
 */
class ExceptionHandler {

	protected static $controller; ///< Controlador
	
	/**
	 * Método para manejar las excepciones ocurridas en la aplicación
	 * @param exception Excepción producida
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-02-20
	 */
	public static function handle (Exception $exception) {
		ob_clean();
		// Generar arreglo
		$data = array(
			'exception' => get_class($exception),
			'message' => $exception->getMessage(),
			'trace' => $exception->getTraceAsString()
		);
		// si existe Dispatcher es una web
		if (class_exists('Dispatcher')) {
			self::$controller = new ErrorController(new Request(), new Response());
			self::$controller->error_reporting = Configure::read('debug');
			self::$controller->display($data);
		}
		// si no existe será una Shell
		else {
			$stdout = new ConsoleOutput('php://stdout');
			$stdout->write("\n".'<error>'.$data['exception'].':</error>', 2);
			$stdout->write("\t".'<error>'.str_replace("\n", "\n\t", $data['message']).'</error>', 2);
			$stdout->write("\t".'<error>'.str_replace("\n", "\n\t", $data['trace']).'</error>', 2);
		}
	}

}
