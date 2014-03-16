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

// autor y versión para el código que se está generando
define('AUTHOR', 'MiPaGiNa Code Generator');
define('VERSION', date(Configure::read('time.format')));

// clases utilizadas por esta shell
App::uses('AppShell', 'Shell');
App::uses('Database', 'Model/Datasource/Database');

/**
 * Comando para generar código de forma automática
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-16
 */
class CodeGeneratorShell extends AppShell {
	
	public static $db; ///< Conexión a la base de datos
	public static $destination; ///< Destino de los archivos que se generan
	public static $module; ///< Módulo donde se encontrarán los archivos
	public static $module_url; ///< Url para acceder al módulo
	private static $tables; ///< Tablas de la base de datos que se estarán procesando
	private static $nTables; ///< Cantidad de tablas que se estarán procesando
	
	/**
	 * Método principal del comando
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-03-16
	 */
	public function main () {
		// obtener nombre de la base de datos
		$database = $this->selectDatabase();
		if(!$database) {
			$this->out('<error>No se encontró configuración válida para la base de datos</error>');
			exit(1);
		}
		// obtener conexión a la base de datos
		self::$db = &Database::get($database);
		// obtener tablas de la base de datos
		$aux = self::$db->getTables();
		$tables = array();
		foreach ($aux as &$t) {
			$tables[] = $t['name'];
		}
		// mostrar tablas disponibles para que usuario elija cual quiere procesar
		$this->out('Tablas disponibles: '.implode(', ', $tables));
		$procesarTablas = $this->in('Ingresar las tablas que desea procesar [*]: ');
		if (!empty($procesarTablas) && $procesarTablas != '*') {
			if (strpos($procesarTablas, ',')) {
				$procesarTablas = str_replace (' ', '', $procesarTablas);
				$tables = explode (',', $procesarTablas);
			} else {
				$tables = explode (' ', $procesarTablas);
			}
		}
		// obtener información de las tablas
		self::$nTables = count ($tables);
		$nTables = 0;
		$this->out('<info>Recuperando información de las tablas '.round(($nTables/self::$nTables)*100).'%</info>', 0);
		self::$tables = array();
		foreach($tables as &$table) {
			self::$tables[$table] = self::$db->getInfoFromTable($table);
			$nTables++;
			$this->out("\r".'<info>Recuperando información de las tablas '.round(($nTables/self::$nTables)*100).'%</info>', 0);
		}
		unset($tables);
		unset($nTables);
		$this->out('');
		// obtener destino para los archivos
		self::$destination = $this->selectDestination();
		// crear directorios para archivos que se crearán
		if(!file_exists(self::$destination.'/Model')) mkdir(self::$destination.'/Model');
		if(!file_exists(self::$destination.'/Controller')) mkdir(self::$destination.'/Controller');
		// generar archivos
		$this->generateModelBase();
		$this->generateModel($database);
		$this->generateControllerBase();
		$this->generateController();
		$this->generateView();
	}
	
	/**
	 * Método para seleccionar una base de datos en caso de existir múltiples configuraciones
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-11-09
	 */
	private function selectDatabase () {
		$this->out('<info>Seleccionando base de datos para generar código</info>');
		// obtener bases de datos disponibles
		$databases = Configure::read('database');
		$keys = array_keys($databases);
		$encontradas = count($keys);
		// si no hay
		if(!$encontradas) return false;
		// si solo hay una
		else if($encontradas==1) return $keys[0];
		// si hay más de una se debe elegir una
		else if($encontradas>1) {
			// mostrar bases disponibles
			$this->out('Bases de datos disponibles:');
			$i = 1;
			foreach($databases as $name => &$config) {
				$this->out($i.'.- '.$name);
				++$i;
			}
			// solicitar opción hasta tener una válida
			do {
				$opcion = $this->in('Seleccionar una base de datos: ');
			} while($opcion<1 || $opcion>$encontradas);
			// retornar nombre de la conexión
			return $keys[$opcion-1];
		}
		// no se encontró configuración válida
		return false;
	}
	
