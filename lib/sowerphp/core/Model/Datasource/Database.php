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
 * Clase para manejar bases de datos.
 * 
 * Capa de abstracción para base de datos, la clase puede ser fácilmente
 * utilizada fuera del framework SowerPHP sin mayores modificaciones.
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-29
 */
class Model_Datasource_Database
{

    private static $_databases; ///< Arreglo con las bases de datos que se han cargado

    /**
     * Método para cargar una base de datos
     * 
     * Si la base de datos ya ha sido cargada solo se devolverá. Además
     * para cargar la base de datos se permite que se pase el nombre de
     * la base de datos, lo cual la buscará en la lista de base de datos
     * cargadas o bien un arreglo con la configuración de la base de
     * datos la cual será utilizada para cargar la base de datos por
     * primera vez.
     * @param database La base de datos que se desea cargar,
     * @param config Configuración de la base de datos
     * @return Objeto con la base de datos seleccionada
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-29
     */
    public static function &get ($database = 'default', $config = array())
    {
        // si $database es un string entonces se podría estar solicitando una
        // base de datos que ya ha sido cargada, por lo cual se revisa si
        // existe dicho indice, si existe se retorna
        if (is_string($database) && isset(self::$_databases[$database])) {
            return self::$_databases[$database];
        }
        // si es un arreglo lo que se pasó como $database, es la configuración
        // que se debe utilizar en la configuración si no existe se busca si
        // hay configuración para la base de datos
        else if (is_array($database)) {
            $config = $database;
            if (isset($config['conf']))
                $database = $config['conf'];
            else
                $database = 'default';
        }
        // si no se pasó configuración se trata de cargar con Configure
        if (!is_array($config) || !isset($config['name'])) {
            // si la clase Configure existe se carga la configuración
            if (is_string($database) && class_exists('Configure')) {
                // se carga
                $config = Configure::read('database.'.$database);
                // si Configure no encontró la configuración se genera
                // excepción ya que no se logró obtener una configuración
                // válida para la base de datos
                if (!is_array($config)) {
                    throw new Exception_Model_Datasource_Database (array(
                        'msg' => 'No se encontró configuración database.'.$database
                    ));
                }
            }
            // si la clase Configure no existe se genera excepción ya que no se
            // logró obtener una configuración válida para la base de datos
            else {
                throw new Exception_Model_Datasource_Database(array(
                    'msg' => 'No se encontró configuración database.'.$database
                ));
            }
        }
        // cargar la clase para la base de datos si no esta cargada
        $model = 'Model_Datasource_Database_'.$config['type'];
        if (!class_exists($model)) {
            require dirname(__FILE__).'/Database/Manager.php';
            require dirname(__FILE__).'/Database/'.$config['type'].'.php';
        }
        // crear objeto de la base de datos
        self::$_databases[$database] = new $model($config);
        // si hubo algún error mostrar mensaje y terminar script
        if (!self::$_databases[$database]->getLink()) {
            $config = self::$_databases[$database]->getConfig();
            throw new Exception_Model_Datasource_Database(array(
                'msg' =>'Conexión a '.$config['type'].' falló!'."\n".
                'Revisar la configuración para la base de datos '.$config['name'].
                ' en el servidor '.$config['host'].':'.$config['port']
            ));
        }
        // retornar objeto creado si la conexión fue ok
        return self::$_databases[$database];
    }

    /**
     * Cerrar conexiones a las bases de datos
     * 
     * Se puede indicar solo una base de datos para cerrar, si no se
     * hace se cerrarán todas (en realidad se hace unset a la base de
     * datos, se espera que el destructor de la clase de la base de
     * datos la cierre). Si no se cierran mediante este método las bases
     * de datos serán cerradas al finalizar el script.
     * @param database La base de datos que se desea cerrar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-09-09
     */
    public static function close ($database = '')
    {
        // si se especifico una base de datos se cierra solo esa
        if (!empty($database)) {
            self::$_databases[$database] = null;
            unset(self::$_databases[$database]);
        }
        // si no se especifico se cierran todas
        else {
            $databases = array_keys(self::$_databases);
            foreach ($databases as &$database) {
                self::$_databases[$database] = null;
                unset(self::$_databases[$database]);
            }
        }
    }

}

// si la clase no existe se debe definir
if(!class_exists('Exception_Model_Datasource_Database')) {
    class Exception_Model_Datasource_Database extends \RuntimeException
    {
        protected $_messageTemplate = '%s';
        public function __construct($message, $code = 500)
        {
            if (is_array($message)) {
                $message = vsprintf($this->_messageTemplate, $message);
            }
            parent::__construct($message, $code);
        }
    }
}
