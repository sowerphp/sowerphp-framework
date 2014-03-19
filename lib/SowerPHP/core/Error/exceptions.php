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
 * @file exceptions.php
 * Archivo con clases para excepciones estándares (genéricas y comúnes)
 * @version 2014-03-15
 */

/**
 * Excepción cuando no se encuentra un controlador (página no existe)
 */ 
class MissingControllerException extends MiException {
	protected $_messageTemplate = 'Controlador %s no fue encontrado';
	public function __construct($message, $code = 404) {
		parent::__construct($message, $code);
	}
}

/**
 * Excepción cuando no se encuentra la acción (método) solicitada en el
 * controlador
 */ 
class MissingActionException extends MiException {
	protected $_messageTemplate = 'Acción %s::%s() no fue encontrada';
	public function __construct($message, $code = 404) {
		parent::__construct($message, $code);
	}
}

/**
 * Excepción cuando se solicita una acción (método) que es privada
 */ 
class PrivateActionException extends MiException {
	protected $_messageTemplate = 'Acción %s::%s() es privada y no puede ser accedida mediante la URL';
	public function __construct($message, $code = 401) {
		parent::__construct($message, $code);
	}
}

/**
 * Excepción cuando no se encuentra un helper que ha sido solicitado
 */ 
class MissingHelperException extends MiException {
	protected $_messageTemplate = 'Clase del helper %s no fue encontrada';
}

/**
 * Excepción cuando no se encuentra un componente que ha sido solicitado
 */ 
class MissingComponentException extends MiException {
	protected $_messageTemplate = 'Componente %s no fue encontrado';
}

/**
 * Excepción cuando no se encuentra la vista de una acción de un controlador
 * que se está tratando de renderizar
 */ 
class MissingViewException extends MiException {
	protected $_messageTemplate = 'Vista %s para acción %s::%s() no ha sido encontrada';
}

/**
 * Excepción estándar (para cuando no hay una definida)
 */ 
class MiErrorException extends MiException {
	protected $_messageTemplate = '%s';
}

/**
 * Excepción cuando no se encuentra un módulo que ha sido configurado con
 * Module::uses()
 */
class MissingModuleException extends MiException {
	protected $_messageTemplate = 'Módulo %s no fue encontrado';
}
