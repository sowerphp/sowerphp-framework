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

namespace sowerphp\app\Sistema\General;

/**
 * Clase para el controlador asociado a la tabla moneda_cambio de la base de
 * datos
 * Comentario de la tabla:
 * Esta clase permite controlar las acciones entre el modelo y vista para la
 * tabla moneda_cambio
 */
class Controller_MonedaCambios extends \sowerphp\autoload\Controller_Model
{

    /**
     * Recurso que entrega el tipo de cambio para cierta moneda en cierto día
     */
    public function _api_tasa_GET($from, $to, $fecha = null)
    {
        if (empty($fecha)) {
            $fecha = date('Y-m-d');
        }
        $from = mb_strtoupper($from);
        $to = mb_strtoupper($to);
        return [
            $from => [
                $to => [
                    $fecha => (float)(new Model_MonedaCambio($from, $to, $fecha))->valor,
                ]
            ]
        ];
    }

}
