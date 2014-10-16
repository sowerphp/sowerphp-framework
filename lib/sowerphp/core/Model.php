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

namespace sowerphp\core;

/**
 * Clase abstracta para todos los modelos
 * Permite trabajar con un registro de la tabla
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-09-25
 */
abstract class Model extends Object
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table; ///< Tabla del modelo
    protected $db; ///< Conexión a base de datos

    /**
     * Constructor de la clase abstracta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    public function __construct ($pk=null)
    {
        // recuperar conexión a la base de datos
        $this->db = \sowerphp\core\Model_Datasource_Database::get($this->_database);
        // setear nombre de la tabla según la clase que se está usando
        if (empty($this->_table)) {
            $this->_table = Utility_Inflector::underscore (explode('_', get_class($this))[1]);
        }
        // setear atributos del objeto con lo que se haya pasado al
        // constructor como parámetros
        if (func_num_args()>0) {
            $firstArg = func_get_arg(0);
            if (is_array($firstArg)) {
                $this->set(array_combine($this->getPk(), $firstArg));
            } else {
                $args = func_get_args();
                foreach ($this::$columnsInfo as $col => &$info) {
                    if ($info['pk']) {
                        $this->$col = array_shift($args);
                    }
                }
            }
            // obtener otros atributos del objeto
            $this->get();
        }
    }

    /**
     * Función que entrega un arreglo con la PK y los campos listos para ser
     * utilizados en una consulta SQL
     * @return Arreglo con los datos de la PK para la consulta y sus valores
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    protected function preparePk ()
    {
        $pk = ['where'=>[], 'values'=>[]];
        foreach ($this::$columnsInfo as $col => &$info) {
            if ($info['pk']) {
                if (empty($this->$col))
                    return false;
                $pk['where'][] = $col.' = :pk_'.$col;
                $pk['values'][':pk_'.$col] = $this->$col;
            }
        }
        $pk['where'] = implode(' AND ', $pk['where']);
        return $pk;
    }

    /**
     * Método para obtener los atributos del objeto, esto es cada una
     * de las columnas que representan al objeto en la base de datos
     * @return =true si se logró obtener los datos desde la BD
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-05-04
     */
    public function get ()
    {
        // preparar pk
        $pk = $this->preparePk();
        if (!$pk) return false;
        // recuperar datos
        $datos = $this->db->getRow(
            'SELECT * FROM '.$this->_table.' WHERE '.$pk['where']
            , $pk['values']
        );
        // si se encontraron datos asignar columnas a los atributos
        // del objeto
        if (count($datos)) {
            foreach ($datos as $key => &$value) {
                if ($this::$columnsInfo[$key]['type']=='boolean') {
                    $value = (int)$value;
                }
                $this->{$key} = $value;
            }
        }
        return true;
    }

    /**
     * Método para determinar si el objeto existe en la base de datos
     * @return =true si el registro existe en la base de datos, =false si no existe
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    public function exists ()
    {
        // preparar pk
        $pk = $this->preparePk();
        if (!$pk) return false;
        // verificar si existe
        return (boolean) $this->db->getValue(
            'SELECT COUNT(*) FROM '.$this->_table.' WHERE '.$pk['where'],
            $pk['values']
        );
    }

    /**
     * Método para borrar el objeto de la base de datos
     * @return =true si se logró eliminar, =false en caso de algún problema
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    public function delete ()
    {
        // preparar pk
        $pk = $this->preparePk();
        if (!$pk) return false;
        // eliminar registro
        $this->db->beginTransaction();
        $stmt = $this->db->query(
            'DELETE FROM '.$this->_table.' WHERE '.$pk['where'],
            $pk['values']
        );
        if ($stmt->errorCode()==='00000') {
            $this->db->commit();
            return true;
        }
        $this->db->rollBack();
        return false;
    }

    /**
     * Método para guardar el objeto en la base de datos
     * @return =true si todo fue ok, =false si hubo algún problema
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    public function save ()
    {
        $op = $this->exists() ? 'update' : 'insert';
        return $this->$op();
    }

    /**
     * Método para insertar el objeto en la base de datos
     * @return =true si se logró insertar, =false en caso de algún problema
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-05-06
     */
    protected function insert ()
    {
        // verificar que no exista
        if ($this->exists())
            return false;
        // preparar columnas y valores
        $cols = [];
        $alias = [];
        $values = [];
        foreach ($this::$columnsInfo as $col => &$info) {
            if ($info['auto'] || $this->$col===null || $this->$col==='')
                continue;
            $cols[] = $col;
            $alias[] = ':'.$col;
            $values[':'.$col] = $this->$col;
        }
        // insertar datos
        $this->db->beginTransaction();
        $stmt = $this->db->query('
            INSERT INTO '.$this->_table.' (
                '.implode(', ', $cols).'
            ) VALUES (
                '.implode(', ', $alias).'
            )
        ', $values);
        if ($stmt->errorCode()==='00000') {
            if (property_exists($this, 'id')) {
                $this->id = $this->db->getValue('SELECT MAX(id) FROM '.$this->_table);
            }
            $this->db->commit();
            return true;
        }
        $this->db->rollBack();
        return false;
    }

    /**
     * Método que permite editar una fila de la base de datos de manera
     * simple desde desde fuera del modelo.
     * @param columns Arreglo asociativo con las columnas a editar o NULL para editar todas las columnas
     * @return =true si se logró actualizar, =false en caso de algún problema
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-23
     */
    public function update ($columns = null)
    {
        // verificar que exista
        if (!$this->exists())
            return false;
        // buscar columnas y valores si no se pasaron
        if ($columns === null) {
            foreach ($this::$columnsInfo as $col => &$info) {
                $columns[$col] = $this->$col;
            }
        }
        // preparar set de la consulta
        $querySet = [];
        foreach ($columns as $col => &$val) {
            $querySet[] = $col.' = :'.$col;
        }
        // preparar pk
        $pk = $this->preparePk();
        if (!$pk) return false;
        // realizar consulta
        $this->db->beginTransaction();
        $stmt = $this->db->query ('
            UPDATE '.$this->_table.'
            SET '.implode(', ', $querySet).'
            WHERE '.$pk['where']
        , array_merge($columns, $pk['values']));
        if ($stmt->errorCode()==='00000') {
            $this->db->commit();
            return true;
        }
        $this->db->rollBack();
        return false;
    }

    /**
     * Método "mágico" para atrapar las llamadas a getFK(), en realidad
     * atrapará las llamadas a cualquier método inexistente, pero solo
     * se procesarán aquellos que sean getFK y en otros caso generará una
     * excepción (ya que el método no existirá)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-09-11
     */
    public function __call($method, $args)
    {
        // asegurarse que sean métodos que inician con get
        if (substr($method, 0, 3)=='get') {
            $fk = substr($method, 3);
            // asegurarse que sea un getFK (ya que debe existir en fkNamespace)
            if (isset($this::$fkNamespace) && isset($this::$fkNamespace['Model_'.$fk])) {
                $fkClass = $this::$fkNamespace['Model_'.$fk].'\Model_'.$fk;
                if (isset($args[0])) {
                    $fkObj = new $fkClass($args[0]);
                } else {
                    $fkObj = new $fkClass($this->{Utility_Inflector::underscore($fk)});
                }
                if ($fkObj->exists()) {
                    return $fkObj;
                }
                return null;
            }
        }
        // si el método no es getFK() se genera una excepción
        throw new Exception(array(
            sprintf ('Método %s::%s() no existe', get_class($this), $method)
        ));
    }

    /**
     * Método que entrega un arreglo con las columnas que son la PK de la tabla
     * @return Arreglo con las columnas que son la PK
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-22
     */
    public function getPk ()
    {
        $pk = [];
        foreach ($this::$columnsInfo as $column => &$info) {
            if ($info['pk'])
                $pk[] = $column;
        }
        return $pk;
    }

    /**
     * Método que entrega un arreglo con los valores de la PK de la tabla
     * @return Arreglo con las columnas que son la PK
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-22
     */
    public function getPkValues ()
    {
        $pk = $this->getPk();
        $values = [];
        foreach ($pk as &$p) {
            $values[$p] = $this->$p;
        }
        return $values;
    }

    /**
     * Método que guarda un archivo en la base de datos
     * @return =true si se logró guardar el archivo, =false en caso de algún problema
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-23
     */
    public function saveFile($name, $file)
    {
        // verificar que exista el campo en la bd
        if (!isset($this::$columnsInfo[$name.'_data'])) {
            return false;
        }
        // preparar pk
        $pk = $this->preparePk();
        if (!$pk) return false;
        // guardar archivo
        $this->db->beginTransaction();
        $stmt = $this->db->query(
            'UPDATE '.$this->_table.'
            SET
                '.$name.'_data = :data,
                '.$name.'_name = :name,
                '.$name.'_type = :type,
                '.$name.'_size = :size
            WHERE '.$pk['where'],
            $file
        );
        if ($stmt->errorCode()==='00000') {
            $this->db->commit();
            return true;
        }
        $this->db->rollBack();
        return false;
    }

    /**
     * Wrapper para comenzar una transacción (permite usar fuera del modelo)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-09-25
     */
    public function beginTransaction()
    {
        $this->db->beginTransaction();
    }

    /**
     * Wrapper para aceptar una transacción (permite usar fuera del modelo)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-09-25
     */
    public function commit()
    {
        $this->db->commit();
    }

    /**
     * Wrapper para cancelar una transacción (permite usar fuera del modelo)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-09-25
     */
    public function rollBack()
    {
        $this->db->rollBack();
    }

}
