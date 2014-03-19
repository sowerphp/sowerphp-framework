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
 * Clase para cargar otras clases y/o archivos
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-15
 */
class App {

	protected static $_paths = array(); ///< Rutas donde se buscarán los archivos de las clases
	protected static $_classMap = array(); ///< Mapa de clases y su ubicación

	/**
	 * Asigna las rutas donde se deberán buscar las clases
	 * @param paths Rutas que se cargarán
	 * @return Las rutas actualmente registradas
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-09-14
	 */
	public static function paths ($paths = null) {
		if($paths != null)
			self::$_paths = array_merge(self::$_paths, $paths);
		return self::$_paths;
	}

	/**
	 * Indica que se usará una determinada clase y su ubicación dentro
	 * de alguna de las rutas
	 * @param className Nombre de la clase que se está registrando
	 * @param location Ubicación de la clase
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-11-20
	 */
	public static function uses ($className, $location) {
		// Si hay puntos es un módulo
		if(strpos($location, '.')!==false) {
			$parts = explode('.', $location);
			$directory = array_pop($parts);
			$location = 'Module/'.implode('/Module/', $parts).'/'.$directory;
		}
		// agregar clase
		self::$_classMap[$className] = $location;
	}
	
	/**
	 * Busca el archivo de la clase indicada en las posibles rutas y
	 * lo carga.
	 * @param className Nombre de la clase que se desea cargar
	 * @return Verdadero si se pudo cargar la clase (falso si no)
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-11-13
	 */
	public static function load ($className, $buscarSingular = true, $buscarSinS = true) {
		// Si la clase ya esta cargada se retorna verdadero
		if(class_exists($className)) return true;
		// Ver si la clase existe en el mapa
		if (!isset(self::$_classMap[$className])) {
			 $encontradaComoSingular = $encontradaSinS = false;
			// Si no la encuentra, podría ser porque es una clase plural, entonces se busca como singular
			if($buscarSingular) {
				 $encontradaComoSingular = self::load(Inflector::singularize($className), false, false);
			}
			// si no se encuentra como singular buscar si la "s" final, si existe
			// esto ya que algunas reglas del inflector no transforman bien (parche?)
			if (!$encontradaComoSingular && $buscarSinS && $className[strlen($className)-1]=='s') {
				$encontradaSinS = self::load(substr($className, 0, -1), false, false);
			}
			// Si ya se busco como singular (o sea esta es la segunda pasada) y no esta mapeada se retorna falso
			return $encontradaComoSingular || $encontradaSinS;
		}
		// Buscar el archivo de la clase en las posibles rutas
		$location = self::$_classMap[$className];
		foreach (self::$_paths as $path) {
			$file = $path.'/'.$location.'/'.$className.'.php';
			if (file_exists($file)) {
				return include $file;
			}
		}
		// Si se llego aqui (no se encontro la clase) y si se busca el controlador del Modulo se usa el por defecto (general)
		if($className=='ModuleController') {
			foreach (self::$_paths as $path) {
				$file = $path.'/Controller/ModuleController.php';
				if (file_exists($file)) {
					return include $file;
				}
			}
		}
		// Si no se encontró el archivo de la clase se retorna falso
		return false;
	}
	
	/**
	 * Método para importar (usando include)
	 * Si se pasa una ruta absoluta se incluirá solo ese archivo si
	 * existe, si es relativa se buscará en los posibles paths
	 * @param archivo Archivo que se desea incluir (sin extensión .php)
	 * @return Verdadero si se pudo incluir el archivo (falso si no)
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-29
	 */
	public static function import ($archivo) {
		// Si es una ruta absoluta
		if($archivo[0]=='/' || strpos($archivo, ':\\')) {
			if (file_exists($archivo.'.php')) {
				return include_once $archivo.'.php';
			}
			return false;
		}
		// Buscar el archivo en las posibles rutas
		foreach (self::$_paths as $path) {
			$file = $path.'/'.$archivo.'.php';
			if (file_exists($file)) {
				return include_once $file;
			}
		}
		// Si no se encontró el archivo se retorna falso
		return false;
	}
	
	/**
	 * Método que entrega la ubicación real de un archivo (busca en los
	 * posibles paths)
	 * @param archivo Archivo que se está buscando
	 * @return Ruta del archivo si fue encontrado (falso si no)
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-09-14
	 */
	public static function location ($archivo) {
		foreach (self::$_paths as $path) {
			$file = $path.'/'.$archivo;
			if (file_exists($file)) {
				return $file;
			}
		}
		return false;
	}
	
}
