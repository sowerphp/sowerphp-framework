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
 * Clase para trabajar con una base de datos PostgreSQL
 * Paquete requerido:
 *   Debian: php5-pgsql
 *   CentOS: php-pgsql
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-08
 */
class PostgreSQL extends DatabaseManager {

	/**
	 * Constructor de la clase
	 * 
	 * Realiza conexión a la base de datos, recibe parámetros para la
	 * conexión
	 * @param config Arreglo con los parámetros de la conexión
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-03-08
	 */
	public function __construct ($config) {
		// verificar que existe el soporte para PostgreSQL en PHP
		if (!function_exists('pg_connect')) {
			$this->error ('No se encontró la extensión de PHP para PostgreSQL (pgsql)');
		}
		// definir configuración para el acceso a la base de datos
		$this->config = array_merge(array(
			'host' => 'localhost',
			'port' => '5432',
			'char' => 'utf8',
			'sche' => 'public',
		), $config);
		// realizar conexión a la base de datos
		$this->link = @pg_connect(
			'host='.$this->config['host'].
			' port='.$this->config['port'].
			' user='.$this->config['user'].
			' password='.$this->config['pass'].
			' dbname='.$this->config['name'].
			' options=\'--client_encoding='.
				$this->config['char'].'\'',
			PGSQL_CONNECT_FORCE_NEW
		);
		// si no se logró conectar => error
		if (!$this->link) {
			$this->error('¡No fue posible conectar con la base de datos!');
		}
		// definir esquema que se utilizará (solo si es diferente a
		// public)
		if ($this->config['sche'] != 'public') {
			$this->query(
				'SET search_path TO '.$this->config['sche']
			);
		}
	}

	/**
	 * Destructor de la clase
	 * 
	 * Cierra la conexión con la base de datos.
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function __destruct () {
		// si el identificador es un recurso de PostgreSQL se cierra
		if (
			is_resource($this->link) &&
			get_resource_type($this->link)=='pgsql link'
		) {
			pg_close($this->link);
		}
	}
	
	/**
	 * Realizar consulta en la base de datos
	 * @param sql Consulta SQL que se desea realizar
	 * @return Resource Identificador de la consulta
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-03-08
	 */
	public function query($sql) {
		// verificar que exista una consulta
		if(empty($sql)) {
			$this->error('¡Consulta no puede estar vacía!');
		}
		// realizar consulta
		$queryId = @pg_query($this->link, $sql);
		// si hubo error al realizar la consulta se muestra y termina el
		// script
		if(!$queryId) {
			$this->error(
				$sql."\n".pg_last_error($this->link)
			);
		}
		// retornar identificador de la consulta
		return $queryId;
	}
	
	/**
	 * Obtener una tabla (como arreglo) desde la base de datos
	 * @param sql Consulta SQL que se desea realizar
	 * @return Array Arreglo bidimensional con la tabla y sus datos
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-09-09
	 */
	public function getTable($sql) {
		// realizar consulta
		$queryId = $this->query($sql);
		// procesar resultado de la consulta
		$table = pg_fetch_all($queryId);
		// liberar datos de la consulta
		pg_free_result($queryId);
		// retornar tabla
		return is_array($table) ? $table : array();
	}
	
	/**
	 * Obtener una sola fila desde la base de datos
	 * @param sql Consulta SQL que se desea realizar
	 * @return Array Arreglo unidimensional con la fila
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-09-09
	 */
	public function getRow($sql) {
		// realizar consulta
		$queryId = $this->query($sql);
		// procesar resultado de la consulta
		//$row = pg_fetch_array($queryId, null, PGSQL_ASSOC);
		$row = pg_fetch_assoc($queryId);
		// liberar datos de la consulta
		pg_free_result($queryId);
		// retornar fila
		return is_array($row) ? $row : array();
	}

	/**
	 * Obtener una sola columna desde la base de datos
	 * @param sql Consulta SQL que se desea realizar
	 * @return Array Arreglo unidimensional con la columna
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-09-09
	 */
	public function getCol($sql) {
		// realizar consulta
		$queryId = $this->query($sql);
		// procesar resultado de la consulta
		$col = pg_fetch_all_columns($queryId);
		// liberar datos de la consulta
		pg_free_result($queryId);
		// retornar tabla
		return is_array($col) ? $col : array();
	}

	/**
	 * Obtener un solo valor desde la base de datos
	 * @param sql Consulta SQL que se desea realizar
	 * @return Mixed Valor devuelto
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-09-09
	 */
	public function getValue($sql) {
		// realizar consulta
		$queryId = $this->query($sql);
		// procesar resultado de la consulta
		$value = pg_fetch_row($queryId);
		// liberar datos de la consulta
		pg_free_result($queryId);
		// retornar tabla
		return is_array($value) ? array_pop($value) : false;
	}
	
