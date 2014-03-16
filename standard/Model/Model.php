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

// Interfaz que implementará la clase
App::import('Model/ModelInterface');

/**
 * Clase abstracta para todos los modelos
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2013-11-27
 */
abstract class Model extends Object implements ModelInterface {
	
	// Datos para la conexión a la base de datos
	protected $_database = 'default'; ///< Base de datos del modelo
	protected $_table; ///< Tabla del modelo
	protected $db; ///< Conexión a base de datos

	// Información de las columnas de la tabla en la base de datos
	public static $columnsInfo = array();
	
	/**
	 * Constructor de la clase abstracta
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	public function __construct () {
		// recuperar conexión a la base de datos
		$this->db = Database::get($this->_database);
		// setear nombre de la tabla según la clase que se está usando
		if (empty($this->_table)) {
			$this->_table = Inflector::underscore (
				get_class($this)
			);
		}
	}
	
	/**
	 * Método para convertir el objeto a un string, usará el atributo
	 * que tenga el mismo nombre que la tabla a la que está asociada
	 * esta clase. Si no existe el atributo se devolverá el nombre de la
	 * clase (en dicho caso, se debe sobreescribir en el modelo final)
	 * @return Nombre de la tabla asociada al modelo o la clase misma
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-11-02
	 */
	public function __toString () {
		if(isset($this->{$this->_table}))
			return $this->{$this->_table};
		else return get_class($this);
	}

	/**
	 * Método para setear los atributos de la clase
	 * @param array Arreglo con los datos que se deben asignar
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-02-05
	 */
	public function set ($array) {
		$class = get_class($this);
		foreach ($class::$columnsInfo as $a => $data) {
			if (isset($array[$a]))
				$this->$a = $array[$a];
		}
	}
	
	/**
	 * Método para guardar el objeto en la base de datos
	 * @return =true si todo fue ok, =false si se hizo algún rollback
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	public function save () {
		$this->db->transaction();
		if(!$this->beforeSave()) {
			$this->db->rollback();
			return false;
		}
		if($this->exists()) $status = $this->update();
		else $status = $this->insert();
		if(!$status || !$this->afterSave()) {
			$this->db->rollback();
			return false;
		}
		$this->db->commit();
		return true;
	}

	/**
	 * Método que permite editar una fila de la base de datos de manera
	 * simple desde desde fuera del modelo.
	 * @param columns Arreglo con las columnas a editar (como claves) y los nuevos valores
	 * @param pks Arreglo con las columnas PK (como claves) y los valores para decidir que actualizar
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-02-07
	 */
	public function edit ($columns, $pks = null) {
		// preparar set de la consulta
		$querySet = array ();
		foreach ($columns as $col => &$val) {
			if ($val===null) $val = 'NULL';
			else if ($val===true) $val = 'true';
			else if ($val===false) $val = 'false';
			else $val = '\''.$this->db->sanitize($val).'\'';
			$querySet[] = $col.' = '.$val;
		}
		// preparar PK de la consulta
		$queryPk = array();
		if ($pks===null) {
			$class = get_class($this);
			foreach ($class::$columnsInfo as $col => &$info) {
				if ($info['pk']) {
					$queryPk[] = $col.' = \''.$this->db->sanitize($this->$col).'\'';
				}
			}
		} else {
			foreach ($pks as $pk => &$val) {
				$queryPk[] = $pk.' = \''.$this->db->sanitize($val).'\'';
			}
		}
		// realizar consulta
		$this->db->query ('
			UPDATE '.$this->_table.'
			SET '.implode(', ', $querySet).'
			WHERE '.implode(' AND ', $queryPk)
		);
	}

