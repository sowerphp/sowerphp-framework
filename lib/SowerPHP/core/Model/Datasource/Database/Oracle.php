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
 * Clase para trabajar con una base de datos Oracle
 * 
 * Requerido:
 *   oci8: http://esteban.delaf.cl/sistemas-operativos/servicios/oracle/soporte-php
 * 
 * Para obtener SID de la base de datos desde comando SQL utilizar:
 *   select instance_name from v$instance;
 *
 * @todo Se deben completar los métodos para la clase
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-08
 */
class Oracle extends DatabaseManager {

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
		// verificar que existe el soporte para Oracle en PHP
		if (!function_exists('oci_connect')) {
			$this->error('No se encontró la extensión de PHP para Oracle (oci8)');
		}
		// definir configuración para el acceso a la base de datos
		$this->config = array_merge(array(
			'host' => 'localhost',
			'port' => '1521',
			'char' => 'utf8',
		), $config);
		// si no se especificó el servicio se asume que es NAME.HOST
		if(!isset($this->config['serv']))
			$this->config['serv'] = $this->config['name'].'.'.$this->config['host'];
		// realizar conexión a la base de datos
		// la forma de conectar utilizada es la de Oracle 11g
		// http://php.net/manual/es/function.oci-connect.php
		$this->link = @oci_connect(
			$this->config['user'],
			$this->config['pass'],
			$this->config['host'].':'.$this->config['port'].'/'.$this->config['serv'].'/'.$this->config['name'],
			$this->config['char']
		);
	}

	/**
	 * Destructor de la clase
	 * 
	 * Cierra la conexión con la base de datos.
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-10-22
	 */
	public function __destruct () {
		// si el identificador es un recurso de Oracle se cierra
		if(is_resource($this->link) && get_resource_type($this->link)=='oci8 connection') {
			oci_close($this->link);
		}
	}
	
	/**
	 * Realizar consulta en la base de datos
	 * @param sql Consulta SQL que se desea realizar
	 * @return Resource Identificador de la consulta
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-03-08
	 */
	public function query ($sql) {
		// verificar que exista una consulta
		if(empty($sql)) {
			$this->error('¡Consulta no puede estar vacía!');
		}
		// se prepara la consulta
		$queryId = oci_parse($this->link, $sql);
		// se verifican errores por oci_parse
		if (!$queryId) {
			$error = oci_error($this->link);
			$this->error($error['message'].'. '.$error['sqltext']);
		}
		// se realiza la consulta
		$queryRs = oci_execute($queryId);
		// se verifican errores por oci_execute
		if (!$queryRs) {
			$error = oci_error($this->link);
			$this->error($error['message'].'. '.$error['sqltext']);
		}
		// se retorna el id de la consulta
		return $queryId;
	}
	
	/**
	 * Obtener una tabla (como arreglo) desde la base de datos
	 * @param sql Consulta SQL que se desea realizar
	 * @return Array Arreglo bidimensional con la tabla y sus datos
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-26
	 */
	public function getTable ($sql) {
		$result = array();
		$queryId = $this->query($sql);
		while (($row = oci_fetch_array($queryId, OCI_ASSOC + OCI_RETURN_NULLS))) {
			array_push($result, $row);
		}
		oci_free_statement($queryId);
		return $result;
	}
	
	/**
	 * Obtener una sola fila desde la base de datos
	 * @param sql Consulta SQL que se desea realizar
	 * @return Array Arreglo unidimensional con la fila
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-26
	 */
	public function getRow ($sql) {
		return oci_fetch_array($this->query($sql), OCI_ASSOC + OCI_RETURN_NULLS);
	}

	/**
	 * Obtener una sola columna desde la base de datos
	 * @param sql Consulta SQL que se desea realizar
	 * @return Array Arreglo unidimensional con la columna
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-26
	 */
	public function getCol ($sql) {
		$result = array();
		$queryId = $this->query($sql);
		while($row = oci_fetch_array($queryId, OCI_ASSOC + OCI_RETURN_NULLS)) {
			array_push($result, array_pop($row));
		}
		oci_free_statement($queryId);
		return $result;
	}

	/**
	 * Obtener un solo valor desde la base de datos
	 * @param sql Consulta SQL que se desea realizar
	 * @return Mixed Valor devuelto
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-26
	 */
	public function getValue ($sql) {
		$row = oci_fetch_row($this->query($sql));
		return is_array($row) ? array_pop($row) : '';
	}
	
	/**
	 * Método que limpia el string recibido para hacer la consulta en la
	 * base de datos de forma segura
	 * @param string String que se desea limpiar
	 * @param trim Indica si se deben o no quitar los espacios
	 * @return String String limpiado
	 * @todo Implementar sanitizado
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-26
	 */
	public function sanitize ($string, $trim = true) {
		// se quitan espacios al inicio y final
		if($trim) $string = trim($string);
		// se proteje
		return $string;
	}

	/**
	 * Iniciar transacción
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-24
	 */
	public function transaction () {
		
	}
	
	/**
	 * Confirmar transacción
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-24
	 */
	public function commit () {
		
	}
	
	/**
	 * Cancelar transacción
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-24
	 */
	public function rollback () {
		
	}
	
	/**
	 * Ejecutar un procedimiento almacenado
	 * @param procedure Procedimiento almacenado que se quiere ejecutar
	 * @return Mixed Valor que retorna el procedimeinto almacenado
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-24
	 */
	public function exec ($procedure) {
		
	}
	
	/**
	 * Obtener una tabla mediante un procedimiento almacenado
	 * @param procedure Procedimiento almacenado que se desea ejecutar
	 * @return Array Arreglo bidimensional con la tabla y sus datos
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-24
	 */
	public function getTableFromSP ($procedure) {
		
	}

	/**
	 * Obtener una sola fila mediante un procedimiento almacenado
	 * @param procedure Procedimiento almacenado que se desea ejecutar
	 * @return Array Arreglo unidimensional con la fila
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-24
	 */
	public function getRowFromSP ($procedure) {
		
	}
	
	/**
	 * Obtener una sola columna mediante un procedimiento almacenado
	 * @param procedure Procedimiento almacenado que se desea ejecutar
	 * @return Array Arreglo unidimensional con la columna
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-24
	 */
	public function getColFromSP ($procedure) {
		
	}

	/**
	 * Asigna un límite para la obtención de filas en la consulta SQL
	 * @param sql Consulta SQL a la que se le agrega el límite
	 * @return String Consulta con el límite agregado
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-26
	 */
	public function setLimit ($sql, $records, $offset = 0) {
		return 'SELECT * FROM ('.$sql.') WHERE ROWNUM >= '.(integer)$offset.' AND ROWNUM <= '.((integer)$offset+(integer)$records);
	}

	/**
	 * Genera filtro para utilizar like en la consulta SQL
	 * @param colum Columna por la que se filtrará (se sanitiza)
	 * @param value Valor a buscar mediante like (se sanitiza)
	 * @return String Filtro utilizando like
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-26
	 */
	public function like ($column, $value) {
		return "$column LIKE '%".$this->sanitize($value)."%'";
	}

	/**
	 * Concatena los parámetros pasados al método
	 * 
	 * El método acepta n parámetros, pero dos como mínimo deben ser
	 * pasados.
	 * @param par1 Parámetro 1 que se quiere concatenar
	 * @param par2 Parámetro 2 que se quiere concatenar
	 * @todo Programar método
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-26
	 */
	public function concat ($par1, $par2) {
		
	}
	
	/**
	 * Listado de tablas de la base de datos
	 * @return Array Arreglo con las tablas (nombre y comentario)
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-26
	 */
	public function getTables () {
		// http://stackoverflow.com/questions/205736/oracle-get-list-of-all-tables
		return $this->getCol('SELECT table_name FROM user_tables');
	}

	/**
	 * Obtener comentario de una tabla
	 * @param table Nombre de la tabla
	 * @return String Comentario de la tabla
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-24
	 */
	public function getCommentFromTable ($table) {
		
	}
	
	/**
	 * Listado de columnas de una tabla (nombre, tipo, largo máximo, si
	 * puede tener un valor nulo y su valor por defecto)
	 * @param table Tabla a la que se quiere buscar las columnas
	 * @return Array Arreglo con la información de las columnas
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-24
	 */
	public function getColsFromTable ($table) {
		
	}
	
	/**
	 * Listado de claves primarias de una tabla
	 * @param table Tabla a buscar su o sus claves primarias
	 * @return Arreglo con la o las claves primarias
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-24
	 */
	public function getPksFromTable ($table) {
		
	}
	
	/**
	 * Listado de claves foráneas de una tabla
	 * @param table Tabla a buscar su o sus claves foráneas
	 * @return Arreglo con la o las claves foráneas
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-24
	 */
	public function getFksFromTable ($table) {
		
	}

	/**
	 * Seleccionar una tabla con los nombres de las columnas
	 * @param sql Consulta SQL que se desea realizar
	 * @todo Verificar que efectivamente exista campo de tipo blob y sea ese el que no se muestra en la web
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-24
	 */
	public function getTableWithColsNames ($sql) {
		$data = array();
		$keys = array();
		$queryId = $this->query($sql);
		$ncolumnas = oci_num_fields($queryId);
		for($i=1; $i<=$ncolumnas; ++$i)
			array_push($keys, oci_field_name($queryId, $i));
		array_push($data, $keys);
		unset($keys);
		while($rows = oci_fetch_array($queryId, OCI_NUM + OCI_RETURN_NULLS)) {
			$row = array();
			for($i=1; $i<=$ncolumnas; ++$i) {
				if(preg_match('/blob/i', oci_field_type($queryId, $i))) // si es un blob no se muestra el contenido en la web
					array_push($row, '['.oci_field_type($queryId, $i).']');
				else
					array_push($row, $rows[($i-1)]);
			}
			array_push($data, $row);
		}
		oci_free_statement($queryId);
		unset($sql, $nfilas, $i, $value, $row);
		return $data;
	}
	
	/**
	 * Exportar una consulta a un archivo CSV y descargar
	 *
	 * La cantidad de campos seleccionados en la query debe ser igual
	 * al largo del arreglo de columnas
	 * @param sql Consulta SQL
	 * @param file Nombre para el archivo que se descargará
	 * @param cols Arreglo con los nombres de las columnas a utilizar en la tabla
	 * @todo Probar que funcione
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-24
	 */
	public function toCSV ($sql, $file, $cols) {
		// realizar consulta
		/*$this->consulta('
			SELECT "'.implode('", "', $columnas).'"
			UNION
			'.$consulta.'
			INTO OUTFILE "'.TMP.'/'.$archivo.'.csv"
				FIELDS TERMINATED BY ";"
				LINES TERMINATED BY "\r\n"
		');*/
		$this->consulta("
			begin
			set colsep ,
			set pagesize 0
			set trimspool on
			set headsep off
			set linesize 200
			spool ".TMP."/".$archivo.".csv
			".$this->proteger($consulta).";
			spool off
			end;
		");
		// enviar archivo
		ob_clean();
		header ('Content-Disposition: attachment; filename='.$archivo.'.csv');
		header ('Content-Type: text/csv');
		header ('Content-Length: '.filesize(TMP.'/'.$archivo.'.csv'));
		readfile(TMP.'/'.$archivo.'.csv');
	}

}
