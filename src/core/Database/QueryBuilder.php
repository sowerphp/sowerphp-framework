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

use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Extensión del QueryBuilder de Illuminate para proveer macros personalizadas.
 *
 * Esta clase principalmente ofrece una macro que permite hacer filtros
 * avanzados de manera sencilla según la configuración de un campo del modelo.
 */
class Database_QueryBuilder extends QueryBuilder
{

    /**
     * Clase a la que se mapearán los resultados.
     *
     * Si no se especifica se entregarán con la clase estándar: \stdClass.
     *
     * @var string|null
     */
    protected $mapClass = null;

    /**
     * Operadores para los filtros de SmartFilter.
     *
     * @var array
     */
    protected $smartFilterOperators = [
        '!=',           // Distinto de X.
        '!',            // Distinto de X.
        '=',            // Igual a X.
        '>=',           // Mayor o igual que X.
        '<=',           // Menor o igual que X.
        '>',            // Mayor que X.
        '<',            // Menor que X.
        '^',            // Empieza por X.
        '~',            // Contiene a X.
        '$',            // Termina con X.
        'in:',          // Es algún el elemento Xi en el listado X.
        'notin:',       // No es ningún elemento Xi en el listado X.
        'between:',     // Entre dos valores.
        'notbetween:',  // No entre dos valores.
        'date:',        // Fecha específica, formato AAAAMMDD o AAMMDD.
        'year:',        // Año específico, formato AAAA o AA.
        'month:',       // Mes específico, formato AAAAMM o AAMM.
    ];

