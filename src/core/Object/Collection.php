<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
 * Copyright (C) SowerPHP <https://www.sowerphp.org>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace sowerphp\core;

/**
 * Clase para manejar colecciones de objetos
 */
abstract class Object_Collection
{

    protected $_loaded = []; ///< Hash de los objetos cargados

    /**
     * Método para cargar los objetos
     */
    abstract public function load ($name, $options = []);

    /**
     * Obtiene un objeto desde la colección
     * @param name Nombre del objeto que se quiere obtener
     * @return object|null El objeto solicitado o null si no lo encontró
     */
    public function __get($name)
    {
        if (isset($this->_loaded[$name])) {
            return $this->_loaded[$name];
        }
        return null;
    }

    /**
     * Verifica si un objeto existe dentro de la colección
     * @param Nombre del objeto que se quiere verificar si existe
     * @return bool =true si existe, =false si no existe
     */
    public function __isset($name)
    {
        return isset($this->_loaded[$name]);
    }

    /**
     * Normaliza un arreglo de objetos, para una carga más simple
     * @param objects Objetos a normalizar
     * @return array Objetos normalizados
     */
    public static function normalizeObjectArray($objects)
    {
        $normal = [];
        foreach ($objects as $i => $objectName) {
            $options = [];
            if (!is_int($i)) {
                $options = (array)$objectName;
                $objectName = $i;
            }
            list($plugin, $name) = Module::split($objectName);
            $normal[$name] = array('class' => $objectName, 'settings' => $options);
        }
        return $normal;
    }

}
