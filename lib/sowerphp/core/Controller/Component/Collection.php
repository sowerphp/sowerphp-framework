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
 * Clase para manejar una colección de componentes
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-22
 */
class Controller_Component_Collection extends Object_Collection
{

    protected $_Controller = null; ///< Controlador donde esta colección esta siendo inicializada
    protected $_loaded = array(); ///< Arreglo con los componentes ya cargados

    /**
     * Método que inicializa la colección de componentes.
     * Cargará cada uno de los componentes creando un atributo en el
     * controlador con el nombre del componente
     * @param Controller Controlador para el que se usan los componentes
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-14
     */
    public function init (Controller $Controller)
    {
        $this->_Controller = &$Controller;
        $components = self::normalizeObjectArray (
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
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-05-04
     */
    public function load ($component, $settings = array())
    {
        // si ya se cargó solo se retorna
        if (isset($this->_loaded[$component])) {
            return $this->_loaded[$component];
        }
        // cargar clase para el componente, si no existe error
        $componentClass = App::findClass('Controller_Component_'.$component);
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
     * callback
     * @param callback Función que se debe ejecutar (ej: beforeRender)
     * @param params Parámetros que se pasarán a la función callback
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-14
     */
    public function trigger($callback, $params = [])
    {
        foreach ($this->_loaded as $object) {
            call_user_func_array(array($object, $callback), $params);
        }
    }

}