	/**
	 * Seleccionar destino donde se guardarán los archivos generados
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-11-20
	 */
	private function selectDestination () {
		// buscar directorios de módulos
		$modulos = $this->getModules();
		$encontradas = count($modulos) + 1;
		// mostrar directorio principal
		$this->out('Directorios de destino disponibles:');
		$this->out('1.- Directorio base de la aplicación');
		// mostrar directorios de módulos disponibles
		$i = 2;
		foreach($modulos as &$modulo) {
			$this->out($i.'.- Módulo '.$modulo);
			++$i;
		}
		// solicitar opción hasta tener una válida
		do {
			$opcion = $this->in('Seleccionar un directorio para guardar archivos generados: ');
		} while($opcion<1 || $opcion>$encontradas);
		// retornar la ubicación
		if ($opcion == 1) {
			$this->setModuleUrl();
			return DIR_WEBSITE;
		} else {
			$modulo = $modulos[$opcion-2];
			$this->setModuleUrl($modulo);
			$modulo = str_replace('.', '/Module/', $modulo);
			return DIR_WEBSITE.'/Module/'.$modulo;
		}
	}

	/**
	 * Buscar recursivamente todos los módulos de la aplicación
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-03-16
	 */
	private function getModules ($dir = null, $parentModule = '') {
		// si no se indicó directorio es el principal
		if (!$dir) $dir = DIR_WEBSITE.'/Module';
		// si no existe el directorio terminar de procesar
		if(!is_dir($dir)) return array();
		// buscar módulos en el directorio
		$modulesAux = array_values(array_diff(
			scandir($dir),
			array('..', '.')
		));
		// agregar módulos encontrados
		$modules = array();
		// por cada módulo procesar el subdirectorio
		foreach($modulesAux as &$module) {
			// crear padre
			$padre = $parentModule;
			$padre = empty($padre) ? $module : $padre.'.'.$module;
			// agregar módulo
			$modules[] = $padre;
			// buscar submodulos
			$modules = array_merge(
				$modules,
				$this->getModules(
					$dir.'/'.$module.'/Module',
					$padre
				)
			);
		}
		// entregar módulos
		return $modules;
	}

	/**
	 * Método que asigna el nombre del módulo y su url
	 * @param modulo Nombre del módulo donde se generarán los archivos
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-06-25
	 */
	private function setModuleUrl ($modulo = '') {
		if(empty($modulo)) {
			self::$module = '';
			self::$module_url = '';
		} else {
			self::$module = $modulo.'.';
			$partes = explode('.', $modulo);
			$module_url = '';
			foreach($partes as &$p) {
				$module_url .= Inflector::underscore($p).'/';
			}
			self::$module_url = $module_url;
		}
	}
	