	/**
	 * Se ejecuta automáticamente antes del save
	 * @return boolean Verdadero en caso de éxito
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	protected function beforeSave () {
		return true;
	}
	
	/**
	 * Se ejecuta automáticamente después del save
	 * @return boolean Verdadero en caso de éxito
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	protected function afterSave () {
		return true;
	}
	
	/**
	 * Se ejecuta automáticamente antes del insert
	 * @return boolean Verdadero en caso de éxito
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	protected function beforeInsert () {
		return true;
	}
	
	/**
	 * Se ejecuta automáticamente después del insert
	 * @return boolean Verdadero en caso de éxito
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	protected function afterInsert () {
		return true;
	}
	
	/**
	 * Se ejecuta automáticamente antes del update
	 * @return boolean Verdadero en caso de éxito
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	protected function beforeUpdate () {
		return true;
	}
	
	/**
	 * Se ejecuta automáticamente después del update
	 * @return boolean Verdadero en caso de éxito
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	protected function afterUpdate () {
		return true;
	}
	
	/**
	 * Se ejecuta automáticamente antes del delete
	 * @return boolean Verdadero en caso de éxito
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	protected function beforeDelete () {
		return true;
	}

	/**
	 * Se ejecuta automáticamente después del delete
	 * @return boolean Verdadero en caso de éxito
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	protected function afterDelete () {
		return true;
	}

}

/**
 * Clase abstracta para todos los modelos
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2013-06-10
 */
abstract class Models extends Object implements ModelsInterface {
	
	// Datos para la conexión a la base de datos
	protected $_database = 'default'; ///< Base de datos del modelo
	protected $_table; ///< Tabla del modelo
	protected $db; ///< Conexión a base de datos
	
	// Atributo con configuración para generar consultas SQL
	protected $selectStatement; ///< Columnas a consultar
	protected $whereStatement; ///< Condiciones para la consula
	protected $groupByStatement; ///< Campos para agrupar
	protected $havingStatement; ///< Condiciones de los campos agrupados
	protected $orderByStatement; ///< Orden de los resultados
	protected $limitStatementRecords; ///< Registros que se seleccionarán
	protected $limitStatementOffset; ///< Desde que fila se seleccionarán

	/**
	 * Constructor de la clase abstracta
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2013-06-10
	 */
	public function __construct () {
		// crear statement vacío
		$this->clear();
		// recuperar conexión a la base de datos
		$this->db = Database::get($this->_database);
		// setear nombre de la tabla según la clase que se está usando
		if (empty($this->_table)) {
			$this->_table = Inflector::underscore (
				Inflector::singularize(get_class($this))
			);
		}
	}
	
	/**
	 * Método para limpiar los atributos que contienen las opciones para
	 * realizar la consulta SQL
	 * @param statement Statement que se quiere borrar (select, where, groupBy, having, orderBy, limitRecords o limitOffset), nulo para borrar todos
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-22
	 */
	public function clear ($statement = null) {
		if($statement == null || $statement =='select') $this->selectStatement = null;
		if($statement == null || $statement =='where') $this->whereStatement = null;
		if($statement == null || $statement =='groupBy') $this->groupByStatement = null;
		if($statement == null || $statement =='having') $this->havingStatement = null;
		if($statement == null || $statement =='orderBy') $this->orderByStatement = null;
		if($statement == null || $statement =='limitRecords') $this->limitStatementRecords = null;
		if($statement == null || $statement =='limitOffset') $this->limitStatementOffset = null;
	}
	
	/**
	 * Ingresa las columnas que se seleccionarán en el select
	 * @param selectStatement Columna/s que se desea seleccionar de la tabla
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	public function setSelectStatement ($selectStatement) {
		$this->selectStatement = $selectStatement;
	}

	/**
	 * Ingresa las condiciones para utilizar en el where de la consulta sql
	 * @param whereStatement Condiciones para el where de la consulta sql
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	public function setWhereStatement ($whereStatement) {
		$this->whereStatement = ' WHERE '.$whereStatement;
	}

	/**
	 * Ingresa las columnas por las que se agrupara la consulta
	 * @param groupByStatement Columna/s por la que se desea agrupar la tabla
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	public function setGroupByStatement ($groupByStatement) {
		$this->groupByStatement = ' GROUP BY '.$this->db->sanitize($groupByStatement);
	}

	/**
	 * Ingresa las condiciones para utilizar en el having de la consulta sql
	 * @param havingStatement Condiciones para el having de la consulta sql
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	public function setHavingStatement ($havingStatement) {
		$this->havingStatement = ' HAVING '.$havingStatement;
	}
	
	/**
	 * Ingresa los campos por los que se deberá ordenar
	 * @param orderByStatement Columna/s de la tabla por la cual se ordenará
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	public function setOrderByStatement ($orderByStatement) {
		$this->orderByStatement = ' ORDER BY '.$this->db->sanitize($orderByStatement);
	}

	/**
	 * Ingresa las condiciones para hacer una seleccion de solo cierta cantidad de filas
	 * @param records Cantidad de filas a mostrar (mayor que 0)
	 * @param offset Desde que registro se seleccionara (default: 0)
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	public function setLimitStatement ($records, $offset = 0) {
		if ((integer)$records > 0) {
			$this->limitStatementRecords = $this->db->sanitize($records);
			$this->limitStatementOffset = $this->db->sanitize($offset);
		}
	}
	
	/**
	 * Wrapper para el método sanitize de la Base de datos
	 * @param string Valor que se desea limpiar
	 * @return String sanitizado
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-11-11
	 */
	public function sanitize ($string) {
		return $this->db->sanitize($string);
	}

