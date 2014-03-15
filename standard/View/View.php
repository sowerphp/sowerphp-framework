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
 * Clase que renderizará las vistas de la aplicación
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-15
 */
class View {
	
	protected $request; ///< Objeto Request
	protected $response; ///< Objeto Response
	public $viewVars = array(); ///< Variables que se pasarán al renderizar la vista
	private $layout; ///< Tema que se debe usar para renderizar la página
	private $defaultLayout = 'SimpleLight'; ///< Layout por defecto
	protected static $_viewsLocation; ///< Listado de vistas que se han buscado
	protected static $extensions = null; ///< Listado de extensiones
	
	/**
	 * Constructor de la clase View
	 * @param controller Objeto con el controlador que ocupa la vista
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-11-09
	 */
	public function __construct(Controller $controller) {
		$this->request = $controller->request;
		$this->response = $controller->response;
		$this->viewVars = $controller->viewVars;
		$this->layout = $controller->layout;
	}

	/**
	 * Método para renderizar una página
	 * El como renderizará dependerá de la extensión de la página encontrada
	 * @param page Ubicación relativa de la página
	 * @param ext Extensión de la página que se está renderizando
	 * @return Buffer de la página renderizada
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-03-15
	 */
	public function render ($page, $ext = null) {
		// buscar página
		$location = self::location(
			$page, $this->request->params['module'],
			$ext
		);
		// si no se encontró error
		if(!$location) {
			if($this->request->params['controller']=='pages')
				$this->render('/error/404');
			else {
				throw new MissingViewException(array(
					'view' => $page,
					'controller' => Inflector::camelize($this->request->params['controller']),
					'action' => $this->request->params['action'],
				));
			}
			return;
		}
		// dependiendo de la extensión de la página se renderiza
		$ext = substr($location, strrpos($location, '.')+1);
		$class = ucfirst($ext).'Page';
		App::uses($class, 'View/Helper/Pages');
		$page_content = $class::render($location, $this->viewVars);
		if ($this->layout === null)
			return $page_content;
		// buscar archivo del tema que está seleccionado, si no existe
		// se utilizará el tema por defecto
		$layout = $this->getLayoutLocation($this->layout);
		if(!$layout) {
			$this->layout = $this->defaultLayout;
			$layout = $this->getLayoutLocation($this->layout);
		}
		// página que se está viendo
		if(!empty($this->request->request)) {
			$slash = strpos($this->request->request, '/', 1);
			$page = $slash===false ? $this->request->request : substr($this->request->request, 0, $slash);
		} else $page = '/inicio';
		// renderizar layout de la página (con su contenido)
		App::uses('PhpPage', 'View/Helper/Pages');
		return PhpPage::render($layout, array_merge(array(
			'_header_title' => Configure::read('page.header.title').': '.$page,
			'_header_extra' => '<!-- MODIFICAR EN View.php -->',
			'_body_title' => Configure::read('page.body.title'),
			'_page' => $page,
			'_nav_website' => Configure::read('nav.website'),
			'_nav_app' => Configure::read('nav.app'),
			'_footer' => Configure::read('page.footer'),
			'_timestamp' => date(Configure::read('time.format'), filemtime($location)),
			'_layout' => $this->layout,
			'_content' => $page_content,
		), $this->viewVars));
	}

	/**
	 * Método que entrega la ubicación del tema que se está utilizando
	 * @param layout Tema que se quiere buscar su ubicación
	 * @return Ubicación del tema (o falso si no se encontró)
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-09-14
	 */
	private function getLayoutLocation ($layout) {
		$paths = App::paths(); 
		foreach($paths as $path) {
			$file = $path.'/View/Layouts/'.$layout.'.php';
			if (file_exists($file)) {
				return $file;
			}
		}
		return false;
	}

	/**
	 * Método que busca la vista en las posibles rutas y para todas las posibles extensiones
	 * @param view Nombre de la vista buscada (ejemplo: /inicio)
	 * @param module Nombre del módulo en caso de pertenecer a uno
	 * @param ext Extensión de la vista buscada
	 * @return Ubicación de la vista que se busca
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-11-09
	 */
	public static function location ($view, $module = null, $ext = null) {
		// si la página está en cache se retorna
		if(isset(self::$_viewsLocation[$view])) return self::$_viewsLocation[$view];
		// obtener paths
		$paths = App::paths();
		// extensiones
		if(!self::$extensions) {
			self::$extensions = Configure::read('page.extensions');
			if(!is_array(self::$extensions)) self::$extensions = array();
			if(!in_array('php', self::$extensions)) self::$extensions[] = 'php'; // php siempre debe estar
		}
		// Determinar de donde sacar la vista
		$location = $module ? '/Module/'.str_replace('.', '/Module/', $module) : '';
		// buscar archivo en cada ruta
		foreach($paths as $path) {
			foreach(self::$extensions as $extension) {
				$file = $path.$location.'/View/'.$view.'.'.$extension;
				if (file_exists($file)) {
					self::$_viewsLocation[$view] = $file;
					return $file;
				}
			}
		}
		// Si se busca la vista Module/index y no fue encontrada se carga la por defecto
		if($view=='Module/index') {
			// Buscar el archivo de la vista en las posibles rutas
			foreach ($paths as $path) {
				$file = $path . '/View/Module/index.php';
				if (file_exists($file)) {
					return $file;
				}
			}
		}
		// si no se encontró el archivo de la vista se retorna falso
		return false;
	}

}