	/**
	 * Método que genera el código para la clase base de modelos
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-03-16
	 */
	private function generateModelBase () {
		$this->out('<info>Generando base para modelos</info>');
		foreach(self::$tables as $table => &$info) {
			// buscar info de la tabla
			$class = Inflector::camelize($table);
			$columns = array();
			$getObjectFKs = array();
			$columns_sql_insert = array();
			$values_sql_insert = array();
			$columns_sql_update = array();
			$columns_clear = array();
			$columnsInfo = array();
			foreach($info['columns'] as &$column) {
				// generar atributo
				$columns[] = $this->src('public ${column}; ///< {comment}{type}({length}){null}{default}{auto}{pk}{fk}', array(
					'column'	=> $column['name'],
					'comment'	=> $column['comment']!=''?$column['comment'].': ':'',
					'type'		=> $column['type'],
					'length'	=> $column['length'],
					'null'		=> ($column['null']==='YES'||$column['null']==1)?' NULL':' NOT NULL',
					'default'	=> " DEFAULT '".$column['default']."' ",
					'auto'		=> ($column['auto']==='YES'||$column['auto']==1)?'AUTO ':'',
					'pk'		=> in_array($column['name'], $info['pk'])?'PK ':'',
					'fk'		=> is_array($column['fk'])?'FK:'.$column['fk']['table'].'.'.$column['fk']['column']:'',
				));
				// generar información de la columna
				$columnsInfo[] = $this->src('columnInfo.phps', array(
					'column'	=> $column['name'],
					'name'		=> Inflector::humanize($column['name']),
					'comment'	=> $column['comment'],
					'type'		=> $column['type'],
					'length'	=> !empty($column['length'])?$column['length']:'null',
					'null'		=> ($column['null']==='YES'||$column['null']==1)?'true':'false',
					'default'	=> $column['default'],
					'auto'		=> ($column['auto']==='YES'||$column['auto']==1)?'true':'false',
					'pk'		=> in_array($column['name'], $info['pk'])?'true':'false',
					'fk'		=> is_array($column['fk']) ? 'array(\'table\' => \''.$column['fk']['table'].'\', \'column\' => \''.$column['fk']['column'].'\')':'null',
				));
				// procesar si es FK
				if(is_array($column['fk'])) {
					$fk_class = Inflector::camelize($column['fk']['table']);
					$getObjectFKs[] = $this->src('getObjectFK.phps', array(
						'fk_name' => Inflector::camelize($column['name']),
						'fk_class' => $fk_class,
						'class' => $class,
						'author' => AUTHOR,
						'version' => VERSION,
						'pk' => '$this->'.$column['name'],
					));
				}
				// valor para la columna, ya sea al insertar o al actualizar
				$value = "\".(!empty(\$this->".$column['name'].") || \$this->".$column['name']."=='0' ? \"'\".\$this->db->sanitize(\$this->".$column['name'].").\"'\" : 'NULL').\"";
				// procesar para insertar
				if(!($column['auto']==='YES'||$column['auto']===1)) {
					if(in_array($column['name'], array('creado', 'creada', 'modificado', 'modificada'))) {
						$value = 'NOW()';
					}
					$columns_sql_insert[] = $column['name'];
					$values_sql_insert[] = $value;
				}
				// procesar para actualizar
				if(!in_array($column['name'], $info['pk']) && !in_array($column['name'], array('creado', 'creada'))) {
					if(in_array($column['name'], array('modificado', 'modificada'))) {
						$value = 'NOW()';
					}
					$columns_sql_update[] = $column['name'].' = '.$value;
				}
				// generar columnas para clear
				$columns_clear[] = '$this->'.$column['name'].' = null;';
			}
			$columns = implode("\n\t", $columns);
			$columnsInfo = implode('', $columnsInfo);
			$getObjectFKs = implode("\n", $getObjectFKs);
			$columns_sql_insert = implode(",\n\t\t\t\t", $columns_sql_insert);
			$values_sql_insert = implode(",\n\t\t\t\t", $values_sql_insert);
			$columns_sql_update = implode(",\n\t\t\t\t", $columns_sql_update);
			$columns_clear = implode("\n\t\t", $columns_clear);
			// procesar pks
			$pk_parameter = array();
			$pk_set_from_parameter = array();
			$pk_attributes_not_empty = array();
			$pk_sql_where = array();
			foreach($info['pk'] as &$pk) {
				$pk_parameter[] = '$'.$pk.' = null';
				$pk_set_from_parameter[] = '$this->'.$pk.' = $'.$pk.';';
				$pk_attributes_not_empty[] = '!empty($this->'.$pk.')';
				$pk_sql_where[] = $pk." = '\".\$this->db->sanitize(\$this->".$pk.").\"'";
			}
			$pk_parameter = implode(', ', $pk_parameter);
			$pk_set_from_parameter = implode("\n\t\t\t\t", $pk_set_from_parameter);
			$pk_attributes_not_empty = implode(' && ', $pk_attributes_not_empty);
			$pk_sql_where = implode(' AND ', $pk_sql_where);
			// generar datos
			$file = $this->src('ModelBase.phps', array(
				'table' => $table,
				'comment' => $info['comment'],
				'author' => AUTHOR,
				'version' => VERSION,
				'class' => $class,
				'classs' => Inflector::camelize(Inflector::pluralize($table)),
				'columns' => $columns,
				'columnsInfo' => $columnsInfo,
				'getObjectFKs' => $getObjectFKs,
				'pk_parameter' => $pk_parameter,
				'pk_set_from_parameter' => $pk_set_from_parameter,
				'pk_attributes_not_empty' => $pk_attributes_not_empty,
				'pk_sql_where' => $pk_sql_where,
				'columns_sql_insert' => $columns_sql_insert,
				'values_sql_insert' => $values_sql_insert,
				'columns_sql_update' => $columns_sql_update,
				'columns_clear' => $columns_clear,
			));
			// guardar archivo en el directorio de modelos
			file_put_contents(self::$destination.'/Model/'.$class.'Base.php', $file);
		}
	}

