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
 * Clase base para todos los componentes
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2012-11-06
 */
class Component {

	public $request; ///< Objeto Request
	public $settings = array(); ///< Opciones del componente
	public $Components = null; ///< Colección de componentes que se cargarán
	public $components = array(); ///< Nombre de componentes que este componente utiliza

	/**
	 * Constructor de la clase
	 * @todo Cargar componentes que este componente utilice
	 * @param Components Colección de componentes
	 * @param settings Opciones para la carga de componentes
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-11-06
	 */
	public function __construct(ComponentCollection $Components, $settings = array()) {
		$this->Components = $Components;
		$this->settings = array_merge_recursive_distinct (
			$this->settings,
			$settings
		);
	}

	/**
	 * Método llamado desde Controller::beforeFilter()
	 * Deberá se sobreescrito en el componente si se quiere utilizar
	 * @param controller Controllador que está siendo usado en el momento
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-11-06
	 */
	public function beforeFilter($controller) {
	}

	/**
	 * Método llamado desde Controller::afterFilter()
	 * Deberá se sobreescrito en el componente si se quiere utilizar
	 * @param controller Controllador que está siendo usado en el momento
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-11-06
	 */
	public function afterFilter($controller) {
	}

	/**
	 * Método llamado desde Controller::beforeRender()
	 * Deberá se sobreescrito en el componente si se quiere utilizar
	 * @param controller Controllador que está siendo usado en el momento
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-11-06
	 */
	public function beforeRender($controller) {
	}

	/**
	 * Método llamado desde Controller::beforeRedirect()
	 * Deberá se sobreescrito en el componente si se quiere utilizar
	 * @param controller Controllador que está siendo usado en el momento
	 * @param url Dirección hacia donde se está redirigiendo
	 * @param status Estado de términi del script (0 es todo ok)
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-11-06
	 */
	public function beforeRedirect($controller, $url = null, $status = null) {
	}

}
