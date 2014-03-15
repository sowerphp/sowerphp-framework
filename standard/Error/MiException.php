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
 * Clase base para todas las excepciones
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2012-10-27
 */
class MiException extends RuntimeException {

	protected $_messageTemplate = ''; ///< Mensaje que se utilizará al renderizar el error

	/**
	 * Constructor para la excepción
	 * @param message Un string con el error o bien un arreglo con atributos que son pasados al mensaje que se traducirá
	 * @param code string Código del error (default: 500)
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-27
	 */
	public function __construct($message, $code = 500) {
		// si es un arreglo se utilizará junto a sprintf
		if (is_array($message)) {
			$message = vsprintf($this->_messageTemplate, $message);
		}
		// llamar al constructor con el error y el mensaje
		parent::__construct($message, $code);
	}

}
