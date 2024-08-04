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

use \Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Extensión del QueryBuilder de Illuminate para proveer macros personalizadas.
 *
 * Esta clase principalmente ofrece una macro que permite hacer filtros
 * avanzados de manera sencilla según la configuración de un campo del modelo.
 */
class Database_QueryBuilder extends QueryBuilder
{

    /**
     * Operadores para los filtros de SmartFilter.
     *
     * @var array
     */
    protected $smartFilterOperators = [
        '!=',   // Distinto de X.
        '!',    // Distinto de X.
        '=',    // Igual a X.
        '>=',   // Mayor o igual que X.
        '<=',   // Menor o igual que X.
        '>',    // Mayor que X.
        '<',    // Menor que X.
        '^',    // Empieza por X.
        '~',    // Contiene a X.
        '$',    // Termina con X.
    ];

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
                !empty($config['foreign_key'])
                && is_string($config['searchable'])
            ) {
                $this->join(
                    $config['to_table'],
                    $config['to_table'] . '.' . $config['to_field'],
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
     * @param mixed $value  El valor del filtro que puede incluir operadores
     * como '!=', '>=', '<=', '>', '<', 'null', '!null', etc.
     * @param array $config Configuración adicional para la columna, como
     * 'foreign_key', 'to_table', 'to_field', 'db_column', y 'searchable'.
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
        if (!empty($config['foreign_key'])) {
            return $this->applyForeignKeySmartFilter($column, $value, $config);
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
     *   - 'to_table': El nombre de la tabla relacionada.
     *   - 'to_field': El campo en la tabla relacionada que se usa para la
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
                array_merge($config, ['foreign_key' => null])
            );
        }

        // JOIN con la tabla relacionada de la FK.
        $this->join(
            $config['to_table'],
            $config['to_table'] . '.' . $config['to_field'],
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
                            $config['to_table'] . '.' . $fkColumn,
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
                : '/^(' . preg_quote($operator, '/') . ')(.+)$/'
            ;
            if (preg_match($pattern, $filter, $matches)) {
                return [
                    'operator' => $matches[1],
                    'value' => $matches[2],
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
        // Normalizar el tipo de la columna.
        $columnType = $this->normalizeColumnType($config);

        // Si es string se pasa a minúscula y se usa LIKE.
        if ($columnType == 'string') {
            $value = strtolower($value);
            return $this->whereRaw('LOWER(' . $column . ') LIKE ?', [$value]);
        }

        // Otros casos (como fechas) se castea a texto y se usa LIKE.
        else {
            return $this->whereRaw('CAST('.$column.' AS TEXT) LIKE ?', [$value]);
        }
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
            return $this->whereRaw('CAST('.$column.' AS TEXT) LIKE ?', [$value]);
        }

        // Si es cualquier otro caso se comparará con una igualdad.
        return $this->where($column, '=', $value);
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

}
