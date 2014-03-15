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

// clases que son necesarias para ejecutar el Dispatcher
App::uses('Request', 'Network');
App::uses('Response', 'Network');
App::uses('Router', 'Routing');

/**
 * Clase para despachar la página que se esté solicitando
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-15
 */
class Dispatcher {

	/**
	 * Método que despacha la página solicitada
	 * @param request Objeto Request
	 * @param response Objeto Response
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-02-23
	 */
	public function dispatch(Request $request, Response $response) {
		// Verificar si el recurso solicitado es un archivo físico dentro del directorio webroot
		if ($this->_asset($request->request, $response)) {
			return; // retorna el método Dispatcher::dispatch, con lo cual termina el procesado de la página
		}
		// Parsear parámetros del request
		$request->params = Router::parse($request->request);
		// Si se solicita un modulo tratar de cargar y verificar que quede activo
		if(!empty($request->params['module'])) {
			Module::load($request->params['module']);
			if(!Module::loaded($request->params['module'])) {
				throw new MissingModuleException(array(
						'module' => $request->params['module']
				));
			}
		}
		// Obtener controlador
		$controller = $this->_getController($request, $response);
		// Verificar que lo obtenido sea una instancia de la clase Controller
		if (!($controller instanceof Controller)) {
			throw new MissingControllerException(array(
				'class' => Inflector::camelize($request->params['controller']) . 'Controller'
			));
		}
		// Invocar a la acción del controlador
		return $this->_invoke($controller, $request, $response);
	}
	
	/**
	 * Busca si lo solicitado existe físicamente en el servidor y lo entrega
	 * @param url Ruta de los que se está solicitando
	 * @param response Objeto Response
	 * @return Verdadero si lo solicitado existe dentro de /webroot
	 * @todo Revisar en paths de modulos
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-03-15
	 */
	private function _asset($url, Response $response) {
		// Si la URL es vacía se retorna falso
		if($url=='') return false;
		// Inicializar el archivo como null
		$assetFile = null;
		// Buscar el archivo en los posibles directorios webroot,
		// incluyendo los paths de los modulos
		$paths = null;
		// si hay más de dos slash en la url podría ser un modulo asi
		// que se busca path para el modulo
		$slashPos = strpos($url, '/', 1);
		if($slashPos) {
			// paths de plugins
			$module = Module::find($url);
			if (isset($module[0])) {
				Module::load($module);
				$paths = Module::paths($module);
			}
			// si existe el módulo en los paths entonces si es un
			// módulo lo que se está pidiendo, y es un módulo ya
			// cargado. Si este no fuera el caso podría no ser
			// plugin, o no estar cargado
			if($paths) {
				$removeCount = count(explode('.', $module)) + 1;
				$aux = explode('/', $url);
				while ($removeCount-->0) array_shift($aux);
				$url = '/'.implode('/', $aux);
			}
		}
		// si no está definido el path entonces no era de módulo y se
		// deberá buscar en los paths de la aplicación
		if(!$paths) $paths = App::paths();
		// en cada paths encontrado buscar el archivo solicitado
		foreach($paths as &$path) {
			$file = $path.'/webroot'.$url;
			if (file_exists($file) && !is_dir($file)) {
				$assetFile = $file;
				break;
			}
		}
		// Si se encontró el archivo se envía al cliente
		if($assetFile!==null) {
			// Solo se entrega mediante PHP si el archivo no está en DIR_WEBSITE/webroot
			if(!strpos($assetFile, DIR_WEBSITE)!==false) {
				$response->sendFile($assetFile);
			}
			return true;
		}
		// Si no se encontró se retorna falso
		return false;
	}

	/**
	 * Método que obtiene el controlador
	 */
	private function _getController(Request $request, Response $response) {
		// Cargar clase del controlador
		$controller = Inflector::camelize($request->params['controller']);
		$class = $controller.'Controller';
		$location = !empty($request->params['module']) ? $request->params['module'].'.'.'Controller' : 'Controller';
		App::uses($class, $location);
		$ctrlClass = class_exists($class) ? $class : false;
		// Si la clase no se logro cargar se retorna falso
		if (!$ctrlClass) return false;
		// Se verifica que la clase no sea abstracta o una interfaz
		$reflection = new ReflectionClass($ctrlClass);
		if ($reflection->isAbstract() || $reflection->isInterface()) {
			return false;
		}
		// Se retorna la clase instanciada del controlador con los parámetros $request y $response al constructor
		return $reflection->newInstance($request, $response);
	}
	
	/**
	 * Método que se encarga de invocar a la acción del controlador y
	 * entregar la respuesta
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-02-24
	 */
	private function _invoke(Controller $controller, Request $request, Response $response) {
		// Iniciar el proceso
		$controller->startupProcess();
		// Ejecutar acción
		$result = $controller->invokeAction();
		// Renderizar proceso
		if ($controller->autoRender) {
			$response = $controller->render();
		} elseif ($response->body() === null) {
			$response->body($result);
		}
		// Detener el proceso
		$controller->shutdownProcess();
		// Retornar respuesta al cliente
		if (isset($request->params['return'])) {
			return $response->body();
		}
		// Enviar respuesta al cliente
		$response->send();
	}

}