	/**
	 * Método que genera el código para la clase final de modelos
	 * @param database Nombre de la conexión a la base de datos
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-03-16
	 */
	private function generateModel ($database) {
		$this->out('<info>Generando modelos</info>');
		foreach(self::$tables as $table => &$info) {
			$fkModule = array();
			foreach($info['columns'] as &$column) {
				// procesar si es FK
				if(is_array($column['fk'])) {
					$fk_class = Inflector::camelize($column['fk']['table']);
					$fkModule[] = "'$fk_class' => '".self::$module."'";
				}
			}
			if(count($fkModule)) {
				$fkModule = "\n\t\t".implode(",\n\t\t", $fkModule)."\n\t";
			} else $fkModule = '';
			// generar datos
			$class = Inflector::camelize($table);
			$file = $this->src('Model.phps', array(
				'database' => $database,
				'table' => $table,
				'comment' => $info['comment'],
				'author' => AUTHOR,
				'version' => VERSION,
				'class' => $class,
				'classs' => Inflector::camelize(Inflector::pluralize($table)),
				'module' => self::$module,
				'fkModule' => $fkModule,
			));
			// guardar archivo en el directorio de clases (si no existe)
			$filename = self::$destination.'/Model/'.$class.'.php';
			if(!file_exists($filename)) {
				file_put_contents($filename, $file);
			}
		}
	}
	
	/**
	 * Método que genera el código para la clase base de controladores
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-03-16
	 */
	private function generateControllerBase () {
		$this->out('<info>Generando base para controladores</info>');
		foreach(self::$tables as $table => &$info) {
			// buscar info de la tabla
			$class = Inflector::camelize($table);
			// procesar pks
			$pk_parameter = array();
			foreach($info['pk'] as &$pk) {
				$pk_parameter[] = '$'.$pk;
			}
			$pk_parameter = implode(', ', $pk_parameter);
			// buscar columnas que sean archivos
			$files = $this->getColsForFiles($info['columns']);
			// procesar archivos
			if(isset($files[0])) {
				$methods_ud = $this->src('upload_download.phps', array(
					'author' => AUTHOR,
					'version' => VERSION,
					'pk_parameter' => $pk_parameter,
					'class' => $class,
				));
				$files = "'".implode("', '", $files)."'";
			} else {
				$methods_ud = '';
				$files = '';
			}
			// generar datos
			$classs = Inflector::camelize(Inflector::pluralize($table));
			$file = $this->src('ControllerBase.phps', array(
				'table' => $table,
				'comment' => $info['comment'],
				'author' => AUTHOR,
				'version' => VERSION,
				'class' => $class,
				'classs' => $classs,
				'controller' => Inflector::pluralize($table),
				'pk_parameter' => $pk_parameter,
				'methods_ud' => $methods_ud,
				'files' => $files,
			));
			// guardar archivo en el directorio de clases (si no existe)
			$filename = self::$destination.'/Controller/'.$classs.'BaseController.php';
			file_put_contents($filename, $file);
		}
	}

	/**
	 * Método que genera el código para la clase final del controlador
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-03-16
	 */
	private function generateController () {
		$this->out('<info>Generando controladores</info>');
		foreach(self::$tables as $table => &$info) {
			// generar datos
			$class = Inflector::camelize($table);
			$classs = Inflector::camelize(Inflector::pluralize($table));
			$file = $this->src('Controller.phps', array(
				'table' => $table,
				'comment' => $info['comment'],
				'author' => AUTHOR,
				'version' => VERSION,
				'class' => $class,
				'classs' => $classs,
				'module' => self::$module,
				'module_url' => self::$module_url,
			));
			// guardar archivo en el directorio de clases (si no existe)
			$filename = self::$destination.'/Controller/'.$classs.'Controller.php';
			if(!file_exists($filename)) {
				file_put_contents($filename, $file);
			}
		}
	}

