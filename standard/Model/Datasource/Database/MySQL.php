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
 * Clase para trabajar con una base de datos MySQL
 * @todo Se deben completar los métodos para la clase
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-08
 */
class MySQL extends DatabaseManager {
	
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
		// verificar que existe el soporte para MySQL en PHP
		if (!function_exists('mysqli_init')) {
			$this->error ('No se encontró la extensión de PHP para MySQL (mysqli)');
		}
		// definir configuración para el acceso a la base de datos
		$this->config = array_merge(array(
			'host' => 'localhost',
			'port' => '3306',
			'char' => 'utf8',
		), $config);
		// realizar conexión a la base de datos
		$this->link = mysqli_init();
		$conexion = @mysqli_real_connect(
			$this->link,
			$this->config['host'],
			 $this->config['user'],
			$this->config['pass'],
			$this->config['name'],
			$this->config['port']
		);
		if (!$conexion) {
			$this->error(
				'¡No fue posible conectar con la base de datos!<br/>'.
				'Número de error: '.mysqli_connect_errno().'<br/>'.
				'Error: '.mysqli_connect_error()
				
			);
		}
		unset($conexion);
		// establecer charset para la conexion
		mysqli_set_charset($this->link, $this->config['char']);
	}

	/**
	 * Destructor de la clase
	 * 
	 * Cierra la conexión con la base de datos.
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function __destruct () {
		// si el identificador es un recurso de MySQL se cierra
 		if (get_class($this->link)=='mysqli') {
			mysqli_close ($this->link);
		}
	}
	
	/**
	 * Realizar consulta en la base de datos
	 * @param sql Consulta SQL que se desea realizar
	 * @param multi =true se utilizara mysqli_multi_query
	 * @return Resource Identificador de la consulta
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-03-08
	 */
	public function query ($sql, $multi = false) {
		// verificar que exista una consulta
		if(empty($sql)) {
			$this->error('¡Consulta no puede estar vacía!');
		}
		// realizar consulta
		if ($multi) $queryId = mysqli_multi_query($this->link, $sql);
		else $queryId = mysqli_query($this->link, $sql);
		// si hubo error al realizar la consulta se muestra y termina el
		// script
		if(!$queryId) {
			$this->error(
				$sql."\n".mysqli_error ($this->link)
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
	 * @version 2013-10-22
	 */
	public function getTable ($sql) {
		// realizar consulta
		$queryId = $this->query($sql);
		// procesar resultado de la consulta
		$table = array();
		while($row = mysqli_fetch_assoc ($queryId)) {
			array_push($table, $row);
		}
		// liberar datos de la consulta
		mysqli_free_result($queryId);
		// retornar tabla
		return $table;
	}
	
	/**
	 * Obtener una sola fila desde la base de datos
	 * @param sql Consulta SQL que se desea realizar
	 * @return Array Arreglo unidimensional con la fila
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function getRow ($sql) {
		// realizar consulta
		$queryId = $this->query($sql);
		// procesar resultado de la consulta
		$row = mysqli_fetch_assoc ($queryId);
		// liberar datos de la consulta
		mysqli_free_result($queryId);
		// retornar fila
		return is_array($row) ? $row : array();
	}

	/**
	 * Obtener una sola columna desde la base de datos
	 * @param sql Consulta SQL que se desea realizar
	 * @return Array Arreglo unidimensional con la columna
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function getCol ($sql) {
		// realizar consulta
		$queryId = $this->query($sql);
		// procesar resultado de la consulta
		$cols = array();
		while($row = mysqli_fetch_assoc ($queryId)) {
			array_push($cols, array_pop($row));
		}
		// liberar datos de la consulta
		mysqli_free_result($queryId);
		// retornar columnas
		return $cols;
	}

	/**
	 * Obtener un solo valor desde la base de datos
	 * @param sql Consulta SQL que se desea realizar
	 * @return Mixed Valor devuelto
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function getValue ($sql) {
		// realizar consulta
		$queryId = $this->query($sql);
		// procesar resultado de la consulta
		$row = mysqli_fetch_assoc ($queryId);
		// liberar datos de la consulta
		mysqli_free_result($queryId);
		// retornar fila
		return is_array($row) ? array_pop($row) : '';
	}
	
	/**
	 * Método que limpia el string recibido para hacer la consulta en la
	 * base de datos de forma segura
	 * @param string String que se desea limpiar
	 * @param trim Indica si se deben o no quitar los espacios
	 * @return String String limpiado
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function sanitize ($string, $trim = true) {
		if ($trim)
			$string = trim($string);
		return mysqli_real_escape_string ($this->link, $string);
	}

	/**
	 * Iniciar transacción
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function transaction () {
		$this->query ('START TRANSACTION');
	}
	
	/**
	 * Confirmar transacción
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function commit () {
		$this->query ('COMMIT');
	}
	
	/**
	 * Cancelar transacción
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function rollback () {
		$this->query ('ROLLBACK');
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
		$parameters = isset($parameters[0]) ? "'".implode("', '", $parameters)."'" : '';
		return $this->getValue("CALL $procedure($parameters)");
	}
	
	/**
	 * Obtener una tabla mediante un procedimiento almacenado
	 * @param procedure Procedimiento almacenado que se desea ejecutar
	 * @return Array Arreglo bidimensional con la tabla y sus datos
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function getTableFromSP ($procedure) {
		$data = array();
		$parameters = func_get_args();
		$procedure = $this->sanitize(array_shift($parameters));
                foreach($parameters as &$parameter)
			$parameter = $this->sanitize($parameter);
		$parameters = isset($parameters[0]) ?
			"'".implode("', '", $parameters)."'" : '';
		$queryId = $this->query("CALL $procedure($parameters)", true);
		do {
			$result = mysqli_store_result($this->link);
			if ($result) {
				$resultData = array();
				while($row = mysqli_fetch_assoc($result)) {
					array_push($resultData, $row);
				}
				array_push($data, $resultData);
				mysqli_free_result($result);
			}
		} while(mysqli_more_results($this->link) && mysqli_next_result($this->link));
		if(!isset($data[1])) $data = array_pop($data); // si habia un puro result set se devuelve una tabla
		return $data;
	}

	/**
	 * Obtener una sola fila mediante un procedimiento almacenado
	 * @param procedure Procedimiento almacenado que se desea ejecutar
	 * @return Array Arreglo unidimensional con la fila
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function getRowFromSP ($procedure) {
		$data = array();
		$parameters = func_get_args();
		$procedure = $this->sanitize(array_shift($parameters));
                foreach($parameters as &$parameter)
			$parameter = $this->sanitize($parameter);
		$parameters = isset($parameters[0]) ?
			"'".implode("', '", $parameters)."'" : '';
		$queryId = $this->query("CALL $procedure($parameters)", true);
		do {
			$result = mysqli_store_result($this->link);
			if ($result) {
				array_push ($data, mysqli_fetch_assoc($result));
				mysqli_free_result($result);
			}
		} while(mysqli_more_results($this->link) && mysqli_next_result($this->link));
		if(!isset($data[1])) $data = array_pop($data); // si habia un puro result set se devuelve una tabla
		return $data;
	}
	
	/**
	 * Obtener una sola columna mediante un procedimiento almacenado
	 * @param procedure Procedimiento almacenado que se desea ejecutar
	 * @return Array Arreglo unidimensional con la columna
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function getColFromSP ($procedure) {
		$data = array();
		$parameters = func_get_args();
		$procedure = $this->sanitize(array_shift($parameters));
                foreach($parameters as &$parameter)
			$parameter = $this->sanitize($parameter);
		$parameters = isset($parameters[0]) ?
			"'".implode("', '", $parameters)."'" : '';
		$queryId = $this->query("CALL $procedure($parameters)", true);
		do {
			$result = mysqli_store_result($this->link);
			if ($result) {
				$resultData = array();
				while($row = mysqli_fetch_assoc($result)) {
					array_push($resultData, array_pop($row));
				}
				array_push($data, $resultData);
				mysqli_free_result($result);
			}
		} while(mysqli_more_results($this->link) && mysqli_next_result($this->link));
		if(!isset($data[1])) $data = array_pop($data); // si habia un puro result set se devuelve una tabla
		return $data;
	}

	/**
	 * Asigna un límite para la obtención de filas en la consulta SQL
	 * @param sql Consulta SQL a la que se le agrega el límite
	 * @return String Consulta con el límite agregado
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function setLimit ($sql, $records, $offset = 0) {
		return $sql.' LIMIT '.(int)$offset.','.(int)$records;
	}

	/**
	 * Genera filtro para utilizar like en la consulta SQL
	 * @param colum Columna por la que se filtrará (se sanitiza)
	 * @param value Valor a buscar mediante like (se sanitiza)
	 * @return String Filtro utilizando like
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function like ($column, $value) {
		return "$colum LIKE '%".$this->sanitize($value)."%'";
	}

	/**
	 * Concatena los parámetros pasados al método
	 * 
	 * El método acepta n parámetros, pero dos como mínimo deben ser
	 * pasados.
	 * @param par1 Parámetro 1 que se quiere concatenar
	 * @param par2 Parámetro 2 que se quiere concatenar
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function concat ($par1, $par2) {
		$concat = array();
		$parameters = func_get_args();
		foreach($parameters as &$parameter) {
			if($parameter==' ' || $parameter==',' || $parameter==', ' || $parameter=='-' || $parameter==' - ')
				$parameter = "'".$parameter."'";
			array_push($concat, $parameter);
		}
		return 'CONCAT('.implode(', ', $concat).')';
	}
	
	/**
	 * Listado de tablas de la base de datos
	 * @return Array Arreglo con las tablas (nombre y comentario)
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-23
	 */
	public function getTables () {
		return $this->getTable ('
			SELECT table_name AS name, table_comment AS comment
			FROM information_schema.tables
			WHERE
				table_schema = "'.$this->config['name'].'"
				AND table_type != \'VIEW\'
			ORDER BY table_name
		');
	}

	/**
	 * Obtener comentario de una tabla
	 * @param table Nombre de la tabla
	 * @return String Comentario de la tabla
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function getCommentFromTable ($table) {
		return $this->getValue ('
			SELECT table_comment
			FROM information_schema.tables
			WHERE
				table_schema = "'.$this->config['name'].'"
				AND table_name = "'.$table.'"
		');
	}

	/**
	 * Listado de columnas de una tabla (nombre, tipo, largo máximo, si
	 * puede tener un valor nulo y su valor por defecto)
	 * @param table Tabla a la que se quiere buscar las columnas
	 * @return Array Arreglo con la información de las columnas
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function getColsFromTable ($table) {
		return $this->getTable ('
			SELECT
				column_name AS name
				, column_type AS type
				, IFNULL(character_maximum_length, numeric_precision) AS length
				, IF(STRCMP(is_nullable,"NO"),"YES","NO") AS `null`
				, column_default AS `default`
				, column_comment AS comment
				, extra
			FROM information_schema.columns
			WHERE
				table_schema = "'.$this->config['name'].'"
				AND table_name = "'.$table.'"
			ORDER BY ordinal_position ASC
		');
	}
	
	/**
	 * Listado de claves primarias de una tabla
	 * @param table Tabla a buscar su o sus claves primarias
	 * @return Arreglo con la o las claves primarias
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function getPksFromTable ($table) {
		return $this->getCol ('
			SELECT column_name
			FROM information_schema.key_column_usage
			WHERE
				constraint_schema = "'.$this->config['name'].'"
				AND table_name = "'.$table.'"
				AND constraint_name = "PRIMARY"
		');
	}
	
	/**
	 * Listado de claves foráneas de una tabla
	 * @param table Tabla a buscar su o sus claves foráneas
	 * @return Arreglo con la o las claves foráneas
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function getFksFromTable ($table) {
		$fks =  $this->getTable ('
			SELECT
				column_name AS name
				, referenced_table_name AS `table`
				, referenced_column_name AS `column`
			FROM
				information_schema.key_column_usage
			WHERE
				constraint_schema = "'.$this->config['name'].'"
				AND table_name = "'.$table.'"
				AND constraint_name in (
					SELECT constraint_name
					FROM information_schema.table_constraints
					WHERE
						constraint_schema = "'.$this->config['name'].'"
						AND table_name = "'.$table.'"
						AND constraint_type = "FOREIGN KEY")
		');
		return is_array($fks) ? $fks : array();
	}

	/**
	 * Seleccionar una tabla con los nombres de las columnas
	 * @param sql Consulta SQL que se desea realizar
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function getTableWithColsNames ($sql) {
		$data = array();
		$keys = array();
		$queryId = $this->query($sql);
		$ncolumnas = mysqli_num_fields($queryId);
		$fields = mysqli_fetch_fields($queryId);
		for($i=0; $i<$ncolumnas; ++$i)
			array_push($keys, $fields[$i]->name);
		array_push($data, $keys);
		unset($keys);
		while($rows = mysqli_fetch_array($queryId)) {
			$row = array();
			for($i=0; $i<$ncolumnas; ++$i) {
				if(preg_match('/blob/i', $fields[$i]->type)) // si es un blob no se muestra el contenido en la web
					array_push($row, '['.$fields[$i]->type.']');
				else
					array_push($row, $rows[$i]);
			}
			array_push($data, $row);
		}
		mysqli_free_result($queryId);
		unset($sql, $nfilas, $i, $value, $row);
		return $data;
	}
	
	/**
	 * Exportar una consulta a un archivo CSV y descargar
	 *
	 * La cantidad de campos seleccionados en la query debe ser igual
	 * al largo del arreglo de columnas
	 *
	 * Se requieren permisos para que el usuario pueda copiar a un CSV:
	 * 	GRANT FILE ON *.* TO '<usuario>'@'localhost';
	 * Tiene que ser *.* (no hay otra opción)
	 *
	 * @param sql Consulta SQL
	 * @param file Nombre para el archivo que se descargará
	 * @param cols Arreglo con los nombres de las columnas a utilizar en la
	 * tabla
	 * @todo Eliminar el archivo generado o bien generar uno random
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function toCSV ($sql, $file, $cols) {
		// definir ruta del archivo
		if(defined('TMP')) $tmp = TMP;
		else if(defined('DIR_TMP')) $tmp = DIR_TMP;
		else $tmp = sys_get_temp_dir();
		$file = $tmp.DIRECTORY_SEPARATOR.$file.'.csv';
		// si no se paso el arreglo con columnas se obtiene
		if(!is_array($cols)) {
			$colsInfo = $this->getColsFromTable($cols);
			$cols = array();
			foreach($colsInfo as &$col)
				$cols[] = $col['name'];
		}
		// realizar consulta
		$this->query('
			SELECT "'.implode('", "', $cols).'"
			UNION
			'.$sql.'
			INTO OUTFILE "'.$file.'"
				FIELDS TERMINATED BY ";"
				LINES TERMINATED BY "\r\n"
		');
		// enviar archivo
		ob_clean();
		header ('Content-Disposition: attachment; filename='.basename($file));
		header ('Content-Type: text/csv');
		header ('Content-Length: '.filesize($file));
		readfile ($file);
		exit;
	}
	
}
