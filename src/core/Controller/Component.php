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
 * Clase base para todos los componentes
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
     */
    public function __construct(Controller_Component_Collection $Components, $settings = [])
    {
        $this->Components = $Components;
        $this->settings = Utility_Array::mergeRecursiveDistinct(
            $this->settings, $settings
        );
    }

    /**
     * Método llamado desde Controller::boot()
     * Deberá se sobreescrito en el componente si se quiere utilizar
     */
    public function boot()
    {
    }

    /**
     * Método llamado desde Controller::terminate()
     * Deberá se sobreescrito en el componente si se quiere utilizar
     */
    public function terminate()
    {
    }

}
