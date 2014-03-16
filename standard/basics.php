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
 * @file basics.php
 * Archivo de funciones básicas para la aplicación
 * @version 2014-03-16
 */

/**
 * Función para mostrar el valor de una variable (y su tipo) o un objeto (y su
 * clase)
 * @param var Variable que se desea mostrar
 * @param withtype Si es verdadero se usará "var_dump" sino "print_r"
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-15
 */
function debug ($var, $withtype = false) {
	if (class_exists('Dispatcher')) echo '<pre>';
	if ($withtype) var_dump($var);
	else print_r($var);
	if (class_exists('Dispatcher')) echo '</pre>',"\n";
}

/**
 * Función para traducción de string singulares, en dominio master.
 * @param string Texto que se desea traducir
 * @param args Argumentos para reemplazar en el string, puede ser un arreglo o bien n argumentos a la función
 * @return Texto traducido
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-16
 */
function __ ($string, $args = null) {
	if (!is_array($args)) {
		$args = array_slice(func_get_args(), 1);
	}
	return __d ('master', $string, $args);
}

/**
 * Función para traducción de string singulares, eligiendo dominio.
 * @param string Texto que se desea traducir
 * @param args Argumentos para reemplazar en el string, puede ser un arreglo o bien n argumentos a la función
 * @return Texto traducido
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-03
 */
function __d ($dominio, $string, $args = null) {
	// si no hay argumentos solo se retorna el texto traducido
	if (!$args) {
		return I18n::translate($string, $dominio);
	}
	// si los argumentos no son un arreglo se obtiene arreglo a partir
	// de los argumentos pasados a la función
	if (!is_array($args)) {
		$args = array_slice(func_get_args(), 2);
	}
	return vsprintf(I18n::translate($string, $dominio), $args);
}

/**
 * Une dos o más arrays recursivamente
 * @param array $array1
 * @param array $array2
 * @return array
 * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
 * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
 */
function array_merge_recursive_distinct ( array &$array1, array &$array2 ) {
	$merged = $array1;
	foreach ( $array2 as $key => &$value ) {
		if (
			is_array ( $value )
			&& isset ( $merged [$key] )
			&& is_array ( $merged [$key] )
		) {
			$merged [$key] = array_merge_recursive_distinct (
				$merged [$key],
				$value
			);
		} else {
			$merged [$key] = $value;
		}
	}
	return $merged;
}

/**
 * Función para formatear números
 * @param n Número a formatear
 * @param d Cantidad de decimales
 * @return String Número formateado
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2012-10-19
 */
function num ($n, $d=0) {
	return number_format($n, $d, ',', '.');
}

/**
 * Convierte una cadena de texto "normal" a una del tipo url, ejemplo:
 *   Cadena normal: Esto es un texto
 *   Cadena convertida: esto-es-un-texto
 * @param string String a convertir
 * @param encoding Codificación del string
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-02-17
 */
function string2url ($string, $encoding = 'UTF-8') {
	// tranformamos todo a minúsculas
	$string = mb_strtolower($string, $encoding);
	// rememplazamos carácteres especiales latinos
	$find = array('á', 'é', 'í', 'ó', 'ú', 'ñ');
	$repl = array('a', 'e', 'i', 'o', 'u', 'n');
	$string = str_replace($find, $repl, $string);
	// añadimos los guiones
	$find = array(' ', '&', '\r\n', '\n', '+', '_');
	$string = str_replace($find, '-', $string);
	// eliminamos y reemplazamos otros caracteres especiales
	$find = array('/[^a-z0-9\-<>]/', '/[\-]+/', '/<[^>]*>/');
	$repl = array('', '-', '');
	$string = preg_replace($find, $repl, $string);
	unset($find, $repl);
	return $string;
}

/**
 * Función que genera un string de manera aleatoria
 * @param length Tamaño del string que se desea generar
 * @author http://www.lost-in-code.com/programming/php-code/php-random-string-with-numbers-and-letters
 */
function string_random ($length = 10) {
	$characters = '0123456789';
	$characters .= 'abcdefghijklmnopqrstuvwxyz';
	$characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$string = '';
	for ($p = 0; $p < $length; $p++) {
		$string .= $characters[mt_rand(0, strlen($characters)-1)];
	}
	return $string;
}

/**
 * Convierte una tabla de Nx2 (N filas 2 columnas) a un arreglo asociativo
 * @param table Tabla de Nx2 (N filas 2 columnas) que se quiere convertir
 * @return Arreglo convertido a asociativo
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-02-24
 */
function table2array ($table) {
	$array = array();
	foreach($table as &$row) {
		$array[array_shift($row)] = array_shift($row);
	}
	return $array;
}
