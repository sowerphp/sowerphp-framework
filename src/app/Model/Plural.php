<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
 * Copyright (C) SowerPHP <https://www.sowerphp.org>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero
 * de GNU publicada por la Fundación para el Software Libre, ya sea la
 * versión 3 de la Licencia, o (a su elección) cualquier versión
 * posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU
 * para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General
 * Affero de GNU junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace sowerphp\app;

/**
 * Clase abstracta para todos los modelos
 * Permite trabajar con varios registros de una tabla
 */
abstract class Model_Plural
{

    use \sowerphp\core\Trait_Object;

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table; ///< Tabla del modelo
    protected $_class; ///< Clase singular de la clase plural
    protected $db; ///< Conexión a base de datos

    // caché
    private static $objects = []; ///< caché para objetos singulares

    // Atributo con configuración para generar consultas SQL
    protected $selectStatement; ///< Columnas a consultar
    protected $whereStatement; ///< Condiciones para la consula
    protected $groupByStatement; ///< Campos para agrupar
    protected $havingStatement; ///< Condiciones de los campos agrupados
    protected $orderByStatement; ///< Orden de los resultados
    protected $limitStatementRecords; ///< Registros que se seleccionarán
    protected $limitStatementOffset; ///< Desde que fila se seleccionarán
    protected $queryVars = []; ///< Variables que se utilizarán en la query

    /**
     * Constructor de la clase abstracta
     */
    public function __construct()
    {
        // crear statement vacío
        $this->clear();
        // recuperar conexión a la base de datos
        $this->getDB();
        // setear nombre de la clase y de la tabla según la clase que se está usando
        if (empty($this->_class)) {
            $this->_class = \sowerphp\core\Utility_Inflector::singularize(get_class($this));
            /*if (!class_exists($this->_class)) {
                throw new \Exception('Clase '.$this->_class.' no existe (especificar singular de '.get_class($this).' manualmente)');
            }*/
        }
        if (empty($this->_table)) {
            $this->_table = \sowerphp\core\Utility_Inflector::underscore (
                $this->_class
            );
        }
    }

    /**
     * Método que recupera la conexión a la base de datos del objeto.
     * Si la conexión no existe se conecta.
     */
    protected function getDB()
    {
        if (!isset($this->db)) {
            $this->db = database($this->_database);
        }
        return $this->db;
    }

    /**
     * Método para limpiar los atributos que contienen las opciones para
     * realizar la consulta SQL
     * @param statement Statement que se quiere borrar (select, where, groupBy, having, orderBy, limitRecords o limitOffset), null para borrar todos
     */
    public function clear($statement = null)
    {
        if ($statement == null) {
            $this->selectStatement = null;
            $this->whereStatement = null;
            $this->groupByStatement = null;
            $this->havingStatement = null;
            $this->orderByStatement = null;
            $this->limitStatementRecords = null;
            $this->limitStatementOffset = null;
            $this->queryVars = [];
        }
        else if ($statement == 'select') {
            $this->selectStatement = null;
        } else if ($statement == 'where') {
            $this->whereStatement = null;
        } else if ($statement == 'groupBy') {
            $this->groupByStatement = null;
        } else if ($statement == 'having') {
            $this->havingStatement = null;
        } else if ($statement == 'orderBy') {
            $this->orderByStatement = null;
        } else if ($statement == 'limitRecords') {
            $this->limitStatementRecords = null;
        } else if ($statement == 'limitOffset') {
            $this->limitStatementOffset = null;
        } else if ($statement == 'queryVars') {
            $this->queryVars = [];
        }
    }

    /**
     * Ingresa las columnas que se seleccionarán en el select
     * @param selectStatement Arreglo con la(s) columna(s) que se desea seleccionar de la tabla
     */
    public function setSelectStatement(array $selectStatement)
    {
        $this->selectStatement = implode(',', $selectStatement);
        return $this;
    }

    /**
     * Ingresa las condiciones para utilizar en el where de la consulta sql
     * @param whereStatement Condiciones para el where de la consulta sql
     */
    public function setWhereStatement(array $whereStatement, array $whereVars = [])
    {
        $this->whereStatement = ' WHERE '.implode(' AND ', $whereStatement);
        $this->queryVars = array_merge($this->queryVars, $whereVars);
        return $this;
    }

    /**
     * Ingresa las columnas por las que se agrupara la consulta
     * @param groupByStatement Arreglo con la(s) columna(s) por la(s) que se desea agrupar la tabla
     */
    public function setGroupByStatement(array $groupByStatement)
    {
        $this->groupByStatement = ' GROUP BY '.implode(', ', $groupByStatement);
        return $this;
    }

