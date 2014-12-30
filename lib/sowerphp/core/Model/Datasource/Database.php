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
 * @version 2014-12-29
 */
class Model_Datasource_Database extends Model_Datasource
{

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
     * @version 2014-12-29
     */
    public static function &get($name = 'default', $config = [])
    {
        $config = parent::getDatasource('database', $name, $config);
        if (is_object($config)) return $config;
        $class = '\Model_Datasource_Database_'.$config['type'];
        self::$datasources['database'][$config['conf']] = new $class($config);
        if (!is_object(self::$datasources['database'][$config['conf']])) {
            throw new Exception_Model_Datasource_Database(array(
                'msg' =>'¡Conexión a database.'.$config['conf'].' ('.$config['type'].') falló!'
            ));
        }
        return self::$datasources['database'][$config['conf']];
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
     * @version 2014-12-29
     */
    public static function close($database = '')
    {
        // si se especificó una base de datos se cierra solo esa
        if (!empty($database)) {
            unset(self::$datasources['database'][$database]);
        }
        // si no se especificó se cierran todas
        else {
            $databases = array_keys(self::$datasources['database']);
            foreach ($databases as &$database) {
                self::close($database);
            }
        }
    }

}
