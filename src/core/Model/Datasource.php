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
 * Clase base para las fuentes de datos, permite obtener la configuración de las
 * mismas y mantener un caché con los objetos que representan las conexiones a
 * las fuentes de datos.
 */
abstract class Model_Datasource
{

    protected static $datasources; ///< Objetos con caché para fuentes de datos

    /**
     * Método que permite obtener la fuente de datos si está en caché o bien la
     * configuración si no está en caché para que pueda ser creada
     * @param datasource Identificador de la fuente de datos (ej: database)
     * @param name Nombre de la configuración o arreglo con la configuración
     * @param config Arreglo con la configuración
     */
    public static function getDatasource($datasource, $name = 'default', $config = [])
    {
        // si el objeto solicitado está en caché se entrega
        if (is_string($name) && isset(self::$datasources[$datasource][$name])) {
            return self::$datasources[$datasource][$name];
        }
        // si $name es un arreglo entonces es la configuración lo que se pasó
        if (is_array($name)) {
            $config = $name;
            $name = 'default';
        }
        // se crea configuración
        $config = array_merge((array)config($datasource.'.'.$name), $config);
        if (empty($config)) {
            throw new Exception_Database (array(
                'msg' => 'No se encontró configuración '.$datasource.'.'.$name
            ));
        }
        // si no está el nombre de la configuración se asigna
        if (!isset($config['conf'])) {
            $config['conf'] = is_string($name) ? $name : 'default';
        }
        // entregar configuración
        return $config;
    }

}
