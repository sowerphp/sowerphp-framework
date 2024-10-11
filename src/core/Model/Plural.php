<?php

/**
 * SowerPHP: Simple and Open Web Ecosystem Reimagined for PHP.
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

use Illuminate\Config\Repository;
use Illuminate\Support\Collection;
use sowerphp\core\Database_QueryBuilder as QueryBuilder;

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
     * @var array|\Illuminate\Config\Repository
     */
    protected $metadata = [
        'model' => [
            'db_name' => 'default',
        ],
    ];

    /**
     * Conexión a la base de datos asociada al modelo.
     *
     * @var Database_Connection
     */
    protected $db;

    /**
     * Constructor del modelo plural.
     */
    public function __construct(?Repository $metadata = null)
    {
        // Asignar la configuración del modelo (metadatos) si no fue pasada.
        if ($metadata === null) {
            $singularClass = app('inflector')->singularize(
                $this->getReflector()->getName()
            );
            if (class_exists($singularClass)) {
                $singularInstance = new $singularClass();
                $metadata = $singularInstance->getMetadata();
            } else {
                $metadata = new Repository($this->metadata);
            }
        }
        $this->metadata = $metadata;
        // Asignar la conexión a la base de datos.
        $this->db = $this->getDatabaseConnection();
    }

    /**
     * Entrega el repositorio con los metadatos normalizados del modelo o un
     * valor dentro de los metadatos si se indica la llave.
     *
     * Si el modelo plural:
     *
     *   - Tiene un modelo singular asociado, entregará los metadatos del
     *     modelo singular.
     *   - No tiene un modelo singular asociado, entregará los metadatos del
     *     modelo plural.
     *
     * @param string|null $key Llave de búsqueda dentro de los metadatos.
     * @return Repository|mixed|null Repositorio con los metadatos o valor
     * solicitado si se especificó una llave con $key. `null` si se especificó
     * llave y no se encontró un valor en el repositorio.
     */
    public function getMetadata(?string $key = null)
    {
        // Si los metadatos no son un repositorio, se crean como repositorio.
        if (!($this->metadata instanceof Repository)) {
            $this->metadata = new Repository($this->metadata);
        }
        // Entregar todos los metadatos o la llave solicitada.
        return $key ? $this->metadata[$key] : $this->metadata;
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
            $this->db = database($this->getMetadata('model.db_name'));
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
        // Asignar ordenamiento por defecto del modelo si no fue solicitado.
        if (empty($parameters['sort'])) {
            $parameters['sort'] = [];
            foreach ($this->getMetadata('model.ordering') as $ordering) {
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
        // Obtejer objeto query usando el QueryBuilder y SmartQuery.
        return $this->getDatabaseConnection()->query()->smartQuery(
            $parameters,
            $this->getMetadata('model.db_table'),
            $this->getMetadata('fields')
        );
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
     * Realiza una búsqueda y cuenta los registros del modelo.
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
     * Realiza una búsqueda y obtiene registros del modelo.
     *
     * @param array $parameters Parámetros de búsqueda y obtención de registros.
     * @param bool $stdClass `true` se entregarán objetos stdClass en el
     * resultado, `false` (por defecto) se entregarán objetos del modelo
     * singular en el resultado.
     * @return Collection
     */
    public function filter(array $parameters, ?bool $stdClass = false): Collection
    {
        $query = $this->query($parameters);
        if (!$stdClass) {
            $query->setMapClass($this->getMetadata('model.singular'));
        }
        return $query->get();
    }

    /**
     * Obtener un recurso (registro) desde el modelo (base de datos).
     *
     * Este método difiere de get() porque:
     *
     *   - No busca por ID (PK), sino por cualquier filtro.
     *   - No usa la caché que usa get() al usar el Service_Model.
     *
     * @param array $filters Filtros con la clave primaria del modelo.
     * @param bool $stdClass =true se entregará un objeto stdClass.
     * @return \stdClass|Model
     */
    public function retrieve(array $filters, bool $stdClass = false)
    {
        // Generar filtros con la PK.
        $results = $this->filter(['filters' => $filters], $stdClass);
        $n_results = $results->count();
        // Excepción equivalente a: DoesNotExist.
        if ($n_results === 0) {
            throw new \Exception(__(
                'No se encontró un registro para %s::retrieve(%s).',
                $this->getMetadata('model.label'),
                implode(', ', array_values($filters))
            ), 404);
        }
        // Excepción equivalente a: MultipleObjectsReturned.
        else if ($n_results > 1) {
            throw new \Exception(__(
                'Se obtuvo más de un registro para %s::retrieve(%s).',
                $this->getMetadata('model.label'),
                implode(', ', array_values($filters))
            ), 409);
        }
        // Se encontró exactamente un resultado (como se espera para una PK).
        return $results[0];
    }

    /**
     * Obtiene una instancia de un registro (modelo singular).
     *
     * La ventaja es que se utiliza el caché del servicio de modelos
     * (Service_Model). O sea, si el registro ya había sido recuperado no se
     * vuelve a hacer la consulta a la base de datos.
     *
     * @param array ...$id Identificador del registro.
     */
    public function get(...$id): Model
    {
        return model($this->getMetadata('model.singular'), ...$id);
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
            FROM ' . $this->getMetadata('model.db_table')
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
        $query = 'SELECT MAX('.$campo.') FROM '.$this->getMetadata('model.db_table');
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
        $query = 'SELECT MIN('.$campo.') FROM '.$this->getMetadata('model.db_table');
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
        $query = 'SELECT SUM('.$campo.') FROM '.$this->getMetadata('model.db_table');
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
        $query = 'SELECT AVG('.$campo.') FROM '.$this->getMetadata('model.db_table');
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
            $query = 'SELECT '.$this->selectStatement.' FROM '.$this->getMetadata('model.db_table');
        } else {
            $query = 'SELECT * FROM '.$this->getMetadata('model.db_table');
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
                    $class = $namespace.'\Model_'.\sowerphp\core\Utility_Inflector::camelize($this->getMetadata('model.db_table'));
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
     * Método para obtener un listado de los objetos (usando id y glosa).
     *
     * El método de la clase abstracta asume que el campo glosa se llama
     * igual que la tabla, o sea se buscará id y tabla como campos,
     * donde id es la PK. Si estos no son, el método deberá ser
     * reescrito en la clase final.
     *
     * @return array Arreglo con el listado de registros en una tupla:
     * (id, name).
     */
    public function getList(): array
    {
        $id = $this->getMetadata('model.choices.id');
        $name = $this->getMetadata('model.choices.name');
        return $this->getDatabaseConnection()->getTable('
            SELECT ' . $id . ' AS id, ' . $name . ' AS name
            FROM ' . $this->getMetadata('model.db_table') . '
            ORDER BY ' . $name
        );
    }

}