	/**
	 * Método que limpia el string recibido para hacer la consulta en la
	 * base de datos de forma segura
	 * @param string String que se desea limpiar
	 * @param trim Indica si se deben o no quitar los espacios
	 * @return String String limpiado
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-09-09
	 */
	public function sanitize($string, $trim = true) {
		// se quitan espacios al inicio y final
		if($trim) $string = trim($string);
		// se proteje
		return pg_escape_string($this->link, $string);
	}

	/**
	 * Iniciar transacción
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-09-09
	 */
	public function transaction () {
		$this->query('BEGIN');
	}
	
	/**
	 * Confirmar transacción
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-09-09
	 */
	public function commit () {
		$this->query('COMMIT');
	}
	
	/**
	 * Cancelar transacción
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-09-09
	 */
	public function rollback () {
		$this->query('ROLLBACK');
	}
	
	/**
	 * Ejecutar un procedimiento almacenado
	 * @param procedure Procedimiento almacenado que se quiere ejecutar
	 * @return Mixed Valor que retorna el procedimeinto almacenado
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function exec ($procedure) {
		$parameters = func_get_args();
		$procedure = $this->sanitize(array_shift($parameters));
		foreach($parameters as &$parameter)
			$parameter = $this->sanitize($parameter);
		$parameters = isset($parameters[0]) ?
			"'".implode("', '", $parameters)."'" : '';
		return $this->getValue("SELECT $procedure($parameters)");
	}
	
	/**
	 * Obtener una tabla mediante un procedimiento almacenado
	 * @param procedure Procedimiento almacenado que se desea ejecutar
	 * @return Array Arreglo bidimensional con la tabla y sus datos
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function getTableFromSP ($procedure) {
		// recuperar parámetros pasados y extraer procedimiento
		$parameters = func_get_args();
		$procedure = array_shift($parameters);
		// limpiar parametros pasados
		foreach($parameters as &$parameter)
			$parameter = $this->sanitize($parameter);
		// agregar parámetro para el cursor que se usa para el resultado
		array_unshift($parameters, 'c_result');
		// preparar parámetros
		$parameters = isset($parameters[0]) ?
			"'".implode("', '", $parameters)."'" : '';
		// realizar consulta
		$queryId = $this->query("BEGIN; SELECT * FROM $procedure($parameters); FETCH ALL FROM c_result;");
		$table = pg_fetch_all($queryId);
		$this->query("END;");
		// liberar datos de la consulta
		pg_free_result($queryId);
		// retornar tabla
		return $table;
	}

	/**
	 * Obtener una sola fila mediante un procedimiento almacenado
	 * @param procedure Procedimiento almacenado que se desea ejecutar
	 * @return Array Arreglo unidimensional con la fila
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function getRowFromSP($procedure) {
		// recuperar parámetros pasados y extraer procedimiento
		$parameters = func_get_args();
		$procedure = array_shift($parameters);
		// limpiar parametros pasados
		foreach($parameters as &$parameter)
			$parameter = $this->sanitize($parameter);
		// agregar parámetro para el cursor que se usa para el resultado
		array_unshift($parameters, 'c_result');
		// preparar parámetros
		$parameters = isset($parameters[0]) ?
			"'".implode("', '", $parameters)."'" : '';
		// realizar consulta
		$queryId = $this->query("BEGIN; SELECT * FROM $procedure($parameters); FETCH ALL FROM c_result;");
		$row = pg_fetch_assoc($queryId);
		$this->query("END;");
		// liberar datos de la consulta
		pg_free_result($queryId);
		// retornar tabla
		return $row;
	}
	
	/**
	 * Obtener una sola columna mediante un procedimiento almacenado
	 * @param procedure Procedimiento almacenado que se desea ejecutar
	 * @return Array Arreglo unidimensional con la columna
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function getColFromSP($procedure) {
		// recuperar parámetros pasados y extraer procedimiento
		$parameters = func_get_args();
		$procedure = array_shift($parameters);
		// limpiar parametros pasados
		foreach($parameters as &$parameter)
			$parameter = $this->sanitize($parameter);
		// agregar parámetro para el cursor que se usa para el resultado
		array_unshift($parameters, 'c_result');
		// preparar parámetros
		$parameters = isset($parameters[0]) ?
			"'".implode("', '", $parameters)."'" : '';
		// realizar consulta
		$queryId = $this->query("BEGIN; SELECT * FROM $procedure($parameters); FETCH ALL FROM c_result;");
		$col = pg_fetch_all_columns($queryId);
		$this->query("END;");
		// liberar datos de la consulta
		pg_free_result($queryId);
		// retornar tabla
		return $col;
	}

	/**
	 * Asigna un límite para la obtención de filas en la consulta SQL
	 * @param sql Consulta SQL a la que se le agrega el límite
	 * @return String Consulta con el límite agregado
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-09-09
	 */
	public function setLimit ($sql, $records, $offset = 0) {
		return $sql.' LIMIT '.(integer)$records.' OFFSET '.(integer)$offset;
	}

