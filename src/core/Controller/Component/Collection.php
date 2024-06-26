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
 * Clase para manejar una colección de componentes.
 */
class Controller_Component_Collection
{

    protected $_Controller = null; ///< Controlador donde esta colección esta siendo inicializada
    protected $_loaded = []; ///< Arreglo con los componentes ya cargados

    /**
     * Obtiene un objeto desde la colección.
     * @param string $name Nombre del objeto que se quiere obtener.
     * @return object|null El objeto solicitado o null si no lo encontró.
     */
    public function __get($name)
    {
        if (isset($this->_loaded[$name])) {
            return $this->_loaded[$name];
        }
        return null;
    }

    /**
     * Verifica si un objeto existe dentro de la colección.
     * @param string $name Nombre del objeto que se quiere verificar si existe.
     * @return bool =true si existe, =false si no existe.
     */
    public function __isset($name)
    {
        return isset($this->_loaded[$name]);
    }

    /**
     * Normaliza un arreglo de objetos, para una carga más simple.
     * @param array $objects Objetos a normalizar.
     * @return array Objetos normalizados.
     */
    private function normalizeObjectArray(array $objects)
    {
        $normal = [];
        foreach ($objects as $i => $objectName) {
            $options = [];
            if (!is_int($i)) {
                $options = (array)$objectName;
                $objectName = $i;
            }
            list($plugin, $name) = $this->splitModuleName($objectName);
            $normal[$name] = [
                'class' => $objectName,
                'settings' => $options,
            ];
        }
        return $normal;
    }

    /**
     * Separar el nombre del módulo del nombre de la clase que se desea cargar.
     * @param string $name Nombre a separar.
     * @return array Arreglo con el nombre del módulo y la clase.
     */
    private function splitModuleName(string $name): array
    {
        $lastdot = strrpos($name, '.');
        if ($lastdot !== false) {
            $module = substr($name, 0, $lastdot);
            $name = substr($name, $lastdot + 1);
        } else {
            $module = '';
        }
        return array($module, $name);
    }

    /**
     * Método que inicializa la colección de componentes.
     * Cargará cada uno de los componentes creando un atributo en el
     * controlador con el nombre del componente
     * @param Controller Controlador para el que se usan los componentes
     */
    public function init(Controller $Controller)
    {
        $this->_Controller = &$Controller;
        $components = $this->normalizeObjectArray(
            $Controller->components
        );
        foreach ($components as $name => $properties) {
            $Controller->{$name} = $this->load (
                $properties['class'], $properties['settings']
            );
        }
    }

    /**
     * Función que carga y construye el componente
     * @param component Componente que se quiere cargar
     * @param settins Opciones para el componente
     * @return Componente cargado
     */
    public function load($component, $settings = [])
    {
        // si ya se cargó solo se retorna
        if (isset($this->_loaded[$component])) {
            return $this->_loaded[$component];
        }
        // cargar clase para el componente, si no existe error
        $componentClass = 'Controller_Component_' . $component;
        if (!class_exists($componentClass)) {
            throw new Exception_Controller_Component_Missing(array(
                'class' => $componentClass
            ));
        }
        // cargar componente
        $this->_loaded[$component] = new $componentClass($this, $settings);
        $this->_loaded[$component]->controller = &$this->_Controller;
        // retornar componente
        return $this->_loaded[$component];
    }

    /**
     * Lanzar métodos de todos los componentes cargados para el evento
     * callback.
     * @param string $callback Función que se debe ejecutar (ej: boot o
     * terminate).
     * @param array $params Parámetros que se pasarán a la función callback.
     */
    public function trigger(string $callback, array $params = [])
    {
        foreach ($this->_loaded as $object) {
            call_user_func_array(array($object, $callback), $params);
        }
    }

}