    /**
     * Arma el query builder y lo retorna según los parámetros pasados.
     *
     * @param array $parameters Parámetros de búsqueda y obtención de registros.
     * @return Database_QueryBuilder
     */
    public function smartQuery(
        array $parameters = [],
        ?string $table = null,
        array $fieldsConfig = []
    ): QueryBuilder
    {
        // Inicializar el query builder para el modelo.
        $query = $this->from($table);
        if (empty($parameters)) {
            return $query;
        }
        // Aplicar filtros.
        $filters = $parameters['filters'] ?? [];
        if (!empty($filters)) {
            // Determinar columnas por las que se puede buscar.
            $searchableQuery = $parameters['searchable'] ?? [];
            $searchableReal = array_keys(array_filter(
                $fieldsConfig,
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
                        $fieldsConfig,
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
                    $fieldsConfig[$field]
                );
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
                    $db_column = $fieldsConfig[$field]['db_column'] ?? null;
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
     * Añade una condición WHERE a la consulta para realizar una búsqueda
     * insensible a mayúsculas.
     *
     * Este método aplica una condición LIKE a una columna de la base de datos,
     * ignorando diferencias entre mayúsculas y minúsculas. Utiliza la función
     * SQL `LOWER` para convertir tanto la columna como el valor de búsqueda a
     * minúsculas antes de compararlos.
     *
     * @param string $column El nombre de la columna en la que se va a aplicar
     * la condición.
     * @param mixed $value El valor a buscar en la columna, que será convertido
     * a minúsculas.
     * @return self Retorna la instancia del Query Builder para permitir el
     * encadenamiento de métodos.
     */
    public function whereIlike(string $column, $value): self
    {
        // Normalizar el nombre de la columna.
        $column = $this->normalizeColumnName($column);

        // Aplicar filtro LIKE como si fuese ILIKE.
        $value = '%' . strtolower($value) . '%';
        return $this->whereRaw('LOWER(' . $column . ') LIKE ?', [$value]);
    }

    /**
     * Añade una condición WHERE global a la consulta para realizar búsquedas
     * en múltiples campos.
     *
     * Este método aplica una condición de búsqueda global que puede abarcar
     * múltiples campos de la tabla principal o de tablas relacionadas.
     * Primero, se añaden los JOIN necesarios para las tablas relacionadas.
     * Luego, se añaden las condiciones de búsqueda a cada campo especificado.
     *
     * @param array $fields Un array asociativo donde las claves son los
     * nombres de los campos y los valores son configuraciones adicionales.
     * @param mixed $value  El valor de búsqueda que se aplicará a todos los
     * campos.
     * @return self Retorna la instancia del Query Builder para permitir el
     * encadenamiento de métodos.
     */
    public function whereGlobalSearch(array $fields, $value): self
    {
        foreach ($fields as $field => $config) {
            if (
                !empty($config['relation'])
                && is_string($config['searchable'])
            ) {
                $this->join(
                    $config['belongs_to'],
                    $config['belongs_to'] . '.' . $config['related_field'],
                    '=',
                    $this->from . '.' . $config['db_column']
                );
            }
        }

        $this->where(function($query) use ($fields, $value) {
            foreach ($fields as $field => $config) {
                $query->orWhere(
                    function($subQuery) use ($field, $value, $config) {
                        $subQuery->whereSmartFilter($field, $value, $config);
                    }
                );
            }
        });

        return $this;
    }

    /**
     * Añade una condición WHERE a la consulta basada en un filtro inteligente.
     *
     * Este método aplica un filtro a una columna específica, permitiendo el
     * uso de diferentes operadores y manejando de manera flexible tipos de
     * datos y relaciones de llaves foráneas. El método soporta condiciones
     * como "igual a", "distinto de", "mayor que", "menor que", "nulo",
     * "no nulo", y más.
     *
     * @param string $column El nombre de la columna en la que se va a aplicar
     * la condición.
     * @param mixed $value El valor del filtro que puede incluir operadores
     * como '!=', '>=', '<=', '>', '<', 'null', '!null', etc.
     * @param array $config Configuración adicional para la columna, como
     * 'relation', 'belongs_to', 'related_field', 'db_column', y 'searchable'.
     * @return self Retorna la instancia del Query Builder para permitir el
     * encadenamiento de métodos.
     */
    public function whereSmartFilter(string $column, $value, array $config): self
    {
        // Si no se indicó la tabla en la columna se usa la tabla de la query.
        if (strpos($column, '.') === false) {
            $column = $this->from . '.' . $column;
        }

        // Se pide que la columna no sea NULL.
        if ($value == '!=null' || $value == '!null') {
            return $this->whereNotNull($column);
        }

        // Se pide que la columna sea NULL.
        if ($value === null || $value == '=null' || $value == 'null') {
            return $this->whereNull($column);
        }

        // Si la columna es una llave foránea se aplica el filtro como llave
        // foránea a los campos de la tabla relacionada que son "buscables".
        if (!empty($config['relation'])) {
            return $this->applyForeignKeySmartFilter($column, $value, $config);
        }

        // Verificar si el valor contiene filtros compuestos.
        if ($this->isCompositeFilter($value)) {
            return $this->applyCompositeSmartFilter($column, $value, $config);
        }

        // Aplicar el filtro a la columna mediante un operador.
        $filter = $this->extractOperatorAndValueFromSmartFilter($value);
        if ($filter !== null) {
            return $this->applyOperatorSmartFilter(
                $column, $filter['operator'], $filter['value'], $config
            );
        }

        // Aplicar el filtro directamente a la columna según su tipo.
        return $this->applyTypeSmartFilter($column, $value, $config);
    }

    /**
     * Verifica si el valor contiene operadores && o || para filtros compuestos.
     *
     * @param mixed $value
     * @return boolean
     */
    protected function isCompositeFilter($value): bool
    {
        return strpos($value, '&&') !== false || strpos($value, '||') !== false;
    }

    /**
     * Aplica los filtros de manera compuesta. Determina el árbol asociado al
     * filtro y lo aplica usando SmartFilter.
     *
     * @param string $column
     * @param mixed $value
     * @param array $config
     * @return self
     */
    protected function applyCompositeSmartFilter(string $column, $value, array $config): self
    {
        $tree = $this->parseCompositeFilter($value);
        return $this->applyTreeSmartFilter($column, $tree, $config);
    }

    /**
     * Aplica un filtro compuesto a una columna utilizando un árbol de
     * condiciones.
     *
     * Este método maneja la aplicación de filtros compuestos a una columna,
     * representados por un árbol de condiciones que puede incluir operadores
     * lógicos `AND` (&&) y `OR` (||). Los nodos del árbol pueden ser
     * condiciones simples o subárboles que representan combinaciones de
     * condiciones.
     *
     * @param string $column El nombre de la columna en la que se va a aplicar
     * la condición.
     * @param mixed $node El nodo del árbol de condiciones. Puede ser un string
     * que representa una condición simple o un array que representa un
     * subárbol de condiciones.
     * @param array $config Configuración adicional para la columna, como
     * 'cast' para definir el tipo de datos.
     * @return self Retorna la instancia del Query Builder para permitir el
     * encadenamiento de métodos.
     *
     * @example
     * // Aplicar un filtro compuesto a la columna "usuario".
     * $query->applyTreeSmartFilter('usuario', [
     *     'operator' => '||',
     *     'left' => '=delaf',
     *     'right' => '=esteban'
     * ], ['cast' => 'string']);
     * // SQL: WHERE ("usuario" = 'delaf' OR "usuario" = 'esteban')
     */
    protected function applyTreeSmartFilter(string $column, $node, array $config): self
    {
        // Nodo es NULL (no se procesa).
        if ($node === null) {
            return $this;
        }

        // Nodo hoja (condición simple).
        if (!is_array($node)) {
            return $this->whereSmartFilter($column, $node, $config);
        }

        // Operadores y nodos que se deben aplicar.
        $operator = $node['operator'];
        $left = $node['left'];
        $right = $node['right'];

        // Se realiza una operación AND.
        if ($operator === '&&') {
            return $this->where(function($query) use ($column, $left, $right, $config) {
                $query->applyTreeSmartFilter($column, $left, $config);
                $query->applyTreeSmartFilter($column, $right, $config);
            });
        }

        // Se realiza una operación OR.
        if ($operator === '||') {
            return $this->where(function($query) use ($column, $left, $right, $config) {
                $query->orWhere(function($subQuery) use ($column, $left, $config) {
                    $subQuery->applyTreeSmartFilter($column, $left, $config);
                })->orWhere(function($subQuery) use ($column, $right, $config) {
                    $subQuery->applyTreeSmartFilter($column, $right, $config);
                });
            });
        }

        // Por defecto se entrega la instancia del QueryBuilder.
        return $this;
    }

    /**
     * Convierte una expresión de filtro compuesta en una estructura de árbol.
     *
     * @param string $value La expresión de filtro compuesta.
     * @return array La estructura del árbol que representa la expresión de
     * filtro.
     */
    protected function parseCompositeFilter(string $value): array
    {
        $tokens = $this->tokenizeCompositeFilter($value);
        return $this->buildExpressionTree($tokens);
    }

    /**
     * Divide la expresión de filtro compuesta en tokens.
     *
     * @param string $value La expresión de filtro compuesta.
     * @return array Los tokens de la expresión.
     */
    protected function tokenizeCompositeFilter(string $value): array
    {
        $pattern = '/(\|\||&&|\(|\)|[^|\&\(\)\s]+)/';
        preg_match_all($pattern, $value, $matches);
        return $matches[0];
    }

    /**
     * Construye el árbol de expresiones a partir de los tokens.
     *
     * @param array $tokens Los tokens de la expresión.
     * @return array La estructura del árbol que representa la expresión de filtro.
     */
    protected function buildExpressionTree(array $tokens): array
    {
        $output = [];
        $operators = [];
        $precedence = ['||' => 1, '&&' => 2];

        $applyOperator = function() use (&$output, &$operators) {
            $operator = array_pop($operators);
            $right = array_pop($output);
            $left = array_pop($output);
            $output[] = ['operator' => $operator, 'left' => $left, 'right' => $right];
        };

        foreach ($tokens as $token) {
            if ($token === '(') {
                $operators[] = $token;
            } elseif ($token === ')') {
                while (end($operators) !== '(') {
                    $applyOperator();
                }
                array_pop($operators);
            } elseif (isset($precedence[$token])) {
                while (
                    !empty($operators) &&
                    end($operators) !== '(' &&
                    $precedence[end($operators)] >= $precedence[$token]
                ) {
                    $applyOperator();
                }
                $operators[] = $token;
            } else {
                $output[] = $token;
            }
        }

        while (!empty($operators)) {
            $applyOperator();
        }

        return $output[0];
    }

    /**
     * Aplica un filtro a una columna de llave foránea en la consulta.
     *
     * Este método maneja la aplicación de filtros a columnas que son llaves
     * foráneas, realizando los JOIN necesarios con las tablas relacionadas y
     * aplicando las condiciones de búsqueda en los campos especificados en la
     * configuración.
     *
     * @param string $column El nombre de la columna de la llave foránea en la
     * tabla principal.
     * @param mixed $value El valor del filtro que se aplicará a los campos de
     * la tabla relacionada.
     * @param array $config Configuración adicional para la columna,
     * incluyendo la información de la relación de la llave foránea.
     * Los parámetros esperados en la configuración son:
     *   - 'belongs_to': El nombre de la tabla relacionada.
     *   - 'related_field': El campo en la tabla relacionada que se usa para la
     *     relación.
     *   - 'searchable': Campos en la tabla relacionada que son buscables,
     *     separados por '|'.
     * @return self Retorna la instancia del Query Builder para permitir el
     * encadenamiento de métodos.
     */
    protected function applyForeignKeySmartFilter(
        string $column,
        $value,
        array $config
    ): self
    {
        // Si no se ha indicado cómo buscar en la llave foránea se hace una
        // búsqueda por igualdad con el campo y su valor en la misma tabla
        // (modelo).
        if (!is_string($config['searchable'])) {
            return $this->whereSmartFilter(
                $column,
                $value,
                array_merge($config, ['relation' => null])
            );
        }

        // JOIN con la tabla relacionada de la FK.
        $this->join(
            $config['belongs_to'],
            $config['belongs_to'] . '.' . $config['related_field'],
            '=',
            $column
        );

        // Aplicar el filtro en las columnas de la tabla relacionada.
        $this->where(function($query) use ($config, $value) {
            $searchable = explode('|', $config['searchable']);
            foreach ($searchable as $info) {
                list($fkColumn, $cast) = explode(':', $info);
                $fkConfig = ['cast' => $cast];
                $query->orWhere(
                    function($subQuery) use ($config, $fkColumn, $value, $fkConfig) {
                        $subQuery->whereSmartFilter(
                            $config['belongs_to'] . '.' . $fkColumn,
                            $value,
                            $fkConfig
                        );
                    }
                );
            }
        });

        // Entregar instancia para encadenar métodos.
        return $this;
    }

    /**
     * Extrae el operador y el valor de un filtro inteligente.
     *
     * Este método analiza una cadena de filtro para identificar y extraer el
     * operador y el valor asociado. Utiliza una lista de operadores definidos
     * en la propiedad `smartFilterOperators` para determinar cuál operador
     * está presente en el filtro y separarlo del valor.
     *
     * @param string $filter La cadena del filtro que contiene un operador y un
     * valor.
     * @return array|null Retorna un array asociativo con las claves 'operator'
     * y 'value' si se encuentra un operador válido, o `null` si no se
     * encuentra ningún operador válido.
     */
    protected function extractOperatorAndValueFromSmartFilter(string $filter): ?array
    {
        // Buscar operador y extraer valor si coincide con alguno de la
        // búsqueda.
        foreach ($this->smartFilterOperators as $operator) {
            $pattern = $operator[0] == '/'
                ? $operator
                : '/^(' . preg_quote($operator, '/') . ')\s*(.+)$/'
            ;
            if (preg_match($pattern, $filter, $matches)) {
                $value = trim($matches[2]);
                if ($value === '') {
                    return null;
                }
                return [
                    'operator' => $matches[1],
                    'value' => $value,
                ];
            }
        }

        // No se encontró un operador válido.
        return null;
    }

    /**
     * Aplica un filtro a una columna utilizando un operador específico.
     *
     * Este método maneja la aplicación de diferentes operadores en las
     * condiciones de búsqueda. Los operadores soportados incluyen: '=', '!=',
     * '>=', '<=', '>', '<', '^' (empieza por), '~' (contiene), y '$' (termina
     * con). Dependiendo del operador, se aplica la condición correspondiente a
     * la columna utilizando el método adecuado.
     *
     * @param string $column El nombre de la columna en la que se va a aplicar
     * la condición.
     * @param string $operator El operador que se va a usar en la condición
     * (por ejemplo, '=', '!=', '>', etc.).
     * @param mixed $value El valor del filtro que se comparará con la columna.
     * @param array $config Configuración adicional para la columna, como
     * 'cast' para definir el tipo de datos.
     * @return self Retorna la instancia del Query Builder para permitir el
     * encadenamiento de métodos.
     */
    protected function applyOperatorSmartFilter(
        string $column,
        string $operator,
        $value,
        array $config
    ): self
    {
        switch ($operator) {
            case '!=':
                return $this->whereOperatorSmartFilter(
                    $column, '!=', $value, $config
                );
            case '!':
                return $this->whereOperatorSmartFilter(
                    $column, '!=', $value, $config
                );
            case '=':
                return $this->whereOperatorSmartFilter(
                    $column, '=', $value, $config
                );
            case '>=':
                return $this->whereOperatorSmartFilter(
                    $column, '>=', $value, $config
                );
            case '<=':
                return $this->whereOperatorSmartFilter(
                    $column, '<=', $value, $config
                );
            case '>':
                return $this->whereOperatorSmartFilter(
                    $column, '>', $value, $config
                );
            case '<':
                return $this->whereOperatorSmartFilter(
                    $column, '<', $value, $config
                );
            case '^':
                return $this->whereIlikeSmartFilter(
                    $column, $value . '%', $config
                );
            case '~':
                return $this->whereIlikeSmartFilter(
                    $column, '%' . $value . '%', $config
                );
            case '$':
                return $this->whereIlikeSmartFilter(
                    $column, '%' . $value, $config
                );
            case 'in:':
                return $this->whereInSmartFilter($column, $value, $config);
            case 'notin:':
                return $this->whereNotInSmartFilter($column, $value, $config);
            case 'between:':
                return $this->whereBetweenSmartFilter(
                    $column, $value, $config
                );
            case 'notbetween:':
                return $this->whereNotBetweenSmartFilter(
                    $column, $value, $config
                );
            case 'date:':
                return $this->whereDateSmartFilter($column, $value, $config);
            case 'year:':
                return $this->whereYearSmartFilter($column, $value, $config);
            case 'month:':
                return $this->whereMonthSmartFilter($column, $value, $config);
            default:
                return $this->whereOperatorSmartFilter(
                    $column, '=', $value, $config
                );
        }
    }

    /**
     * Aplica un filtro con un operador específico a una columna, normalizando
     * el tipo de la columna.
     *
     * Este método aplica una condición WHERE a una columna utilizando el
     * operador especificado, normalizando el tipo de la columna según la
     * configuración. Si el valor es un número, se valida que el valor sea
     * numérico antes de aplicar la condición.
     *
     * @param string $column El nombre de la columna en la que se va a aplicar
     * la condición.
     * @param string $operator El operador que se va a usar en la condición
     * (por ejemplo, '=', '!=', '>', etc.).
     * @param mixed $value El valor del filtro que se comparará con la columna.
     * @param array $config Configuración adicional para la columna, como
     * 'cast' para definir el tipo de datos.
     * @return self Retorna la instancia del Query Builder para permitir el
     * encadenamiento de métodos.
     */
    protected function whereOperatorSmartFilter(
        string $column,
        string $operator,
        $value,
        array $config
    ): self
    {
        // Normalizar el tipo de la columna.
        $columnType = $this->normalizeColumnType($config);

        // Si el valor es un número se valida que el valor sea numérico antes
        // de poder asignar el valor.
        if (in_array($columnType, ['int', 'float'])) {
            if (is_numeric($value)) {
                return $this->where($column, $operator, $value);
            }
            return $this;
        }

        // Entregar asignación de reglas para query por defecto.
        return $this->where($column, $operator, $value);
    }

    /**
     * Aplica un filtro ILIKE a una columna, normalizando el tipo de la
     * columna.
     *
     * Este método aplica una condición WHERE a una columna utilizando un
     * filtro insensible a mayúsculas/minúsculas (ILIKE), normalizando el tipo
     * de la columna según la configuración. Para columnas de tipo string, el
     * valor se convierte a minúsculas y se utiliza LIKE. Para otros tipos
     * (como fechas), el valor se castea a texto y se utiliza LIKE.
     *
     * @param string $column El nombre de la columna en la que se va a aplicar
     * la condición.
     * @param mixed $value El valor del filtro que se comparará con la columna.
     * @param array $config Configuración adicional para la columna, como
     * 'cast' para definir el tipo de datos.
     * @return self Retorna la instancia del Query Builder para permitir el
     * encadenamiento de métodos.
     */
    protected function whereIlikeSmartFilter(string $column, $value, array $config): self
    {
        // Normalizar el nombre de la columna.
        $column = $this->normalizeColumnName($column);

        // Normalizar el tipo de la columna.
        $columnType = $this->normalizeColumnType($config);

        // Si es string se pasa a minúscula y se usa LIKE.
        if ($columnType == 'string') {
            $value = strtolower($value);
            return $this->whereRaw('LOWER(' . $column . ') LIKE ?', [$value]);
        }

        // Otros casos (como fechas) se castea a texto y se usa LIKE.
        else {
            return $this->whereRaw('CAST(' . $column . ' AS TEXT) LIKE ?', [$value]);
        }
    }

    /**
     * Aplica un filtro "IN" a una columna con valores normalizados.
     *
     * Este método maneja la aplicación de un filtro "IN" a una columna,
     * asegurando que los valores estén correctamente normalizados según el
     * tipo de la columna. Primero, divide la cadena de valores en una lista,
     * elimina los valores vacíos, y luego normaliza cada valor según el tipo
     * de la columna (entero o decimal). Finalmente, aplica la condición "IN"
     * a la consulta.
     *
     * @param string $column El nombre de la columna en la que se va a aplicar
     * la condición.
     * @param mixed $value La cadena de valores para el filtro "IN", separados
     * por un delimitador.
     * @param array $config Configuración adicional para la columna, como
     * 'cast' para definir el tipo de datos.
     * @return self Retorna la instancia del Query Builder para permitir el
     * encadenamiento de métodos.
     *
     * @example
     * // Aplicar filtro "IN" a la columna "id" con valores "1,2,3".
     * $query->whereInSmartFilter('id', '1,2,3', ['cast' => 'int']);
     * // SQL: WHERE "id" IN (1, 2, 3)
     */
    protected function whereInSmartFilter(string $column, $value, array $config): self
    {
        // Obtener lista de valores no vacíos.
        $values = array_filter(split_parameters($value), function ($v) {
            return $v !== null && $v !== '';
        });
        if (empty($values)) {
            return $this;
        }
        // Normalizar el tipo de la columna y normalizar valores según tipo.
        $columnType = $this->normalizeColumnType($config);
        if ($columnType == 'int') {
            $values = array_map('intval', $values);
        }
        if ($columnType == 'float') {
            $values = array_map('floatval', $values);
        }
        // Agregar filtro "IN".
        return $this->whereIn($column, $values);
    }

    /**
     * Aplica un filtro "NOT IN" a una columna con valores normalizados.
     *
     * Este método maneja la aplicación de un filtro "NOT IN" a una columna,
     * asegurando que los valores estén correctamente normalizados según el
     * tipo de la columna. Primero, divide la cadena de valores en una lista,
     * elimina los valores vacíos, y luego normaliza cada valor según el tipo
     * de la columna (entero o decimal). Finalmente, aplica la condición
     * "NOT IN" a la consulta.
     *
     * @param string $column El nombre de la columna en la que se va a aplicar
     * la condición.
     * @param mixed $value La cadena de valores para el filtro "NOT IN",
     * separados por un delimitador.
     * @param array $config Configuración adicional para la columna, como
     * 'cast' para definir el tipo de datos.
     * @return self Retorna la instancia del Query Builder para permitir el
     * encadenamiento de métodos.
     *
     * @example
     * // Aplicar filtro "NOT IN" a la columna "id" con valores "1,2,3".
     * $query->whereNotInSmartFilter('id', '1,2,3', ['cast' => 'int']);
     * // SQL: WHERE "id" NOT IN (1, 2, 3)
     */
    protected function whereNotInSmartFilter(string $column, $value, array $config): self
    {
        // Obtener lista de valores no vacíos.
        $values = array_filter(split_parameters($value), function ($v) {
            return $v !== null && $v !== '';
        });
        if (empty($values)) {
            return $this;
        }
        // Normalizar el tipo de la columna y normalizar valores según tipo.
        $columnType = $this->normalizeColumnType($config);
        if ($columnType == 'int') {
            $values = array_map('intval', $values);
        }
        if ($columnType == 'float') {
            $values = array_map('floatval', $values);
        }
        // Agregar filtro "NOT IN".
        return $this->whereNotIn($column, $values);
    }

    /**
     * Aplica un filtro "BETWEEN" a una columna con valores normalizados.
     *
     * Este método maneja la aplicación de un filtro "BETWEEN" a una columna,
     * asegurando que los valores estén correctamente normalizados según el
     * tipo de la columna. Primero, divide la cadena de valores en una lista,
     * elimina los valores vacíos y verifica que existan al menos dos valores.
     * Luego, normaliza cada valor según el tipo de la columna (entero o
     * decimal) y finalmente aplica la condición "BETWEEN" a la consulta.
     *
     * @param string $column El nombre de la columna en la que se va a aplicar
     * la condición.
     * @param mixed $value La cadena de valores para el filtro "BETWEEN",
     * separados por un delimitador.
     * @param array $config Configuración adicional para la columna, como
     * 'cast' para definir el tipo de datos.
     * @return self Retorna la instancia del Query Builder para permitir el
     * encadenamiento de métodos.
     *
     * @example
     * // Aplicar filtro "BETWEEN" a la columna "price" con valores "100,200".
     * $query->whereBetweenSmartFilter('price', '100,200', ['cast' => 'float']);
     * // SQL: WHERE "price" BETWEEN 100 AND 200
     */
    protected function whereBetweenSmartFilter($column, $value, $config)
    {
        // Obtener lista de valores no vacíos.
        $values = array_filter(split_parameters($value), function ($v) {
            return $v !== null && $v !== '';
        });
        if (!isset($values[1])) {
            return $this;
        }
        // Normalizar el tipo de la columna y normalizar valores según tipo.
        $columnType = $this->normalizeColumnType($config);
        if ($columnType == 'int') {
            $values = array_map('intval', $values);
        }
        if ($columnType == 'float') {
            $values = array_map('floatval', $values);
        }
        if ($columnType == 'carbon') {
            $pattern = '/^(?:\d{2}|\d{4})\d{2}\d{2}$/';
            for ($i=0; $i<2; $i++) {
                if (!preg_match($pattern, $values[$i]) || !is_numeric($values[$i])) {
                    return $this;
                }
                if (!isset($values[$i][6])) {
                    $values[$i] = '20' . $values[$i];
                }
                $year = substr($values[$i], 0, 4);
                $month = substr($values[$i], 4, 2);
                $day = substr($values[$i], 6, 2);
                $values[$i] = $year . '-' . $month . '-' . $day;
            }
            $values[1] .= ' 23:59:59';
        }
        // Agregar filtro BETWEEN"
        return $this->whereBetween($column, [$values[0], $values[1]]);
    }

    /**
     * Aplica un filtro "NOT BETWEEN" a una columna con valores normalizados.
     *
     * Este método maneja la aplicación de un filtro "NOT BETWEEN" a una
     * columna, asegurando que los valores estén correctamente normalizados
     * según el tipo de la columna. Primero, divide la cadena de valores en una
     * lista, elimina los valores vacíos y verifica que existan al menos dos
     * valores. Luego, normaliza cada valor según el tipo de la columna (entero
     * o decimal) y finalmente aplica la condición "NOT BETWEEN" a la consulta.
     *
     * @param string $column El nombre de la columna en la que se va a aplicar
     * la condición.
     * @param mixed $value La cadena de valores para el filtro "NOT BETWEEN",
     * separados por un delimitador.
     * @param array $config Configuración adicional para la columna, como
     * 'cast' para definir el tipo de datos.
     * @return self Retorna la instancia del Query Builder para permitir el
     * encadenamiento de métodos.
     *
     * @example
     * // Aplicar filtro "NOT BETWEEN" a la columna "price" con valores "100,200".
     * $query->whereBetweenSmartFilter('price', '100,200', ['cast' => 'float']);
     * // SQL: WHERE "price" NOT BETWEEN 100 AND 200
     */
    protected function whereNotBetweenSmartFilter($column, $value, $config)
    {
        // Obtener lista de valores no vacíos.
        $values = array_filter(split_parameters($value), function ($v) {
            return $v !== null && $v !== '';
        });
        if (!isset($values[1])) {
            return $this;
        }
        // Normalizar el tipo de la columna y normalizar valores según tipo.
        $columnType = $this->normalizeColumnType($config);
        if ($columnType == 'int') {
            $values = array_map('intval', $values);
        }
        if ($columnType == 'float') {
            $values = array_map('floatval', $values);
        }
        if ($columnType == 'carbon') {
            $pattern = '/^(?:\d{2}|\d{4})\d{2}\d{2}$/';
            for ($i=0; $i<2; $i++) {
                if (!preg_match($pattern, $values[$i]) || !is_numeric($values[$i])) {
                    return $this;
                }
                if (!isset($values[$i][6])) {
                    $values[$i] = '20' . $values[$i];
                }
                $year = substr($values[$i], 0, 4);
                $month = substr($values[$i], 4, 2);
                $day = substr($values[$i], 6, 2);
                $values[$i] = $year . '-' . $month . '-' . $day;
            }
            $values[1] .= ' 23:59:59';
        }
        // Agregar filtro BETWEEN"
        return $this->whereNotBetween($column, [$values[0], $values[1]]);
    }

    /**
     * Aplica un filtro de fecha específica a una columna.
     *
     * Este método maneja la aplicación de un filtro de fecha a una columna,
     * verificando que el valor esté en el formato estándar AAAAMMDD o AAMMDD,
     * y normalizando el tipo de la columna antes de aplicar el filtro.
     *
     * @param string $column El nombre de la columna en la que se va a aplicar
     * la condición.
     * @param mixed $value El valor del filtro en formato AAAAMMDD o AAMMDD.
     * @param array $config Configuración adicional para la columna, como
     * 'cast' para definir el tipo de datos.
     * @return self Retorna la instancia del Query Builder para permitir el
     * encadenamiento de métodos.
     *
     * @example
     * // Aplicar filtro de fecha a la columna "created_at" con valor "20230815".
     * $query->whereDateSmartFilter('created_at', '20230815', ['cast' => 'carbon']);
     * // SQL: WHERE YEAR("created_at") = 2023 AND MONTH("created_at") = 08 AND
     * // DAY("created_at") = 15
     */
    protected function whereDateSmartFilter($column, $value, $config)
    {
        // Obtener el valor del mes en el formato estándar: AAAAMMDD.
        $pattern = '/^(?:\d{2}|\d{4})\d{2}\d{2}$/';
        if (!preg_match($pattern, $value) || !is_numeric($value)) {
            return $this;
        }
        if (!isset($value[6])) {
            $value = '20' . $value;
        }
        // Normalizar el tipo de la columna.
        $columnType = $this->normalizeColumnType($config);
        // Aplicar el filtro según el tipo e columna.
        if ($columnType == 'carbon') {
            $year = (int)substr($value, 0, 4);
            $month = (int)substr($value, 4, 2);
            $day = (int)substr($value, 6, 2);
            return $this
                ->whereYear($column, $year)
                ->whereMonth($column, $month)
                ->whereDay($column, $day)
            ;
        }
        // Si no se puede aplicar el filtro retornar instancia solamente.
        return $this;
    }

    /**
     * Aplica un filtro de año específico a una columna.
     *
     * Este método maneja la aplicación de un filtro de año a una columna,
     * verificando que el valor esté en el formato estándar AAAA o AA,
     * y normalizando el tipo de la columna antes de aplicar el filtro.
     *
     * @param string $column El nombre de la columna en la que se va a aplicar
     * la condición.
     * @param mixed $value El valor del filtro en formato AAAA o AA.
     * @param array $config Configuración adicional para la columna, como
     * 'cast' para definir el tipo de datos.
     * @return self Retorna la instancia del Query Builder para permitir el
     * encadenamiento de métodos.
     *
     * @example
     * // Aplicar filtro de año a la columna "created_at" con valor "2023".
     * $query->whereYearSmartFilter('created_at', '2023', ['cast' => 'carbon']);
     * // SQL: WHERE YEAR("created_at") = 2023
     */
    protected function whereYearSmartFilter($column, $value, $config)
    {
        // Obtener el valor del mes en el formato estándar: AAAA.
        $pattern = '/^\d{2}|\d{4}$/';
        if (!preg_match($pattern, $value) || !is_numeric($value)) {
            return $this;
        }
        if (!isset($value[2])) {
            $value = '20' . $value;
        }
        // Normalizar el tipo de la columna.
        $columnType = $this->normalizeColumnType($config);
        // Aplicar el filtro según el tipo e columna.
        if ($columnType == 'carbon') {
            return $this->whereYear($column, (int)$value);
        }
        // Si no se puede aplicar el filtro retornar instancia solamente.
        return $this;
    }

    /**
     * Aplica un filtro de mes específico a una columna.
     *
     * Este método maneja la aplicación de un filtro de mes a una columna,
     * verificando que el valor esté en el formato estándar AAAAMM o AAMM,
     * y normalizando el tipo de la columna antes de aplicar el filtro.
     *
     * @param string $column El nombre de la columna en la que se va a aplicar
     * la condición.
     * @param mixed $value El valor del filtro en formato AAAAMM o AAMM.
     * @param array $config Configuración adicional para la columna, como
     * 'cast' para definir el tipo de datos.
     * @return self Retorna la instancia del Query Builder para permitir el
     * encadenamiento de métodos.
     *
     * @example
     * // Aplicar filtro de mes a la columna "created_at" con valor "202308".
     * $query->whereMonthSmartFilter('created_at', '202308', ['cast' => 'carbon']);
     * // SQL: WHERE YEAR("created_at") = 2023 AND MONTH("created_at") = 08
     */
    protected function whereMonthSmartFilter($column, $value, $config)
    {
        // Obtener el valor del mes en el formato estándar: AAAAMM.
        $pattern = '/^(?:\d{2}|\d{4})\d{2}$/';
        if (!preg_match($pattern, $value) || !is_numeric($value)) {
            return $this;
        }
        if (!isset($value[4])) {
            $value = '20' . $value;
        }
        // Normalizar el tipo de la columna.
        $columnType = $this->normalizeColumnType($config);
        // Aplicar el filtro según el tipo e columna.
        if ($columnType == 'carbon') {
            $year = (int)substr($value, 0, 4);
            $month = (int)substr($value, 4, 2);
            return $this
                ->whereYear($column, $year)
                ->whereMonth($column, $month)
            ;
        }
        // Si no se puede aplicar el filtro retornar instancia solamente.
        return $this;
    }

    /**
     * Aplica un filtro a una columna basado en su tipo de dato, normalizando
     * el tipo de la columna.
     *
     * Este método maneja la aplicación de filtros a columnas de diferentes
     * tipos de datos. Normaliza el tipo de la columna según la configuración y
     * aplica el filtro correspondiente. Soporta tipos de datos como string,
     * int, float, bool, y fechas (Carbon).
     *
     * @param string $column El nombre de la columna en la que se va a aplicar
     * la condición.
     * @param mixed $value El valor del filtro que se comparará con la columna.
     * @param array $config Configuración adicional para la columna, incluyendo
     * 'cast' para definir el tipo de datos.
     * @return self Retorna la instancia del Query Builder para permitir el
     * encadenamiento de métodos.
     */
    protected function applyTypeSmartFilter(string $column, $value, array $config): self
    {
        // Normalizar el nombre de la columna.
        $column = $this->normalizeColumnName($column);

        // Normalizar el tipo de la columna.
        $columnType = $this->normalizeColumnType($config);

        // Si es un campo de texto se filtrará con la macro ILIKE.
        if ($columnType == 'string') {
            return $this->whereIlike($column, $value);
        }

        // Si es un campo número entero se castea.
        if ($columnType == 'int') {
            if (is_numeric($value)) {
                return $this->where($column, '=', (int)$value);
            }
            return $this;
        }

        // Si es un campo número decimal se castea.
        if ($columnType == 'float') {
            if (is_numeric($value)) {
                return $this->where($column, '=', (float)$value);
            }
            return $this;
        }

        // Si es un campo booleano se castea.
        if ($columnType == 'bool') {
            if ($value === false || $value === 'false') {
                $value = 0;
            }
            if ($value === true || $value === 'true') {
                $value = 1;
            }
            if (is_numeric($value)) {
                return $this->where($column, '=', (bool)$value);
            }
            return $this;
        }

        // Si es un tipo fecha con hora se usará LIKE.
        if ($columnType == 'carbon') {
            $value = $value . '%';
            return $this->whereRaw('CAST(' . $column . ' AS TEXT) LIKE ?', [$value]);
        }

        // Si es cualquier otro caso se comparará con una igualdad.
        return $this->where($column, '=', $value);
    }

    /**
     * Normaliza el nombre de una columna para prevenir inyección SQL.
     *
     * Este método permite solo letras, números y guiones bajos en el nombre de
     * la columna, eliminando cualquier otro carácter.
     *
     * @param string $column El nombre de la columna a normalizar.
     * @return string El nombre de la columna normalizado.
     */
    protected function normalizeColumnName(string $column): string
    {
        return preg_replace('/[^a-zA-Z0-9_.]/', '', $column);
    }

    /**
     * Normaliza el tipo de una columna basado en la configuración
     * proporcionada.
     *
     * Este método analiza la configuración de una columna y normaliza su tipo
     * de dato a uno de los tipos soportados: 'string', 'int', 'float', 'bool',
     * 'carbon'. La configuración se espera que contenga un parámetro 'cast'
     * que define el tipo de dato.
     *
     * @param array $config La configuración de la columna, incluyendo el
     * parámetro 'cast'.
     * @return string|null Retorna el tipo de dato normalizado ('string',
     * 'int', 'float', 'bool', 'carbon') o null si el tipo no es reconocido.
     */
    protected function normalizeColumnType(array $config): ?string
    {
        $cast = explode(':', $config['cast'], 2)[0];
        if (in_array($cast, ['string'])) {
            return 'string';
        }
        if (in_array($cast, ['int', 'integer'])) {
            return 'int';
        }
        if (in_array($cast, ['float', 'real', 'double', 'decimal'])) {
            return 'float';
        }
        if (in_array($cast, ['bool', 'boolean'])) {
            return 'bool';
        }
        if (in_array($cast, ['date', 'datetime'])) {
            return 'carbon';
        }
        return null;
    }

    /**
     * Define la clase a la que se mapearán los resultados de la consulta.
     *
     * @param string $class El nombre de la clase a la que se mapearán los
     * resultados.
     * @return $this
     */
    public function setMapClass(string $class): self
    {
        $this->mapClass = $class;
        return $this;
    }

    /**
     * Ejecuta la consulta y obtiene los resultados.
     *
     * @param array|string $columns
     * @return \Illuminate\Support\Collection
     */
    public function get($columns = ['*']): \Illuminate\Support\Collection
    {
        $results = parent::get($columns);

        if ($this->mapClass) {
            return $results->map(function ($item) {
                $class = $this->mapClass;
                return (new $class())->forceFill((array)$item)->setExists(true);
            });
        }

        return $results;
    }

    /**
     * Obtiene un cursor para recorrer los resultados de la consulta.
     *
     * @return \Generator
     */
    public function cursor(): \Generator
    {
        foreach (parent::cursor() as $result) {
            if ($this->mapClass) {
                $class = $this->mapClass;
                yield (new $class())->forceFill((array)$result)->setExists(true);
            } else {
                yield $result;
            }
        }
    }

    /**
     * Obtiene un valor de una sola columna de la primera fila del resultado.
     *
     * @param string $column
     * @return mixed
     */
    public function value($column)
    {
        // Desactiva el mapeo temporalmente si está habilitado.
        $originalMapClass = $this->mapClass;
        $this->mapClass = null;

        // Obtiene el valor de la columna solicitada.
        $this->select($column);
        $result = parent::value($column);

        // Restaura el mapeo si era necesario.
        $this->mapClass = $originalMapClass;

        // Entregar el resultado (valor de la columna solicitada).
        return $result;
    }

    /**
     * Cuenta los registros de la consulta.
     *
     * Este método modifica el select actual para realizar la agregación de
     * conteo en la base de datos, eliminando cualquier selección existente
     * para evitar conflictos con selecciones previas (ej: tabla pivote).
     *
     * @param string $columns La columna a contar, por defecto '*' para contar todas.
     * @return int El número de registros encontrados.
     */
    public function count($columns = '*')
    {
        // Clona la consulta actual para modificarla sin afectar la original.
        $query = $this->clone();

        // Limpia la selección actual para evitar conflictos con selecciones
        // previas.
        $query->select($columns)
            ->resetBindings(['select'])
            ->setAggregate('count', [$columns])
        ;

        // Ejecuta la consulta y obtiene el conteo.
        $result = $query->get();

        // Devuelve el conteo obtenido.
        return $result->isEmpty() ? 0 : (int) $result->first()->aggregate;
    }

    /**
     * Resetea los bindings de la consulta.
     *
     * @param array $types Los tipos de bindings que deseas resetear.
     * Por defecto: todos.
     * @return self
     */
    public function resetBindings($types = []): self
    {
        if (empty($types)) {
            $this->bindings = [];
        } else {
            foreach ((array) $types as $type) {
                $this->bindings[$type] = [];
            }
        }

        return $this;
    }

}
