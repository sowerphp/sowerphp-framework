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
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-10-16
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
     * @version 2014-10-16
     */
    public function __construct($host = '127.0.0.1', $port = 11211, $prefix = false)
    {
        if (class_exists('\Memcached')) {
            $this->_cache = new \Memcached();
            $this->_cache->addServer($host, $port);
            if ($prefix) {
                $this->_prefix = $prefix;
            } else if (defined('DIR_PROJECT')) {
                $this->_prefix = DIR_PROJECT.':';
            } else {
                $this->_prefix = '';
            }
        }
    }

    /**
     * Método que guarda en memoria un valor a ser cacheado
     * @param key Clave que tendrá el elemento en la caché
     * @param value Valor del elemento en la caché
     * @param expires Tiempo en segundos que se debe almacenar en memoria
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
     * Método para eliminar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-16
     */
    public function delete($key)
    {
        if (!$this->_cache) return false;
        return $this->_cache->delete($this->_prefix.$key);
    }

}
