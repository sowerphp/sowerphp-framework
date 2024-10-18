<?php

declare(strict_types=1);

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

use Illuminate\Database\Connection;

/**
 * Clase base abstracta para conexiones personalizadas de base de datos.
 *
 * Extiende la funcionalidad de la clase `Connection` de Laravel para incluir
 * características comunes necesarias para las conexiones personalizadas en
 * diferentes motores de bases de datos. Esta clase no se puede instanciar
 * directamente, sino que debe ser extendida por clases específicas del motor
 * de base de datos que implementen comportamientos específicos.
 */
abstract class Database_Connection extends Connection
{
    /**
     * Estadísticas de las llamadas a los métodos de la conexión a la base de
     * datos.
     *
     * Define:
     *   - queries: cantidad de consultas SQL realizadas.
     *
     * @var array
     */
    protected $stats = [
        'queries' => 0,
    ];

    /**
     * Entrega las estadísticas del uso de la conexión de la base de datos.
     *
     * @return array Arreglo con las estadísticas del uso de la base de datos.
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Método que entrega el driver de la base de datos de la conexión.
     *
     * @return string Driver de la base de datos de la conexión.
     */
    public function __toString()
    {
        return $this->getDriverName();
    }

    /**
     * Entrega la instancia del QueryBuilder.
     *
     * @return Database_QueryBuilder
     */
    public function query(): Database_QueryBuilder
    {
        return new Database_QueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    /**
     * Comenzar una transacción en la base de datos.
     *
     * Evita iniciar más de una transacción y establece el nivel de aislamiento
     * a SERIALIZABLE si se requiere, antes de iniciar la transacción.
     *
     * @param bool $serializable Si es true, se ejecutará la transacción de
     * forma SERIALIZABLE.
     * @return bool Retorna true si la transacción se inició correctamente,
     * false en caso contrario.
     */
    public function beginTransaction($serializable = false): bool
    {
        // Configurar nivel de aislamiento para la transacción si se solicitó
        // y la transacción aun no ha sido iniciada.
        if ($serializable && !$this->transactionLevel()) {
            // Serializar transacción en PostgreSQL.
            if ($this->getDriverName() == 'pgsql') {
                $this->executeRawQuery('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
                $this->executeRawQuery('SET TRANSACTION READ WRITE');
            }

            // Serializar transacción en MySQL.
            else if ($this->getDriverName() == 'mysql') {
                $this->executeRawQuery('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
            }
        }

        // Iniciar la transacción.
        try {
            parent::beginTransaction();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Commit de la transacción activa en la base de datos.
     *
     * Este método intenta hacer commit a la transacción actual. Si la
     * transacción se encuentra en el nivel más externo (es decir, no hay
     * transacciones anidadas o es la primera transacción), se procede a hacer
     * el commit en la base de datos. Si ocurre un error durante el proceso de
     * commit, se captura la excepción y se retorna false para indicar el fallo
     * de la operación.
     *
     * @return bool `true` si el commit fue exitoso, o =false en caso de error.
     */
    public function commit(): bool
    {
        if ($this->transactionLevel()) {
            try {
                parent::commit();
                return true;
            } catch (\Throwable $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Revierte la transacción activa hasta un nivel específico, o hasta el
     * nivel más externo si no se especifica un nivel.
     *
     * Este método intenta revertir las transacciones hasta el nivel
     * especificado. Si no se proporciona un nivel, se asume que debe revertir
     * todas las transacciones hasta el nivel más externo. Si el proceso de
     * reversión es exitoso, retorna true. Si ocurre un error durante la
     * reversión, se captura la excepción y se retorna false.
     *
     * @param int|null $toLevel El nivel específico hasta el cual revertir las
     * transacciones. Si es null, se revierten todas hasta el nivel más externo.
     *
     * @return bool Retorna true si la reversión fue exitosa, false en caso de
     * error o si no hay transacciones activas para revertir.
     */
    public function rollBack($toLevel = null): bool
    {
        if ($this->transactionLevel()) {
            try {
                parent::rollBack($toLevel ?? 0);
                return true;
            } catch (\Throwable $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Maneja errores durante las operaciones de base de datos.
     *
     * Este método es responsable de manejar errores que ocurren durante las
     * operaciones de base de datos. Cuando se invoca, primero verifica si hay
     * una transacción en curso. Si existe una transacción activa, la deshace
     * (rollback) para asegurar la integridad de la base de datos.
     * Posteriormente, lanza una excepción personalizada para notificar a los
     * consumidores de la clase sobre el error ocurrido.
     *
     * @param string $message El mensaje de error que será incluido en la
     * excepción lanzada.
     * @throws \Exception Excepción que se lanza para
     * indicar el error.
     */
    protected function error(string $message): void
    {
        // Verificar si existe una transacción activa y deshacerla.
        if ($this->transactionLevel()) {
            $this->rollBack();
        }

        // Lanzar una excepción con el mensaje de error proporcionado.
        throw new \Exception($message);
    }

    /**
     * Ejecuta una consulta SQL directa con parámetros vinculables.
     *
     * Este método facilita la ejecución de consultas SQL directas, permitiendo
     * la vinculación de parámetros para evitar la inyección SQL. Verifica
     * primero que la consulta no esté vacía y procede a preparar la sentencia
     * SQL en la conexión adecuada, ya sea de solo lectura o de
     * lectura/escritura, dependiendo del parámetro `$useReadPdo`. Lanza un
     * error si no es posible preparar la sentencia o si la ejecución falla.
     *
     * @param string $query Consulta SQL que se desea realizar.
     * @param array $bindings Parámetros que se deben enlazar a la consulta.
     * @param bool $useReadPdo Indica que se use una conexión de solo lectura.
     * @return \PDOStatement Objeto statement después de ejecutar la consulta.
     * @throws \Exception Si la consulta no puede ser preparada o si
     * falla la ejecución.
     */
    public function executeRawQuery(
        string $query,
        array $bindings = [],
        bool $useReadPdo = false
    ): \PDOStatement
    {
        // Verificar que exista una consulta SQL que se desee ejecutar.
        if(empty($query)) {
            $this->error('¡Consulta SQL no puede estar vacía!');
        }

        // Contabilizar la consulta SQL de esta conexión.
        $this->stats['queries']++;

        // Decidir si se usará PDO de solo lectura o lectura/escritura.
        $pdo = $useReadPdo ? $this->getPdoForSelect() : $this->getPdo();

        // Preparar la consulta SQL.
        $statement = $pdo->prepare($query);
        if ($statement === false) {
            $this->error(
                'No fue posible preparar la consulta SQL:' . "\n\n" . $query
            );
        }

        // Asignar parámetros para la ejecución de la consulta.
        foreach ($bindings as $key => &$param) {
            if (is_array($param)) {
                $statement->bindParam($key, $param[0], $param[1]);
            } else if ($param === null || $param === '') {
                $statement->bindValue($key, null, \PDO::PARAM_NULL);
            } else {
                $statement->bindParam($key, $param);
            }
        }

        // Realizar consulta SQL a la base de datos.
        try {
            $statement->execute();
        } catch (\PDOException $e) {
            $this->error($e->getMessage());
        }
        if(!$statement || $statement->errorCode() !== '00000') {
            $this->error(
                implode("\n", $statement->errorInfo()) . "\n\n" . $query
            );
        }

        // Retornar identificador de la consulta SQL realizada
        return $statement;
    }

    /**
     * Obtener una tabla, como arreglo asociativo, desde la base de datos.
     *
     * @param string $query Consulta SQL que se desea realizar.
     * @param array $bindings Parámetros que se deben enlazar a la consulta.
     * @return array Arreglo bidimensional con la tabla y sus datos.
     */
    public function getTable(string $query, array $bindings = []): array
    {
        return (array)$this->executeRawQuery($query, $bindings, true)
            ->fetchAll(\PDO::FETCH_ASSOC)
        ;
    }

    /**
     * Obtener una sola fila desde la base de datos.
     *
     * @param string $query Consulta SQL que se desea realizar.
     * @param array $bindings Parámetros que se deben enlazar a la consulta.
     * @return array Arreglo unidimensional con la fila.
     */
    public function getRow(string $query, array $bindings = []): array
    {
        $statement = $this->executeRawQuery($query, $bindings, true);
        $data = $statement->fetch(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return !empty($data) ? $data : [];
    }

    /**
     * Obtener una sola columna desde la base de datos.
     *
     * @param string $query Consulta SQL que se desea realizar.
     * @param array $bindings Parámetros que se deben enlazar a la consulta.
     * @return array Arreglo unidimensional con la columna.
     */
    public function getCol(string $query, array $bindings = []): array
    {
        $statement = $this->executeRawQuery($query, $bindings, true);
        $cols = [];
        while (($col = $statement->fetchColumn()) !== false) {
            $cols[] = $col;
        }
        $statement->closeCursor();

        return $cols;
    }

    /**
     * Obtener un solo valor desde la base de datos.
     *
     * @param string $query Consulta SQL que se desea realizar.
     * @param array $bindings Parámetros que se deben enlazar a la consulta.
     * @return mixed Valor devuelto.
     */
    public function getValue(string $query, array $bindings = []): string
    {
        $statement = $this->executeRawQuery($query, $bindings, true);
        $data = $statement->fetchColumn();
        $statement->closeCursor();

        return !empty($data) ? $data : '';
    }

    /**
     * Obtener un generador para una tabla desde la base de datos.
     *
     * @param string $query Consulta SQL que se desea realizar.
     * @param array $bindings Parámetros que se deben enlazar a la consulta.
     * @return Generator Objecto con el generador de los resultados.
     */
    public function getTableGenerator(string $query, array $bindings = [])
    {
        $statement = $this->executeRawQuery($query, $bindings);
        while (($row = $statement->fetch(\PDO::FETCH_ASSOC)) !== false) {
            yield $row;
        }
        $statement->closeCursor();
    }

    /**
     * Obtener un generador para una sola columna desde la base de datos.
     *
     * @param string $query Consulta SQL que se desea realizar.
     * @param array $bindings Parámetros que se deben enlazar a la consulta.
     * @return Generator Objecto con el generador de los resultados.
     */
    public function getColGenerator(string $query, array $bindings = [])
    {
        $statement = $this->executeRawQuery($query, $bindings);
        while (($col = $statement->fetchColumn()) !== false) {
            yield $col;
        }
        $statement->closeCursor();
    }

    /**
     * Obtener un arreglo con índice el identificador del registro que se
     * está consultando con algún valor asociado.
     *
     * @param string $query Consulta SQL que se desea realizar.
     * @param array $bindings Parámetros que se deben enlazar a la consulta.
     * @return array Arreglo bidimensional con los índices y sus datos (arreglo).
     */
    public function getTableWithAssociativeIndex(string $query, array $bindings = []): array
    {
        return Utility_Array::tableToAssociativeArray(
            $this->getTable($query, $bindings)
        );
    }

    /**
     * Seleccionar una tabla con los nombres de las columnas.
     *
     * @param string $query Consulta SQL que se desea realizar.
     * @param array $bindings Parámetros que se deben enlazar a la consulta.
     * @return array Arreglo con los nombres de columnas y luego los datos.
     */
    public function getTableWithColsNames(string $query, array $bindings = [])
    {
        $skip = ['blob'];
        // Variables para datos y claves.
        $data = [];
        $columns = [];

        // Realizar consulta.
        $statement = $this->executeRawQuery($query, $bindings);

        // Obtener información de las columnas.
        $n_columns = $statement->columnCount();
        for($i=0; $i<$n_columns; ++$i) {
            $aux = $statement->getColumnMeta($i);
            $columns[$aux['name']] = $aux;
            unset($columns[$aux['name']]['name'], $aux);
        }
        $data[] = array_keys($columns);

        // Agregar las filas de la consulta.
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($result as $row) {
            foreach ($row as $col => &$value) {
                if (in_array($columns[$col]['native_type'], $skip)) {
                    $value = '[' . $columns[$col]['native_type'] . ']';
                }
            }
            $data[] = $row;
        }

        // Retornar tabla con sus nombres de columnas.
        return $data;
    }

    /**
     * Entrega información de una tabla: nombre, comentario, columnas, llaves
     * primarias (pk) y llaves foráneas (fk).
     *
     * @param string $table Nombre de la tabla que se desean conocer sus datos.
     * @return array Arreglo con los datos de la tabla.
     */
    public function getInfoFromTable(string $tablename): array
    {
        // Nombre de la tabla.
        $table['name'] = $tablename;

        // Obtener comentario de la tabla.
        $table['comment'] = $this->getCommentFromTable($table['name']);

        // Obtener llaves primarias de la tabla.
        $table['pk'] = $this->getPksFromTable($table['name']);

        // Obtener llaves foráneas de la tabla.
        $fkAux = $this->getFksFromTable($table['name']);
        $fk = [];
        foreach($fkAux as $aux) {
            $fk[array_shift($aux)] = $aux;
        }

        // Obtener columnas de la tabla.
        $columns = $this->getColsFromTable($table['name']);

        // Recorrer columnas para definir pk, fk, auto, null/not null, default
        // y comentario.
        foreach($columns as &$column) {
            // Definir null o not null.
            $column['null'] = $column['null'] == 'YES' ? 1 : 0;

            // Definir si es auto_increment (depende de la base de datos como
            // se hace).
            if ($this->getDriverName() == 'pgsql') {
                $prefix = substr($column['default'], 0, 7);
                $column['auto'] = $prefix == 'nextval' ? 1 : 0;
            } else if ($this->getDriverName() == 'mysql') {
                $column['auto'] = $column['extra'] == 'auto_increment' ? 1 : 0;
                unset ($column['extra']);
            }

            // Limpiar default, quitar lo que viene despues de ::
            if(!$column['auto']) {
                $aux = explode('::', $column['default']);
                $column['default'] = trim(array_shift($aux), '\'');
                if ($column['default'] == 'NULL') {
                    $column['default'] = null;
                }
            }

            // Definir fk.
            $column['fk'] = $fk[$column['name']] ?? null;
        }
        $table['columns'] = $columns;

        // Entregar información de la tabla.
        return $table;
    }

    /**
     * Asigna un límite para la obtención de filas en la consulta SQL.
     *
     * @param string $query Consulta SQL a la que se agregará el límite.
     * @param int $records Registros que se desean obtener.
     * @param int $offset Registro desde donde iniciar el límite.
     * @return string Consulta con el límite agregado.
     */
    public function setLimit(string $query, int $records, int $offset = 0): string
    {
        return '';
    }

    /**
     * Entrega el string SQL de una fecha en cierto formato.
     *
     * Se puede entregar a partir de cierta fecha y hora o bien con la fecha y
     * hora actual.
     */
    public function date(string $format, $datetime = null, $cast = null): string
    {
        return '';
    }

    /**
     * Extrae un valor desde un nodo de un XML almacenado en una columna de la
     * base de datos.
     *
     * Este método es por compatibilidad, aquellas bases de datos que no
     * soportan este método entregarán NULL para cada PATH solicitado.
     *
     * @return array|string
     */
    public function xml(
        string $column,
        $path,
        $namespace = null,
        bool $trim = true,
        $data_format = 'base64_ISO8859-1'
    )
    {
        if (!is_array($path)) {
            $path = [$path];
        }
        $select = [];
        foreach ($path as $p) {
            $select[] = 'NULL';
        }

        return count($select) > 1 ? $select : array_shift($select);
    }

    /**
     * Listado de tablas de la base de datos.
     *
     * @return array Arreglo con las tablas (su nombre y comentario).
     */
    public function getTablesFromDatabase(): array
    {
        return [];
    }

    /**
     * Obtener comentario de una tabla.
     *
     * @param string $table Nombre de la tabla.
     * @return string Comentario de la tabla.
     */
    public function getCommentFromTable(string $table): string
    {
        return '';
    }

    /**
     * Listado de columnas de una tabla (nombre, tipo, largo máximo, si
     * puede tener un valor nulo y su valor por defecto).
     *
     * @param string $table Tabla a la que se quiere buscar las columnas.
     * @return array Arreglo con la información de las columnas.
     */
    public function getColsFromTable(string $table): array
    {
        return [];
    }

    /**
     * Listado de claves primarias de una tabla.
     *
     * @param string $table Tabla a buscar su claves primarias.
     * @return array Arreglo con las claves primarias.
     */
    public function getPksFromTable(string $table): array
    {
        return [];
    }

    /**
     * Listado de claves foráneas de una tabla.
     *
     * @param string $table Tabla a buscar sus claves foráneas.
     * @return array Arreglo con las claves foráneas.
     */
    public function getFksFromTable(string $table): array
    {
        return [];
    }
}