    /**
     * Ingresa las condiciones para utilizar en el having de la consulta sql
     * @param havingStatement Condiciones para el having de la consulta sql
     */
    public function setHavingStatement(array $havingStatement, array $havingVars = [])
    {
        $this->havingStatement = ' HAVING '.implode(' AND ', $havingStatement);
        $this->queryVars = array_merge($this->queryVars, $havingVars);
        return $this;
    }

    /**
     * Ingresa los campos por los que se deberá ordenar
     * @param orderByStatement Columna/s de la tabla por la cual se ordenará
     */
    public function setOrderByStatement($orderByStatement)
    {
        if (is_array($orderByStatement)) {
            $order = [];
            foreach ($orderByStatement as $c => $o) {
                $order[] = $c.' '.$o;
            }
            $this->orderByStatement = ' ORDER BY '.implode(', ', $order);
        } else {
            $this->orderByStatement = ' ORDER BY '.$orderByStatement;
        }
        return $this;
    }

    /**
     * Ingresa las condiciones para hacer una seleccion de solo cierta cantidad de filas
     * @param records Cantidad de filas a mostrar (mayor que 0)
     * @param offset Desde que registro se seleccionara (default: 0)
     */
    public function setLimitStatement($records, $offset = 0)
    {
        if (+$records > 0) {
            $this->limitStatementRecords = +$records;
            $this->limitStatementOffset = +$offset;
        }
        return $this;
    }

    /**
     * Entrega la cantidad de registros que hay en la tabla, hará uso
     * del whereStatement si no es null también de groupByStatement y
     * havingStatement.
     * @return int Cantidad de registros encontrados.
     */
    public function count(): int
    {
        // armar consulta
        $query = '
            SELECT COUNT(*)
            FROM ' . $this->_table
        ;
        // si hay where se usa
        if ($this->whereStatement) {
            $query .= $this->whereStatement;
        }
        // en caso que se quiera usar el group by se hace una subconsulta
        if ($this->groupByStatement) {
            $query .= $this->groupByStatement;
            if ($this->havingStatement) {
                $query .= $this->havingStatement;
            }
            $query = "SELECT COUNT(*) FROM ($query) AS t";
        }
        // entregar resultados
        return (int)$this->db->getValue($query, $this->queryVars);
    }

    /**
     * Entrega el valor máximo del campo solicitado, hará uso del
     * whereStatement si no es null.
     * @param campo Campo que se consultará
     * @return int|float|string Valor máximo del campo
     */
    public function getMax($campo)
    {
        $query = 'SELECT MAX('.$campo.') FROM '.$this->_table;
        if ($this->whereStatement) {
            $query .= $this->whereStatement;
        }
        return $this->db->getValue($query, $this->queryVars);
    }

    /**
     * Entrega el valor mínimo del campo solicitado, hará uso del
     * whereStatement si no es null
     * @param campo Campo que se consultará
     * @return int|float|string Valor mínimo del campo
     */
    public function getMin($campo)
    {
        $query = 'SELECT MIN('.$campo.') FROM '.$this->_table;
        if ($this->whereStatement) {
            $query .= $this->whereStatement;
        }
        return $this->db->getValue($query, $this->queryVars);
    }

    /**
     * Entrega la suma del campo solicitado, hará uso del whereStatement
     * si no es null
     * @param campo Campo que se consultará
     * @return int|float Suma de todos las filas en el campo indicado
     */
    public function getSum ($campo)
    {
        $query = 'SELECT SUM('.$campo.') FROM '.$this->_table;
        if ($this->whereStatement) {
            $query .= $this->whereStatement;
        }
        return $this->db->getValue($query, $this->queryVars);
    }

    /**
     * Entrega el promedio del campo solicitado, hará uso del
     * whereStatement si no es null
     * @param campo Campo que se consultará
     * @return int|float Valor promedio del campo
     */
    public function getAvg($campo)
    {
        $query = 'SELECT AVG('.$campo.') FROM '.$this->_table;
        if ($this->whereStatement) {
            $query .= $this->whereStatement;
        }
        return $this->db->getValue($query, $this->queryVars);
    }

