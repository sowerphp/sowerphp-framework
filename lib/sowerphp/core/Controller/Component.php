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
 * Clase base para todos los componentes
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-10-14
 */
abstract class Controller_Component
{

    public $settings = []; ///< Opciones del componente
    public $controller; ///< Controlador que está cargando el componente
    protected $components = []; ///< Nombre de componentes que este componente utiliza
    protected $Components = null; ///< Colección de componentes que se cargarán

    /**
     * Constructor de la clase
     * @todo Cargar componentes que este componente utilice
     * @param Components Colección de componentes
     * @param settings Opciones para la carga de componentes
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-14
     */
    public function __construct(Controller_Component_Collection $Components, $settings = array())
    {
        $this->Components = $Components;
        $this->settings = Utility_Array::mergeRecursiveDistinct (
            $this->settings, $settings
        );
    }

    /**
     * Método llamado desde Controller::beforeFilter()
     * Deberá se sobreescrito en el componente si se quiere utilizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-14
     */
    public function beforeFilter()
    {
    }

    /**
     * Método llamado desde Controller::afterFilter()
     * Deberá se sobreescrito en el componente si se quiere utilizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-14
     */
    public function afterFilter()
    {
    }

    /**
     * Método llamado desde Controller::beforeRender()
     * Deberá se sobreescrito en el componente si se quiere utilizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-14
     */
    public function beforeRender()
    {
    }

    /**
     * Método llamado desde Controller::beforeRedirect()
     * Deberá se sobreescrito en el componente si se quiere utilizar
     * @param url Dirección hacia donde se está redirigiendo
     * @param status Estado de términi del script (0 es todo ok)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-14
     */
    public function beforeRedirect($url = null, $status = null)
    {
    }

}
