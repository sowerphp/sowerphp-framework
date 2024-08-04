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

namespace sowerphp\core;

use \Illuminate\Config\Repository;
use \sowerphp\core\Database_QueryBuilder as QueryBuilder;

/**
 * Clase abstracta para todos los modelos.
 *
 * Permite trabajar con varios registros de la tabla.
 */
abstract class Model_Plural
{

    /**
     * Se utiliza el trait de objetos para las funcionalidades básicas de un
     * objeto del modelo.
     */
    use Trait_Object;

    /**
     * Todos los metadatos del modelo y de los campos (atributos) del modelo.
     *
     * @var Repository
     */
    protected $meta;

    /**
     * Conexión a la base de datos asociada al modelo.
     *
     * @var Database_Connection
     */
    protected $db;

    /**
     * Constructor del modelo plural.
     */
    public function __construct(?Repository $meta = null)
    {
        // Asignar la configuración del modelo (metadatos) si no fue pasada.
        if ($meta === null) {
            $singularClass = app('inflector')->singularize(
                $this->getReflector()->getName()
            );
            $singularInstance = new $singularClass();
            $meta = $singularInstance->getMeta();
        }
        $this->meta = $meta;
        // Asignar la conexión a la base de datos.
        $this->db = $this->getDatabaseConnection();
    }

    /**
     * Entrega repositorio con los metadatos normalizados del modelo.
     *
     * @return Repository Repositorio con los metadatos para fácil uso.
     */
    public function getMeta(): Repository
    {
        return $this->meta;
    }

    /**
     * Recupera la conexión a la base de datos asociada al modelo.
     *
     * Si la conexión no existe se obtiene desde el servicio de bases de
     * datos.
     *
     * @return Database_Connection
     */
    public function getDatabaseConnection(): Database_Connection
    {
        if (!isset($this->db)) {
            $this->db = database($this->meta['model.db_name']);
        }
        return $this->db;
    }

    /**
     * Arma el query builder y lo retorna según los parámetros pasados.
     *
     * @param array $parameters Parámetros de búsqueda y obtención de registros.
     * @return Illuminate\Database\Query\Builder
     */
    public function query(array $parameters = []): QueryBuilder
    {
        // Inicializar el query builder para el modelo.
        $query = $this->getDatabaseConnection()->table(
            $this->meta['model.db_table']
        );
        if (empty($parameters)) {
            return $query;
        }
        // Aplicar filtros.
        $filters = $parameters['filters'] ?? [];
        if (!empty($filters)) {
            // Determinar columnas por las que se puede buscar.
            $searchableQuery = $parameters['searchable'] ?? [];
            $searchableReal = array_keys(array_filter(
                $this->meta['fields'],
                function ($config) {
                    return $config['db_column'] && $config['searchable'];
                }
            ));
            if (!empty($searchableQuery)) {
                $searchable = array_intersect(
                    $searchableQuery,
                    $searchableReal
                );
            } else {
                $searchable = $searchableReal;
            }
            // Agregar cada filtro pasado a la búsqueda de campos en el modelo.
            foreach ($filters as $field => $value) {
                if ($field == 'search') {
                    $fields = array_intersect_key(
                        $this->meta['fields'],
                        array_flip($searchable)
                    );
                    $query->whereGlobalSearch($fields, $value);
                    continue;
                }
                if (!in_array($field, $searchable)) {
                    continue;
                }
                $query->whereSmartFilter(
                    $field,
                    $value,
                    $this->meta['fields'][$field]
                );
            }
        }
        // Aplicar ordenamiento.
        if (empty($parameters['sort'])) {
            $parameters['sort'] = [];
            foreach ($this->meta['model.ordering'] as $ordering) {
                $column = $ordering;
                $order = 'asc';
                if ($column[0] == '-') {
                    $column = substr($column, 1);
                    $order = 'desc';
                }
                $parameters['sort'][] = [
                    'column' => $column,
                    'order' => $order,
                ];
            }
        }
        if (!empty($parameters['sort'])) {
            foreach ($parameters['sort'] as $sort) {
                $column = $sort['column'];
                if (strpos($column, '.') === false) {
                    $column = $query->from . '.' . $column;
                }
                $order = strtolower($sort['order']) == 'desc' ? 'desc' : 'asc';
                $query->orderBy($column, $order);
            }
        }
        // Aplicar paginación.
        if (
            isset($parameters['pagination']['page'])
            && isset($parameters['pagination']['limit'])
        ) {
            $page = $parameters['pagination']['page'];
            $limit = $parameters['pagination']['limit'];
            $query->skip(($page - 1) * $limit)->take($limit);
        }
        // Seleccionar las columnas deseadas si están especificadas.
        if (!empty($parameters['fields'])) {
            $selectFields = [];
            foreach ($parameters['fields'] as $field) {
                // Si no se indicó la tabla en la columna se asume que se pasó
                // el nombre del campo que no necesariamente es el nombre de la
                // columna en la base de datos. Por lo cual se busca la columna
                // de la base de datos. Si no existe, se omite el campo.
                if (strpos($field, '.') === false) {
                    $db_column = $this->meta['fields.' . $field . '.db_column'];
                    if (!$db_column) {
                        continue;
                    }
                    $field = $query->from . '.' . $db_column;
                }
                // Se agrega el campo a los que se seleccionarán.
                $selectFields[] = $field;
            }
            // Agregar columna solicitada.
            $query->select($selectFields);
        }
        // Debug de la consulta SQL generada.
        // dd($query->toSql(), $query->getBindings());
        // Entregar query builder.
        return $query;
    }