	/**
	 * Método que genera el código para las vistas (para métodos CRUD de mantenedores)
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-03-16
	 */
	private function generateView () {
		$this->out('<info>Generando vistas (en directorio tmp/View)</info>');
		if(!file_exists(self::$destination.'/tmp')) mkdir(self::$destination.'/tmp');
		if(!file_exists(self::$destination.'/tmp/View')) mkdir(self::$destination.'/tmp/View');
		foreach(self::$tables as $table => &$info) {
			// buscar info de la tabla
			$class = Inflector::camelize($table);
			$classs = Inflector::camelize(Inflector::pluralize($table));
			// buscar columnas que sean archivos
			$files = $this->getColsForFiles($info['columns']);
			$filesOk = array();
			// procesamiento real de columnas
			$columns = array();
			foreach($info['columns'] as &$column) {
				// si la columna es creado, modificado, creada o modificada se omite en la vista (ya que se generará automáticamente su valor)
				if(in_array($column['name'], array('creado', 'modificado', 'creada', 'modificada'))) continue;
				// si la columna está relacionada con un archivo (buscar cada una de las posibles columnas)
				$isFile = false;
				foreach($files as &$file) {
					if(!$isFile && in_array($column['name'], array(
						$file.'_name',
						$file.'_type',
						$file.'_size',
						$file.'_data',
						$file.'_x1',
						$file.'_y1',
						$file.'_x2',
						$file.'_y2',
						$file.'_t_size',
						$file.'_t_data',
					))) {
						if(!in_array($file, $filesOk))
							$columns[] = "'".$file."' => array('type' => 'file', 'name' => '".Inflector::humanize($file)."')";
						$filesOk[] = $file;
						$isFile = true;
					}
				}
				if($isFile) continue;
				// columnas de la tabla
				$columns[] = "'".$column['name']."' => '".Inflector::humanize($column['name'])."'";
			}
			$columns = implode(",\n\t", $columns);
			// procesar pks
			$pkUrl = array();
			$pkTupla = array();
			foreach($info['pk'] as &$pk) {
				$pkUrl[] = 'urlencode($obj->'.$pk.')';
				$pkTupla[] = '$obj->'.$pk;
			}
			$pkUrl = implode('.\'/\'.', $pkUrl);
			$pkTupla = implode('.\',\'.', $pkTupla);
			// crear directorio para vista de la tabla
			if(!file_exists(self::$destination.'/tmp/View/'.$classs)) mkdir(self::$destination.'/tmp/View/'.$classs);
			// generar datos para archivos
			foreach(array('listar', 'crear', 'editar') as $src) {
				$file = $this->src('View/'.$src.'.phps', array(
					'comment' => $info['comment'],
					'class' => $class,
					'classs' => Inflector::camelize(Inflector::pluralize($table)),
					'columns' => $columns,
					'pkUrl' => $pkUrl,
					'pkTupla' => $pkTupla,
				));
				// guardar archivos en el directorio de clases (si no existe)
				$filename = self::$destination.'/tmp/View/'.$classs.'/'.$src.'.php';
				file_put_contents($filename, $file);
			}
		}
	}

	private function getColsForFiles ($columns) {
		// preprocesar columnas (para buscar archivos, columnas: *_name, *_type, *_size y *_data)
		$archivos = array();
		foreach($columns as &$column) {
			if(in_array(substr($column['name'], -5), array('_name', '_type', '_size', '_data'))) {
				$nombre = substr($column['name'], 0, -5);
				if(!isset($archivos[$nombre])) $archivos[$nombre] = 0;
				$archivos[$nombre]++;
			}
		}
		// procesar archivos, solo se dejan las que se hayan encontrado las 4 coincidencias
		$files = array();
		foreach($archivos as $archivo => $coincidencias) {
			if($coincidencias==4) $files[] = $archivo;
		}
		// retornar
		return $files;
	}

	/**
	 * Método que renderiza las plantillas para el código
	 * @param plantilla Archivo con la plantilla que se debe renderizar
	 * @param variables Variables que se deben reemplazar al renderizar
	 * @return String Plantilla ya renderizada
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-11-09
	 */
	private function src ($plantilla, $variables = array()) {
		// location
		$archivo = App::location('Shell/Command/CodeGenerator/'.$plantilla);
		// cargar plantilla
		if($archivo)
			$plantilla = file_get_contents($archivo);
		// reemplazar variables en la plantilla
		foreach($variables as $key => $valor) {
			$plantilla = str_replace('{'.$key.'}', $valor, $plantilla);
		}
		// retornar plantilla ya procesada
		return $plantilla;
	}
	
}
