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

namespace sowerphp\app;

/**
 * Clase que implementa los servicios web para subir archivos a la aplicación
 */
class Controller_Upload extends \sowerphp\autoload\Controller
{

    /**
     * Servicio web para subir una imagen usando diferentes métodos
     */
    public function _api_image_POST($method = 'imgur')
    {
        if (empty($_FILES['file'])) {
            return response()->json(
                __('Debe enviar la imagen'), 
                400
            );
        }
        $class = 'Utility_Upload_Image_' . \sowerphp\core\Utility_Inflector::camelize($method);
        if (!class_exists($class)) {
            return response()->json(
                __('No se encontró el método "%(method)s" para subir la imagen',
                    [
                        'method' => $method
                    ]
                ),
                400
            );
        }
        $Method = new $class();
        $location = $Method->upload($_FILES['file']);
        return response()->json(
            ['location' => $location]
        );
    }

}