    /**
     * Entrega el listado de registros que sirven como opciones para una lista
     * desplegable (campo select) en un formulario o similar.
     *
     * @param array $parameters Parámetros de búsqueda y obtención de registros.
     * @return array Arreglo con el listado de opciones.
     */
    public function choices(array $parameters = []): array
    {
        $choices = $this->filter($parameters);
        return $choices;
    }

    /**
     * Realiza una búsqueda y cuenta los registros del.
     *
     * @param array $parameters Parámetros de búsqueda y obtención de registros.
     * @return int Cantidad de registros encontrados.
     */
    public function count(array $parameters): int
    {
        $query = $this->query($parameters);
        return $query->count();
    }

    /**
     * Entrega la cantidad de registros que hay en la tabla, hará uso
     * del whereStatement si no es null también de groupByStatement y
     * havingStatement.
     * @return int Cantidad de registros encontrados.
     */
    /*public function count(): int
    {
        // armar consulta
        $query = '
            SELECT COUNT(*)
            FROM ' . $this->meta['model.db_table']
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
        return (int)$this->getDatabaseConnection()->getValue($query, $this->queryVars);
    }*/

    /**
     * Realiza una búsqueda y obtiene registros del modelo.
     *
     * @param array $parameters Parámetros de búsqueda y obtención de registros.
     * @param bool $stdClass =true se entregará un objeto stdClass.
     * @return \Illuminate\Support\Collection|array
     */
    public function filter(array $parameters, $stdClass = false)
    {
        $query = $this->query($parameters);
        // Obtener los resultados.
        $results = $query->get();
        if ($stdClass) {
            return $results;
        }
        // Crear instancias del modelo para retornar.
        $class = $this->meta['model.singular'];
        $instances = [];
        foreach ($results as $result) {
            $instance = new $class();
            $instance->forceFill((array)$result);
            $instances[] = $instance;
        }
        return $instances;
    }

    /**
     * Obtener un recurso (registro) desde el modelo (base de datos).
     *
     * @param array $filters Filtros con la clave primaria del modelo.
     * @param bool $stdClass =true se entregará un objeto stdClass.
     * @return \stdClass|Model
     */
    public function retrieve(array $filters, bool $stdClass = false)
    {
        // Generar filtros con la PK.
        $results = $this->filter(['filters' => $filters], $stdClass);
        $n_results = count($results);
        // DoesNotExist
        if ($n_results === 0) {
            throw new \Exception(__(
                'No se encontró un registro para %s::retrieve(%s).',
                $this->meta['model.label'],
                implode(', ', array_values($filters))
            ), 404);
        }
        // MultipleObjectsReturned
        else if ($n_results > 1) {
            throw new \Exception(__(
                'Se obtuvo más de un registro para %s::retrieve(%s).',
                $this->meta['model.label'],
                implode(', ', array_values($filters))
            ), 409);
        }
        // Se encontró exactamente un resultado (como se espera para una PK).
        return $results[0];
    }






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
     * Entrega el valor máximo del campo solicitado, hará uso del
     * whereStatement si no es null.
     * @param campo Campo que se consultará
     * @return int|float|string Valor máximo del campo
     */
    public function getMax($campo)
    {
        $query = 'SELECT MAX('.$campo.') FROM '.$this->meta['model.db_table'];
        if ($this->whereStatement) {
            $query .= $this->whereStatement;
        }
        return $this->getDatabaseConnection()->getValue($query, $this->queryVars);
    }