	/**
	 * Genera filtro para utilizar like en la consulta SQL
	 * @param colum Columna por la que se filtrará (se sanitiza)
	 * @param value Valor a buscar mediante like (se sanitiza)
	 * @return String Filtro utilizando like
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-11-11
	 */
	public function like ($column, $value) {
		return $this->sanitize($column).' ILIKE \'%'.$this->sanitize($value).'%\'';
	}

	/**
	 * Concatena los parámetros pasados al método
	 * 
	 * El método acepta n parámetros, pero dos como mínimo deben ser
	 * pasados.
	 * @param par1 Parámetro 1 que se quiere concatenar
	 * @param par2 Parámetro 2 que se quiere concatenar
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-09-09
	 */
	public function concat ($par1, $par2) {
		$separators = array(' ', ',', ', ', '-', ' - ', '|');
		$concat = array();
		$parameters = func_get_args();
		foreach($parameters as &$parameter) {
			if(in_array($parameter, $separators))
				$parameter = "'".$parameter."'";
			array_push($concat, $parameter);
		}
		return implode(' || ', $concat);
	}
	
	/**
	 * Listado de tablas de la base de datos
	 * @return Array Arreglo con las tablas (nombre y comentario)
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-26
	 */
	public function getTables () {
		// obtener solo tablas del esquema indicado de la base de datos
		$tables = $this->getTable("
			SELECT t.table_name AS name
			FROM information_schema.tables AS t
			WHERE
				t.table_catalog = '".$this->config['name']."'
				AND t.table_schema = '".$this->config['sche']."'
				AND t.table_type = 'BASE TABLE'
			ORDER BY t.table_name
		");
		// buscar comentarios de las tablas
		foreach($tables as &$table) {
			$table['comment'] = $this->getCommentFromTable($table['name']);
		}
		// retornar tablas
		return $tables;
	}

	/**
	 * Obtener comentario de una tabla
	 * @param table Nombre de la tabla
	 * @return String Comentario de la tabla
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-26
	 */
	public function getCommentFromTable ($table) {
		return $this->getValue("
			SELECT d.description
			FROM information_schema.tables AS t, pg_catalog.pg_description AS d, pg_catalog.pg_class AS c
			WHERE
				t.table_catalog = '".$this->config['name']."'
				AND t.table_schema = '".$this->config['sche']."'
				AND c.relname = '".$table."'
				AND d.objoid = c.oid
				AND d.objsubid = 0
		");
	}
	
	/**
	 * Listado de columnas de una tabla (nombre, tipo, largo máximo, si
	 * puede tener un valor nulo y su valor por defecto)
	 * @param table Tabla a la que se quiere buscar las columnas
	 * @return Array Arreglo con la información de las columnas
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-26
	 */
	public function getColsFromTable ($table) {
		// buscar columnas
		$cols = $this->getTable("
			SELECT
				c.column_name AS name
				, data_type as type
				, (CASE (SELECT c.character_maximum_length is null)
					WHEN true THEN c.numeric_precision
					ELSE c.character_maximum_length
				END) AS length
				, c.is_nullable AS null
				, c.column_default AS default
			FROM
				information_schema.columns as c
				, pg_class as t
				, pg_namespace
			WHERE
				c.table_catalog = '".$this->config['name']."'
				AND c.table_name = '".$table."'
				AND c.table_schema = '".$this->config['sche']."'
				AND t.relname = '".$table."'
				AND pg_namespace.nspname = '".$this->config['sche']."'
				AND pg_namespace.oid = t.relnamespace
			ORDER BY c.ordinal_position ASC
		");
		// buscar comentarios para las columnas
		foreach($cols as &$col) {
			$col['comment'] = $this->getValue("
				SELECT
					d.description
				FROM
					information_schema.columns as c
					, pg_description as d
					, pg_class as t
					, pg_namespace
				WHERE
					c.table_catalog = '".$this->config['name']."'
					AND c.table_name = '".$table."'
					AND c.column_name = '".$col['name']."'
					AND t.relname = '".$table."'
					AND pg_namespace.nspname = '".$this->config['sche']."'
					AND pg_namespace.oid = t.relnamespace
					AND d.objoid = t.oid
					AND d.objsubid = c.ordinal_position
			");
		}
		// retornar columnas
		return $cols;
	}
	
	/**
	 * Listado de claves primarias de una tabla
	 * @param table Tabla a buscar su o sus claves primarias
	 * @return Arreglo con la o las claves primarias
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-26
	 */
	public function getPksFromTable ($table) {
		return $this->getCol("
			SELECT column_name
			FROM information_schema.constraint_column_usage
			WHERE constraint_name = (
				SELECT relname
				FROM pg_class
				WHERE oid = (
					SELECT indexrelid
					FROM pg_index, pg_class, pg_namespace
					WHERE
						pg_class.relname='".$table."'
						AND  pg_namespace.nspname = '".$this->config['sche']."'
						AND pg_namespace.oid = pg_class.relnamespace
						AND pg_class.oid = pg_index.indrelid
						AND indisprimary = 't'
				)
			) AND table_catalog = '".$this->config['name']."' AND table_name = '".$table."'
		");
	}
	
	/**
	 * Listado de claves foráneas de una tabla
	 * @param table Tabla a buscar su o sus claves foráneas
	 * @return Arreglo con la o las claves foráneas
	 * @todo Claves foráneas de múltiples columnas dan problemas, constraint entre esquemas
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-09-09
	 */
	public function getFksFromTable ($table) {
		$fks = $this->getTable("
			SELECT
				kcu.column_name AS name
				, ccu.table_name AS table
				, ccu.column_name AS column
			FROM information_schema.constraint_column_usage as ccu, information_schema.key_column_usage as kcu
			WHERE
				ccu.table_catalog = '".$this->config['name']."'
				AND ccu.constraint_name = kcu.constraint_name
				AND ccu.constraint_name IN (
					SELECT constraint_name
					FROM information_schema.table_constraints
					WHERE
						table_name = '".$table."'
						-- AND constraint_schema = '".$this->config['sche']."'
						AND constraint_type = 'FOREIGN KEY'
				)
		");
		return is_array($fks) ? $fks : array();
	}

	/**
	 * Seleccionar una tabla con los nombres de las columnas
	 * @param sql Consulta SQL que se desea realizar
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-09-09
	 */
	public function getTableWithColsNames ($sql) {
		// variables para datos y claves
		$data = array();
		$keys = array();
		// realizar consulta
		$queryId = $this->query($sql);
		// obtener el nombre de las columnas y agregarlas a $data
		$ncolumnas = pg_num_fields($queryId);
		for($i=0; $i<$ncolumnas; ++$i)
			array_push($keys, pg_field_name($queryId, $i));
		array_push($data, $keys);
		unset($keys);
		// agregar las filas de la consulta
		while($rows = pg_fetch_array($queryId)) {
			$row = array();
			for($i=0; $i<$ncolumnas; ++$i) {
				// si es un blob se muestra solo su tipo
				if(preg_match('/blob/i', pg_field_type($queryId, $i)))
					array_push($row, '['.pg_field_type($queryId, $i).']');
				else
					array_push($row, $rows[$i]);
			}
			array_push($data, $row);
		}
		// liberar datos de la consulta
		pg_free_result($queryId);
		// retornar tabla
		return $data;
	}
	
	/**
	 * Exportar una consulta a un archivo CSV y descargar
	 *
	 * Se requiere que el usuario que ejecuta COPY sea super usuario:
	 *	ALTER USER <usuario> WITH SUPERUSER;
	 *
	 * La cantidad de campos seleccionados en la query debe ser igual
	 * al largo del arreglo de columnas
	 * @param sql Consulta SQL
	 * @param file Nombre para el archivo que se descargará
	 * @param cols Arreglo con los nombres de las columnas (no usado)
	 * @todo Eliminar el archivo generado o bien generar uno random
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function toCSV ($sql, $file, $cols = null) {
		// definir ruta del archivo
		if(defined('TMP')) $tmp = TMP;
		else if(defined('DIR_TMP')) $tmp = DIR_TMP;
		else $tmp = sys_get_temp_dir();
		$file = $tmp.DIRECTORY_SEPARATOR.$file.'.csv';
		// realizar consulta
		$this->query("
			COPY (
				".$sql."
			) TO '".$file."' CSV HEADER
		");
		// enviar archivo
		ob_clean();
		header ('Content-Disposition: attachment; filename='.basename($file));
		header ('Content-Type: text/csv');
		header ('Content-Length: '.filesize($file));
		readfile ($file);
		exit;
	}

}
