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
 * Clase para desplegar los errores que se generan en la ejecución de la
 * aplicación
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-23
 */
class Controller_Error extends \Controller_App
{

    public $error_reporting; ///< Si se debe o no mostrar los errores exactos de las páginas

    /**
     * Renderizar error
     * @param data Datos qye se deben pasar a la vista del error
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-04-28
     */
    public function display ($data)
    {
        // agregar datos para la vista
        $data['trace'] = str_replace(
            [DIR_FRAMEWORK, DIR_WEBSITE],
            ['DIR_FRAMEWORK', 'DIR_WEBSITE'],
            $data['trace']
        );
        $this->set($data);
        // mostrar error exacto solo si se debe
        if ($this->error_reporting)
            $this->render('Error/error_reporting');
        // mostrar error "genérico"
        else
            $this->render('Error/silence');
    }

}