    /**
     * Entrega el valor mínimo del campo solicitado, hará uso del
     * whereStatement si no es null
     * @param campo Campo que se consultará
     * @return int|float|string Valor mínimo del campo
     */
    public function getMin($campo)
    {
        $query = 'SELECT MIN('.$campo.') FROM '.$this->meta['model.db_table'];
        if ($this->whereStatement) {
            $query .= $this->whereStatement;
        }
        return $this->getDatabaseConnection()->getValue($query, $this->queryVars);
    }

    /**
     * Entrega la suma del campo solicitado, hará uso del whereStatement
     * si no es null
     * @param campo Campo que se consultará
     * @return int|float Suma de todos las filas en el campo indicado
     */
    public function getSum ($campo)
    {
        $query = 'SELECT SUM('.$campo.') FROM '.$this->meta['model.db_table'];
        if ($this->whereStatement) {
            $query .= $this->whereStatement;
        }
        return $this->getDatabaseConnection()->getValue($query, $this->queryVars);
    }

    /**
     * Entrega el promedio del campo solicitado, hará uso del
     * whereStatement si no es null
     * @param campo Campo que se consultará
     * @return int|float Valor promedio del campo
     */
    public function getAvg($campo)
    {
        $query = 'SELECT AVG('.$campo.') FROM '.$this->meta['model.db_table'];
        if ($this->whereStatement) {
            $query .= $this->whereStatement;
        }
        return $this->getDatabaseConnection()->getValue($query, $this->queryVars);
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
            $query = 'SELECT '.$this->selectStatement.' FROM '.$this->meta['model.db_table'];
        } else {
            $query = 'SELECT * FROM '.$this->meta['model.db_table'];
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
            $query = $this->getDatabaseConnection()->setLimit(
                $query,
                $this->limitStatementRecords,
                $this->limitStatementOffset
            );
        }
        // ejecutar
        if ($solicitado == 'objects' || $solicitado == 'table') {
            $tabla = $this->getDatabaseConnection()->getTable($query, $this->queryVars);
            if ($solicitado == 'objects') {
                // procesar tabla y asignar valores al objeto
                $objetos = [];
                // determinar nombre de la clase singular (se busca en el mismo namespace que la clase plural)
                if ($class === null) {
                    $aux = \sowerphp\core\Utility_Inflector::singularize(get_class($this));
                    $namespace = substr($aux, 0, strrpos($aux, '\\'));
                    $class = $namespace.'\Model_'.\sowerphp\core\Utility_Inflector::camelize($this->meta['model.db_table']);
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
            return $this->getDatabaseConnection()->getRow($query, $this->queryVars);
        } else if ($solicitado == 'col') {
            return $this->getDatabaseConnection()->getCol($query, $this->queryVars);
        } else if ($solicitado == 'value') {
            return $this->getDatabaseConnection()->getValue($query, $this->queryVars);
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
    public function get(...$id)
    {
        return model($this->meta['model.singular'], ...$id);
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
        $cols = array_keys($this->meta['model.singular']::$columnsInfo);
        $id = $cols[0];
        $glosa = in_array($this->meta['model.db_table'], $cols) ? $this->meta['model.db_table'] : $cols[1];
        return $this->getDatabaseConnection()->getTable('
            SELECT '.$id.' AS id, '.$glosa.' AS glosa
            FROM '.$this->meta['model.db_table'].'
            ORDER BY '.$glosa
        );
    }

}
