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
 * Clase para desplegar los errores que se generan en la ejecución de la
 * aplicación.
 */
class Controller_Error extends \Controller_App
{

    /**
     * Renderizar error.
     *
     * @param array $data Datos que se deben pasar a la vista del error.
     * @return Network_Response
     */
    public function display(array $data = []): Network_Response
    {
        $this->layout .= '.min';
        $data['error_reporting'] = config('app.debug');
        $layersService = app('layers');
        $data['message'] = htmlspecialchars($data['message']);
        $data['trace'] = str_replace(
            [
                $layersService->getFrameworkPath(),
                $layersService->getProjectPath(),
            ],
            [
                'framework:',
                'project:',
            ],
            $data['trace']
        );
        $data['soporte'] = config('email.default') !== null;
        $this->set($data); // TODO: Quitar uso de $this->controller->viewVars en Log::terminate()
        return $this->render('Error/error');
    }

}
