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
 * Clase para desplegar los errores que se generan en la ejecución de la
 * aplicación
 */
class Controller_Error extends \Controller_App
{

    public $error_reporting; ///< Si se debe o no mostrar los errores exactos de las páginas

    /**
     * Renderizar error
     * @param data Datos qye se deben pasar a la vista del error
     */
    public function display($data)
    {
        // agregar datos para la vista
        if ($this->error_reporting) {
            $data['message'] = htmlspecialchars($data['message']);
            $data['trace'] = str_replace(
                [DIR_FRAMEWORK, DIR_WEBSITE],
                ['DIR_FRAMEWORK', 'DIR_WEBSITE'],
                $data['trace']
            );
        } else {
            unset($data['message'], $data['exception'], $data['trace']);
        }
        $this->layout .= '.min';
        $this->set($data);
        $this->set('soporte', \sowerphp\core\Configure::read('email.default') !== null);
        $this->render('Error/error');
    }

}
