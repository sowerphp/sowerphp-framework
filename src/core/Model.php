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

/**
 * Clase abstracta para todos los modelos.
 * Permite trabajar con un registro de la tabla.
 */
abstract class Model
{

    use Trait_Object;

    // Datos para la conexión a la base de datos.
    protected $_database = 'default'; ///< Base de datos del modelo.
    protected $_table; ///< Tabla del modelo.
    protected $db; ///< Conexión a base de datos.

    /**
     * Constructor de la clase base de modelos.
     */
    public function __construct($pk = null)
    {
        // Recuperar conexión a la base de datos.
        $this->getDB();
        // Setear nombre de la tabla según la clase que se está usando.
        if (empty($this->_table)) {
            $this->_table = Utility_Inflector::underscore(
                explode('_', get_class($this))[1]
            );
        }
        // Setear atributos del objeto con lo que se haya pasado al constructor
        // como parámetros.
        if (func_num_args() > 0) {
            $firstArg = func_get_arg(0);
            if (is_array($firstArg)) {
                $attrs = array_combine($this->getPk(), $firstArg);
                if ($attrs !== false) {
                    $this->set($attrs);
                }
            } else {
                $args = func_get_args();
                foreach ($this->getColumnsInfo() as $col => $info) {
                    if ($info['pk']) {
                        $this->$col = array_shift($args);
                    }
                }
            }
            // Obtener otros atributos del objeto.
            $this->get();
        }
    }

    /**
     * Método que recupera la conexión a la base de datos del objeto.
     * Si la conexión no existe se conecta.
     */
    public function getDB()
    {
        if (!isset($this->db)) {
            $this->db = database($this->_database);
        }
        return $this->db;
    }

    /**
     * Método que entrega la información de las columnas del modelo.
     *
     * @return array
     */
    public function getColumnsInfo(): array
    {
        return $this::$columnsInfo;
    }

    /**
     * Función que entrega un arreglo con la PK y los campos listos para ser
     * utilizados en una consulta SQL
     *
     * @return array Arreglo con los datos de la PK para la consulta y sus
     * valores.
     */
    protected function preparePk(): ?array
    {
        $pk = ['where'=>[], 'values'=>[]];
        foreach ($this->getColumnsInfo() as $col => &$info) {
            if ($info['pk']) {
                if (empty($this->$col) && $this->$col != 0) {
                    return null;
                }
                $pk['where'][] = $col . ' = :pk_' . $col;
                $pk['values']['pk_' . $col] = $this->$col;
            }
        }
        $pk['where'] = implode(' AND ', $pk['where']);
        return $pk;
    }

    /**
     * Método para obtener los atributos del objeto, esto es cada una de las
     * columnas que representan al objeto en la base de datos.
     *
     * @return =true si se logró obtener los datos desde la BD.
     */
    public function get(): bool
    {
        // Preparar PK.
        $pk = $this->preparePk();
        if (!$pk) {
            return false;
        }
        // Recuperar datos.
        $datos = $this->db->getRow('
            SELECT *
            FROM ' . $this->_table . '
            WHERE ' . $pk['where']
        , $pk['values']);
        // Si se encontraron datos asignar columnas a los atributos del objeto.
        if (!empty($datos)) {
            foreach ($datos as $key => &$value) {
                if ($this->getColumnsInfo()[$key]['type'] == 'boolean') {
                    $value = (int)$value;
                }
                $this->{$key} = $value;
            }
        }
        // Todo ok al recuperar y asignar los datos.
        return true;
    }

    /**
     * Método para determinar si el objeto existe en la base de datos.
     *
     * @return bool =true si el registro existe en la base de datos, =false si
     * no existe.
     */
    public function exists(): bool
    {
        // Preparar PK.
        $pk = $this->preparePk();
        if (!$pk) {
            return false;
        }
        // Verificar si el registro existe.
        return (bool)$this->db->getValue('
            SELECT COUNT(*)
            FROM '.$this->_table.'
            WHERE '.$pk['where']
        , $pk['values']);
    }

    /**
     * Método para borrar el objeto de la base de datos.
     * @return bool =true si se logró eliminar, =false si hubo algún problema.
     */
    public function delete(): bool
    {
        // Preparar PK.
        $pk = $this->preparePk();
        if (!$pk) {
            return false;
        }
        // Eliminar registro.
        $beginTransaction = $this->db->beginTransaction();
        $stmt = $this->db->executeRawQuery('
            DELETE FROM '.$this->_table.'
            WHERE '.$pk['where']
        , $pk['values']);
        if ($stmt->errorCode() === '00000') {
            if ($beginTransaction) {
                $this->db->commit();
            }
            return true;
        }
        if ($beginTransaction) {
            $this->db->rollBack();
        }
        return false;
    }

    /**
     * Método para guardar el objeto en la base de datos.
     *
     * @return bool =true si todo fue ok, =false si hubo algún problema.
     */
    public function save(): bool
    {
        $op = $this->exists() ? 'update' : 'insert';
        return $this->$op();
    }

