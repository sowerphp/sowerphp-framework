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
 * Clase para sistema de caché utilizando Memcached
 *
 * Funciona como wrapper de la API de PHP permitiendo el uso de un prefijo para
 * las claves de Memcached y de esta forma poder compartir fácilmente un mismo
 * servidor Memcached entre varias aplicaciones.
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-11-18
 */
class Cache
{

    private $_cache = null; ///< Objeto Memcached
    private $_prefix; ///< Prefijo a utilizar en la clave del elemento en caché
    public static $setCount = 0; ///< Contador para sets realizados
    public static $getCount = 0; ///< Contador para gets realizados

    /**
     * Método que guarda en memoria un valor a ser cacheado
     * @param host Hostname del servidor que corre Memcached
     * @param port Puerto donde Memcached está escuchando
     * @param prefix Prefijo que se utilizará en las claves de los elementos del caché
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-12-25
     */
    public function __construct($host = '127.0.0.1', $port = 11211, $prefix = false)
    {
        if (class_exists('\Memcached')) {
            $this->_cache = new \Memcached();
            $this->_cache->addServer($host, $port);
            $this->_prefix = $prefix ? $prefix : defined('DIR_PROJECT') ? DIR_PROJECT.':' : '';
        }
    }

    /**
     * Método que guarda en memoria un valor a ser cacheado
     * @param key Clave que tendrá el elemento en la caché
     * @param value Valor del elemento en la caché
     * @param expires Tiempo en segundos que se debe almacenar en memoria
     * @return =true si se pudo asignar el elemento en la caché
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-16
     */
    public function set($key, $value, $expires=600)
    {
        if (!$this->_cache) return false;
        $result = $this->_cache->set($this->_prefix.$key, $value, $expires);
        if ($result) self::$setCount++;
        return $result;
    }

    /**
     * Método para recuperar un elemento desde la caché
     * @param key clave del elemento que se desea recuperar desde la caché
     * @return elemento solicitado o =false si no se pudo recuperar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-16
     */
    public function get($key)
    {
        if (!$this->_cache) return false;
        $result = $this->_cache->get($this->_prefix.$key);
        if ($result) self::$getCount++;
        return $result;
    }

    /**
     * Método para eliminar un elemento de la caché
     * @param key clave del elemento que se desea eliminar de la caché
     * @return =true en caso que se haya podido eliminar el elemento de la caché
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-16
     */
    public function delete($key)
    {
        if (!$this->_cache) return false;
        return $this->_cache->delete($this->_prefix.$key);
    }

    /**
     * Método que obtiene todas las claves de elementos almacenados en la caché
     * @return Arreglo con las claves que están actualmente almacenadas
     * @author Esteban De sLa Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-11-18
     */
    public function getAllKeys()
    {
        if (!$this->_cache) return false;
        $allKeys = $this->_cache->getAllKeys();
        if (!$allKeys) return false;
        $keys = [];
        $start = strlen($this->_prefix);
        foreach ($allKeys as &$key) {
            if (strpos($key, $this->_prefix)===0) {
                $keys[] = substr($key, $start);
            }
        }
        return $keys;
    }

    /**
     * Método que obtiene los elementos de las claves indicadas en la caché
     * @param keys Arreglo con las claves que se desen obtener
     * @return Arreglo con las claves como índices y los elementos como valores
     * @author Esteban De sLa Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-11-18
     */
    public function getMulti($keys)
    {
        if (!$this->_cache) return false;
        foreach ($keys as &$key) {
            $key = $this->_prefix.$key;
        }
        $vals = $this->_cache->getMulti($keys);
        if (!$vals) return false;
        $start = strlen($this->_prefix);
        foreach ($vals as $key => &$val) {
            $vals[substr($key, $start)] = $val;
            unset($vals[$key]);
        }
        return $vals;
    }

    /**
     * Método que elimina todos los elementos de la caché
     * @return =true si fue posible hacer el flush
     * @author Esteban De sLa Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-11-18
     */
    public function flush()
    {
        if (!$this->_cache) return false;
        $keys = $this->getAllKeys();
        foreach ($keys as &$key) {
            $key = $this->_prefix.$key;
        }
        return $this->_cache->deleteMulti($keys);
    }

    /**
     * Método que ejecutará un método del objeto de Memcached al no existir el
     * método en la clase Cache
     * @return Valor de retorno original del método que se estpa ejecutando
     * @author Esteban De sLa Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-11-18
     */
    public function __call($method, $args)
    {
        if (method_exists($this->_cache, $method)) {
            return call_user_func_array([$this->_cache, $method], $args);
        } else return false;
    }

}