	/**
	 * Wrapper para el método like de la Base de datos
	 * @param colum Columna por la que se filtrará (se sanitiza)
	 * @param value Valor a buscar mediante like (se sanitiza)
	 * @return String Filtro utilizando like
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-11-11
	 */
	public function like ($column, $value) {
		return $this->db->like($column, $value);
	}

	/**
	 * Entrega la cantidad de registros que hay en la tabla, hará uso
	 * del whereStatement si no es null también de groupByStatement y
	 * havingStatement
	 * @return integer Cantidad de registros encontrados
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	public function count () {
		// armar consulta
		$query = 'SELECT COUNT(*) FROM '.$this->_table;
		// si hay where se usa
		if ($this->whereStatement) $query .= $this->whereStatement;
		// en caso que se quiera usar el group by se hace una subconsulta
		if ($this->groupByStatement) {
			$query .= $this->groupByStatement;
			if ($this->havingStatement) $query .= $this->havingStatement;
			$query = "SELECT COUNT(*) FROM ($query) AS t";
		}
		// entregar resultados
		return $this->db->getValue($query);
	}

	/**
	 * Entrega el valor máximo del campo solicitado, hará uso del
	 * whereStatement si no es null.
	 * @param campo Campo que se consultará
	 * @return Numeric Valor máximo del campo
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	public function getMax ($campo) {
		$query = 'SELECT MAX('.$this->db->sanitize($campo).') FROM '.$this->_table;
		if ($this->whereStatement) $query .= $this->whereStatement;
		return $this->db->getValue($query);
	}

	/**
	 * Entrega el valor mínimo del campo solicitado, hará uso del
	 * whereStatement si no es null
	 * @param campo Campo que se consultará
	 * @return Numeric Valor mínimo del campo
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	public function getMin ($campo) {
		$query = 'SELECT MIN('.$this->db->sanitize($campo).') FROM '.$this->_table;
		if ($this->whereStatement) $query .= $this->whereStatement;
		return self::$bd->getValue($query);
	}

	/**
	 * Entrega la suma del campo solicitado, hará uso del whereStatement
	 * si no es null
	 * @param campo Campo que se consultará
	 * @return Numeric Suma de todos las filas en el campo indicado
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	public function getSum ($campo) {
		$query = 'SELECT SUM('.$this->db->sanitize($campo).') FROM '.$this->_table;
		if ($this->whereStatement) $query .= $this->whereStatement;
		return $this->db->getValue($query);
	}
	
	/**
	 * Entrega el promedio del campo solicitado, hará uso del
	 * whereStatement si no es null
	 * @param campo Campo que se consultará
	 * @return Numeric Valor promedio del campo
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	public function getAvg ($campo) {
		$query = 'SELECT AVG('.$this->db->sanitize($campo).') FROM '.$this->_table;
		if ($this->whereStatement) $query .= $this->whereStatement;
		return $this->db->getValue($query);
	}

	/**
	 * Recupera objetos desde la tabla, hará uso del whereStatement si
	 * no es null, también de limitStatement, de orderbyStatement y de
	 * selectStatement
	 * @param solicitado Lo que se está solicitando (objetcs, table, etc)
	 * @return Mixed Arreglo o valor según lo solicitado
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-12-27
	 */
	protected function get ($solicitado) {
		// preparar consulta inicial
		if($this->selectStatement)
			$query = 'SELECT '.$this->selectStatement.' FROM '.$this->_table;
		else
			$query = 'SELECT * FROM '.$this->_table;
		// agregar where
		if ($this->whereStatement) $query .= $this->whereStatement;
		// agregar group by
		if ($this->groupByStatement) $query .= $this->groupByStatement;
		// agregar having
		if ($this->havingStatement) $query .= $this->havingStatement;
		// agregar order by
		if ($this->orderByStatement) $query .= $this->orderByStatement;
		// agregar limit
		if ($this->limitStatementRecords) $query = $this->db->setLimit(
			$query,
			$this->limitStatementRecords,
			$this->limitStatementOffset
		);
		// ejecutar
		if($solicitado=='objects' || $solicitado=='table') {
			$tabla = $this->db->getTable($query);
			if($solicitado=='objects') {
				// procesar tabla y asignar valores al objeto
				$objetos = array();
				// determinar nombre de la clase
				$class = Inflector::camelize($this->_table);
				if(!class_exists($class)) $class = Inflector::singularize(get_class($this));
				// iterar creando objetos
				foreach($tabla as &$fila) {
					$obj = new $class();
					$obj->set($fila);
					array_push($objetos, $obj);
					unset($fila);
				}
				return $objetos;
			} else {
				return $tabla;
			}
		}
		else if($solicitado=='row') return $this->db->getRow($query);
		else if($solicitado=='col') return $this->db->getCol($query);
		else if($solicitado=='value') return $this->db->getValue($query);
	}