    /**
     * Recupera objetos desde la tabla, hará uso del whereStatement si
     * no es null, también de limitStatement, de orderbyStatement y de
     * selectStatement
     * @param solicitado Lo que se está solicitando (objetcs, table, etc)
     * @param class Se permite pasar el nombre de la clase en caso que se quieran recuperar objetos (si no se pasa se tratará de detectar)
     * @return mixed Arreglo o valor según lo solicitado
     */
    private function getData($solicitado, $class = null)
    {
        // preparar consulta inicial
        if ($this->selectStatement) {
            $query = 'SELECT '.$this->selectStatement.' FROM '.$this->_table;
        } else {
            $query = 'SELECT * FROM '.$this->_table;
        }
        // agregar where
        if ($this->whereStatement) {
            $query .= $this->whereStatement;
        }
        // agregar group by
        if ($this->groupByStatement) {
            $query .= $this->groupByStatement;
        }
        // agregar having
        if ($this->havingStatement) {
            $query .= $this->havingStatement;
        }
        // agregar order by
        if ($this->orderByStatement) {
            $query .= $this->orderByStatement;
        }
        // agregar limit
        if ($this->limitStatementRecords) {
            $query = $this->db->setLimit(
                $query,
                $this->limitStatementRecords,
                $this->limitStatementOffset
            );
        }
        // ejecutar
        if ($solicitado == 'objects' || $solicitado == 'table') {
            $tabla = $this->db->getTable($query, $this->queryVars);
            if ($solicitado == 'objects') {
                // procesar tabla y asignar valores al objeto
                $objetos = [];
                // determinar nombre de la clase singular (se busca en el mismo namespace que la clase plural)
                if ($class === null) {
                    $aux = \sowerphp\core\Utility_Inflector::singularize(get_class($this));
                    $namespace = substr($aux, 0, strrpos($aux, '\\'));
                    $class = $namespace.'\Model_'.\sowerphp\core\Utility_Inflector::camelize($this->_table);
                }
                // iterar creando objetos
                foreach ($tabla as &$fila) {
                    $obj = new $class();
                    $obj->set($fila);
                    if (method_exists($obj, '__init')) {
                        $obj->__init();
                    }
                    array_push($objetos, $obj);
                    unset($fila);
                }
                return $objetos;
            } else {
                return $tabla;
            }
        } else if ($solicitado == 'row') {
            return $this->db->getRow($query, $this->queryVars);
        } else if ($solicitado == 'col') {
            return $this->db->getCol($query, $this->queryVars);
        } else if ($solicitado == 'value') {
            return $this->db->getValue($query, $this->queryVars);
        }
    }

    /**
     * Recupera objetos desde la tabla, hará uso del whereStatement si
     * no es null, también de limitStatement, de orderbyStatement y de
     * selectStatement
     * @param class Clase que se debe usar para instanciar los objetos recuperados de la BD
     * @return array Arreglo con los objetos
     */
    public function getObjects($class = null)
    {
        return $this->getData('objects', $class);
    }

    /**
     * Recupera una tabla con las columnas y filas de la tabla en la BD
     * hará uso del whereStatement si no es null, también de
     * limitStatement, de orderbyStatement y de selectStatement
     * @return array Arreglo con filas y columnas de la tabla
     */
    public function getTable ()
    {
        return $this->getData('table');
    }

    /**
     * Recupera una fila con las columnas de la tabla, hará uso del
     * whereStatement si no es null, también de limitStatement, de
     * orderbyStatement y de selectStatement
     * @return array Arreglo con columnas de la tabla
     */
    public function getRow()
    {
        return $this->getData('row');
    }

    /**
     * Recupera una columna de la tabla, hará uso del whereStatement si
     * no es null, también de limitStatement, de orderbyStatement y de
     * selectStatement
     * @return array Arreglo con la columna de la tabla
     */
    public function getCol()
    {
        return $this->getData('col');
    }

    /**
     * Recupera un valor de la tabla, hará uso del whereStatement si no
     * es null, también de limitStatement, de orderbyStatement y de
     * selectStatement
     * @return mixed Valor solicitado de la tabla
     */
    public function getValue()
    {
        return $this->getData('value');
    }

    /**
     * Método que permite obtener un objeto singular (clase singular). La
     * ventaja es que se utiliza caché, esto es, si el objeto ya había sido
     * recuperado no se vuelve a hacer la consulta a la base de datos.
     * @param pk Clave primaria del objeto (pueden ser varios parámetros)
     */
    public function get($pk)
    {
        $args = func_get_args();
        $key = implode('/', $args);
        if (!isset(self::$objects[$this->_class])) {
            self::$objects[$this->_class] = [];
        }
        if (!isset(self::$objects[$this->_class][$key])) {
            self::$objects[$this->_class][$key] =
                (new \ReflectionClass($this->_class))->newInstanceArgs($args)
            ;
        }
        return self::$objects[$this->_class][$key];
    }

    /**
     * Método para obtener un listado de los objetos (usando id y glosa)
     * El método de la clase abstracta asume que el campo glosa se llama
     * igual que la tabla, o sea se buscará id y tabla como campos,
     * donde id es la PK. Si estos no son, el método deberá ser
     * reescrito en la clase final.
     */
    public function getList()
    {
        $class = $this->_class;
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