    /**
     * Método para insertar el objeto en la base de datos.
     *
     * @return bool =true si se logró insertar, =false si hubo algún problema.
     */
    protected function insert(): bool
    {
        // Preparar columnas y valores.
        $cols = [];
        $alias = [];
        $values = [];
        foreach ($this->getColumnsInfo() as $col => &$info) {
            if ($info['auto'] || $this->$col === null || $this->$col === '') {
                continue;
            }
            $cols[] = $col;
            $alias[] = ':'.$col;
            $values[$col] = $this->$col;
        }
        // Insertar datos.
        $beginTransaction = $this->db->beginTransaction();
        $stmt = $this->db->executeRawQuery('
            INSERT INTO '.$this->_table.' (
                '.implode(', ', $cols).'
            ) VALUES (
                '.implode(', ', $alias).'
            )
        ', $values);
        if ($stmt->errorCode() === '00000') {
            if (property_exists($this, 'id')) {
                $this->id = $this->db->getValue(
                    'SELECT MAX(id) FROM ' . $this->_table
                );
            }
            if ($beginTransaction) {
                $this->db->commit();
            }
            return true;
        }
        if ($beginTransaction) {
            $this->db->rollBack();
        }
        return false;
    }

    /**
     * Método que permite editar una fila de la base de datos de manera simple
     * desde desde fuera del modelo.
     *
     * @param array $columns Arreglo asociativo con las columnas a editar o
     * NULL para editar todas las columnas.
     * @return bool =true si se logró actualizar, =false si hubo algún problema.
     */
    protected function update(array $columns = []): bool
    {
        // Buscar columnas y valores si no se pasaron.
        if (empty($columns)) {
            foreach ($this->getColumnsInfo() as $col => $info) {
                $columns[$col] = $this->$col;
            }
        }
        // Actualizar en el objeto las columnas que se pasaron.
        else {
            foreach ($columns as $col => &$value) {
                $this->$col = $value;
            }
        }
        // Preparar set de la consulta.
        $querySet = [];
        foreach ($columns as $col => &$val) {
            $querySet[] = $col . ' = :' . $col;
        }
        // Preparar PK.
        $pk = $this->preparePk();
        if (!$pk) {
            return false;
        }
        // Realizar consulta.
        $beginTransaction = $this->db->beginTransaction();
        $stmt = $this->db->executeRawQuery('
            UPDATE ' . $this->_table . '
            SET ' . implode(', ', $querySet) . '
            WHERE ' . $pk['where']
        , array_merge($columns, $pk['values']));
        if ($stmt->errorCode() === '00000') {
            if ($beginTransaction) {
                $this->db->commit();
            }
            return true;
        }
        if ($beginTransaction) {
            $this->db->rollBack();
        }
        return false;
    }

    /**
     * Método "mágico" para atrapar las llamadas a getFK(), setAttribute() o
     * getAttribute(). En realidad atrapará las llamadas a cualquier método
     * inexistente, pero solo se procesarán aquellos mencionados y en otros
     * casos se generará una excepción (ya que el método no existirá).
     */
    public function __call(string $method, $args)
    {
        // Asegurarse que sean métodos que inician con get.
        $request = substr($method, 0, 3);
        // Si la solicitud es un getFK() o un getAttribute().
        if ($request == 'get') {
            $fk = substr($method, 3);
            // Es un getFK() -> debe existir en fkNamespace.
            if (
                isset($this::$fkNamespace)
                && isset($this::$fkNamespace['Model_'.$fk])
            ) {
                return $this->getFK($fk, $args);
            }
            // Es un getAttribute().
            else {
                $attribute = Utility_Inflector::underscore(substr($method, 3));
                if (isset($this->getColumnsInfo()[$attribute])) {
                    return $this->$attribute;
                }
            }
        }
        // Si la solicitud es un setAttribute().
        else if ($request == 'set') {
            $attribute = Utility_Inflector::underscore(substr($method, 3));
            if (isset($this->getColumnsInfo()[$attribute])) {
                return call_user_func_array(
                    [$this, 'setAttribute'],
                    array_merge([$attribute], $args)
                );
            }
        }
        // Si el método no existe se genera una excepción.
        throw new \Exception(__(
            'Método %s::%s() no existe.',
            get_class($this),
            $method
        ));
    }

    /**
     * Método que obtiene un objeto que es FK de este.
     *
     * @param string $fk Nombre de la clase que es la FK (sin Model_).
     * @param array $args Argunentos con la PK del objeto que es FK
     * @return object Objeto instanciado de la llave foránea.
     */
    private function getFK(string $fk, array $args = []): object
    {
        $fkClass = $this::$fkNamespace['Model_' . $fk] . '\Model_' . $fk;
        // Si la clase de la FK no existe, entonces error.
        if (!class_exists($fkClass)) {
            throw new \Exception(__(
                'Modelo %s no fue encontrado.',
                $fkClass
            ));
        }
        $fkClasss = Utility_Inflector::pluralize($fkClass);
        $fkClasssExists = class_exists($fkClasss);
        // Obtener la instancia de la clase de la FK.
        // Se tratará de recuperar con la clase plural (para usar el contenedor
        // de clases singulares).
        if (isset($args[0])) {
            return $fkClasssExists
                ? (new $fkClasss)->get($args[0])
                : new $fkClass($args[0])
            ;
        } else {
            $fkLocalValue = $this->{Utility_Inflector::underscore($fk)};
            return $fkClasssExists
                ? (new $fkClasss)->get($fkLocalValue)
                : new $fkClass($fkLocalValue);
            ;
        }
    }

    /**
     * Método que permite asignar el valor de un atributo.
     *
     * @param string $attribute Atributo del objeto que se desea asignar.
     * @param mixed $value Valor que se desea asignar al objeto.
     * @param bool $check Si se debe validar por algún tipo de dato en particular.
     * @param bool $trim Si se debe aplicar la función trim() al valor.
     * @param bool $strip_tags Si se debe aplicar la función strip_tags() al valor.
     * @return bool =true si pasó la validación y se pudo asignar el valor.
     */
    private function setAttribute(
        string $attribute,
        $value,
        bool $check = true,
        bool $trim = true,
        bool $strip_tags = true
    ): bool
    {
        if ($strip_tags) {
            $value = strip_tags($value);
        }
        if ($trim) {
            $value = trim($value);
        }
        if ($check === true || is_array($check)) {
            if (
                !is_array($check)
                && isset($this->getColumnsInfo()[$attribute]['check'])
            ) {
                $check = $this->getColumnsInfo()[$attribute]['check'];
            }
            if (is_array($check)) {
                $status = Utility_Data_Validation::check($value, $check);
                if ($status !== true) {
                    return false;
                }
            }
        }
        $this->$attribute = $value;
        return true;
    }

    /**
     * Método que valida los valores asignados a los atributos del objeto.
     */
    public function checkAttributes()
    {
        foreach ($this->getColumnsInfo() as $attribute => $info) {
            // Verificar que el campo tenga una valor si no puede ser NULL.
            // "0" es un valor aceptado como válido.
            if (
                empty($info['auto'])
                && empty($info['null'])
                && ($this->{$attribute} === null || $this->{$attribute} === '')
            ) {
                throw new \Exception(__(
                    'El campo "%s" debe tener un valor.',
                    $info['name'],
                ));
            }
            // Verificar largo del campo.
            if (
                !empty($info['length'])
                && in_array($info['type'], ['char', 'character varying', 'varchar', 'text'])
            ) {
                $attribute_len = mb_strlen($this->{$attribute});
                if ($attribute_len > $info['length']) {
                    throw new \Exception(__(
                        'El campo "%s" debe tener un largo máximo de %d caracteres. Se ingresaron %d caracteres.',
                        $info['name'],
                        $info['length'],
                        $attribute_len
                    ));
                }
            }
            // Validaciones del modelo estándares.
            if (isset($this->getColumnsInfo()[$attribute]['check'])) {
                $status = Utility_Data_Validation::check(
                    $this->{$attribute}, $this->getColumnsInfo()[$attribute]['check']
                );
                if ($status !== true) {
                    throw new \Exception($status);
                }
            }
        }
    }

    /**
     * Método que entrega un arreglo con las columnas que son la PK de la tabla.
     *
     * @return array Arreglo con las columnas que son la PK.
     */
    public function getPk(): array
    {
        $pk = [];
        foreach ($this->getColumnsInfo() as $column => $info) {
            if ($info['pk']) {
                $pk[] = $column;
            }
        }
        return $pk;
    }

    /**
     * Método que entrega un arreglo con los valores de la PK de la tabla.
     *
     * @return array Arreglo con las columnas que son la PK.
     */
    public function getPkValues(): array
    {
        $pk = $this->getPk();
        $values = [];
        foreach ($pk as &$p) {
            $values[$p] = $this->$p;
        }
        return $values;
    }

    /**
     * Método que asigna un archivo a los campos que corresponden en la clase.
     */
    public function setFile(string $name, array $file): void
    {
        if (!isset($file['data'])) {
            $file['data'] = fread(
                fopen($file['tmp_name'], 'rb'),
                filesize($file['tmp_name'])
            );
        }
        $this->{$name . '_name'} = $file['name'];
        $this->{$name . '_type'} = $file['type'];
        $this->{$name . '_size'} = $file['size'];
        $this->{$name . '_data'} = $file['data'];
    }

    /**
     * Método que elimina la conexión a la base de datos antes de serializar.
     */
    public function __sleep()
    {
        $this->db = null;
        return array_keys(get_object_vars($this));
    }

    /**
     * Método que recupera la conexión a la base de datos después de
     * deserializar.
     */
    public function __wakeup()
    {
        $this->db = database($this->_database);
    }

}