	/**
	 * Recupera objetos desde la tabla, hará uso del whereStatement si
	 * no es null, también de limitStatement, de orderbyStatement y de
	 * selectStatement
	 * @return Array Arreglo con los objetos
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	public function getObjects () {
		return $this->get('objects');
	}

	/**
	 * Recupera una tabla con las columnas y filas de la tabla en la BD
	 * hará uso del whereStatement si no es null, también de
	 * limitStatement, de orderbyStatement y de selectStatement
	 * @return Array Arreglo con filas y columnas de la tabla
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	public function getTable () {
		return $this->get('table');
	}

	/**
	 * Recupera una fila con las columnas de la tabla, hará uso del
	 * whereStatement si no es null, también de limitStatement, de
	 * orderbyStatement y de selectStatement
	 * @return Array Arreglo con columnas de la tabla
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	public function getRow() {
		return $this->get('row');
	}

	/**
	 * Recupera una columna de la tabla, hará uso del whereStatement si
	 * no es null, también de limitStatement, de orderbyStatement y de
	 * selectStatement
	 * @return Array Arreglo con la columna de la tabla
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	public function getCol() {
		return $this->get('col');
	}

	/**
	 * Recupera un valor de la tabla, hará uso del whereStatement si no
	 * es null, también de limitStatement, de orderbyStatement y de
	 * selectStatement
	 * @return Mixed Valor solicitado de la tabla
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2012-10-07
	 */
	public function getValue() {
		return $this->get('value');
	}

	/**
	 * Método para obtener un listado de los objetos (usando id y glosa)
	 * El método de la clase abstracta asume que el campo glosa se llama
	 * igual que la tabla, o sea se buscará id y tabla como campos,
	 * donde id es la PK. Si estos no son, el método deberá ser
	 * reescrito en la clase final.
	 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
	 * @version 2014-02-19
	 */
	public function getList () {
		$class = Inflector::singularize (get_class($this));
		$cols = array_keys($class::$columnsInfo);
		$id = $cols[0];
		$glosa = in_array($this->_table, $cols) ? $this->_table : $cols[1];
		return $this->db->getTable('
			SELECT '.$id.' AS id, '.$glosa.' AS glosa
			FROM '.$this->_table.'
			ORDER BY '.$glosa
		);
	}

}
