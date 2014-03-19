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
 * Clase con la solicitud del cliente
 * @author Esteban De la Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-02-24
 */
class Request {

	public $params = null; ///< Parámetros pasados que definen que ejecutar
	public $base = null; ///< Ruta base de la URL (base + uri arma el total del request)
	public $request = null; ///< URI usada para la consulta (desde la aplicacion, o sea, sin base, iniciando con "/")
	public $url = null; ///< URL completa, partiendo desde HTTP o HTTPS según corresponda
	private static $Base = null; ///< Atributo estático "base", para recuperar de cualquier parte
	private static $Request = null; ///< Atributo estático "request", para recuperar de cualquier parte
	private static $Url = null; ///< Atributo estático "url", para recuperar de cualquier parte
	
	/**
	 * Constructor de la clase
	 * @author Esteban De la Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-03-15
	 */
	public function __construct() {
		// Determinar uri que se utilizó para acceder a la página
		self::$Request = $this->_request();
		// Asignar $this->base
		self::$Base = $this->_base();
		// Definir la URL base completa del sitio web (incluyendo protocolo y nombre del host)
		self::$Url = $this->url = 'http'.(isset($_SERVER['HTTPS'])?'s':null).'://'.$_SERVER['HTTP_HOST'].$this->base;
		// Quitar de lo pasado por get lo que se está solicitando
		unset($_GET[$this->request]);
	}

	/**
	 * Método que determina los campos base y webroot
	 * @return Base de la URL
	 * @author Esteban De la Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-07-31
	 */
	protected function _base() {
		$parts = explode('?', $_SERVER['REQUEST_URI']);
		$last = strrpos($parts[0], $this->request);
		$this->base = $last!==FALSE
				? substr($parts[0], 0, $last) : $parts[0];
		$pos = strlen($this->base)-1;
		if($pos>=0 && $this->base[$pos] == '/')
			$this->base = substr($this->base, 0, -1);
		return $this->base;
	}

	/**
	 * Método que determina la uri utilizada para acceder a la página (a
	 * contar del webroot)
	 * @return URL completa para la página
	 * @author Esteban De la Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-09-14
	 */
	protected function _request() {
		// Obtener ruta que se uso sin "/" (base) inicial
		$uri = substr($_SERVER['QUERY_STRING'], 1);
		// verificar si se pasaron variables GET
		$pregunta = strpos($uri, '&');
		// Asignar uri
		$this->request = $pregunta===false ? $uri : substr($uri, 0, $pregunta);
		// Agregar slash inicial de la uri
		if(!isset($this->request) || (isset($this->request[0])&&$this->request[0]!='/')) {
			$this->request = '/'.$this->request;
		}
		// Decodificar url
		$this->request = urldecode($this->request);
		return $this->request;
	}
	
	/**
	 * Método que entrega la base de la url
	 * @return String
	 * @author Esteban De la Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-16
	 */
	public static function getBase () {
		return self::$Base;
	}
	
	/**
	 * Método que entrega el request de la url (después de base)
	 * @return String
	 * @author Esteban De la Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-16
	 */
	public static function getRequest () {
		return self::$Request;
	}
	
	/**
	 * Método que entrega la url completa
	 * @return String
	 * @author Esteban De la Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-16
	 */
	public static function getUrl () {
		return self::$Url;
	}

}
