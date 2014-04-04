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
 * @version 2014-04-02
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
     * @version 2012-10-07
     */
    public function __construct ()
    {
        // recuperar conexión a la base de datos
        $this->db = \sowerphp\core\Model_Datasource_Database::get($this->_database);
        // setear nombre de la tabla según la clase que se está usando
        if (empty($this->_table)) {
            $this->_table = Utility_Inflector::underscore (explode('_', get_class($this))[1]);
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
    public function __toString ()
    {
        if(isset($this->{$this->_table}))
            return $this->{$this->_table};
        return get_class($this);
    }

    /**
     * Método para setear los atributos de la clase
     * @param array Arreglo con los datos que se deben asignar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-02-05
     */
    public function set ($array)
    {
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
    public function save ()
    {
        $this->db->transaction();
        if (!$this->beforeSave()) {
            $this->db->rollback();
            return false;
        }
        if ($this->exists()) $status = $this->update();
        else $status = $this->insert();
        if (!$status || !$this->afterSave()) {
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
    public function edit ($columns, $pks = null)
    {
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
    protected function beforeSave ()
    {
        return true;
    }

    /**
     * Se ejecuta automáticamente después del save
     * @return boolean Verdadero en caso de éxito
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-10-07
     */
    protected function afterSave ()
    {
        return true;
    }

    /**
     * Se ejecuta automáticamente antes del insert
     * @return boolean Verdadero en caso de éxito
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-10-07
     */
    protected function beforeInsert ()
    {
        return true;
    }

    /**
     * Se ejecuta automáticamente después del insert
     * @return boolean Verdadero en caso de éxito
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-10-07
     */
    protected function afterInsert ()
    {
        return true;
    }

    /**
     * Se ejecuta automáticamente antes del update
     * @return boolean Verdadero en caso de éxito
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-10-07
     */
    protected function beforeUpdate ()
    {
        return true;
    }

    /**
     * Se ejecuta automáticamente después del update
     * @return boolean Verdadero en caso de éxito
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-10-07
     */
    protected function afterUpdate ()
    {
        return true;
    }

    /**
     * Se ejecuta automáticamente antes del delete
     * @return boolean Verdadero en caso de éxito
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-10-07
     */
    protected function beforeDelete ()
    {
        return true;
    }

    /**
     * Se ejecuta automáticamente después del delete
     * @return boolean Verdadero en caso de éxito
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-10-07
     */
    protected function afterDelete ()
    {
        return true;
    }

}
