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
 * Clase para sistema de triggers
 *
 * Permite desencadenar acciones bajo ciertos eventos que se ejecutan en la
 * aplicación.
 */
class Trigger
{

    private static $handler; ///< Handler para los trigger que se ejecutarán

    /**
     * Método que asigna el handler para los triggers
     */
    public static function setHandler($handler)
    {
        self::$handler = $handler;
    }

    /**
     * Método que lanza el handler de los triggers o falla silenciosamente
     * en caso que no exista uno asociado
     */
    public static function run($trigger, $args = null)
    {
        // si no hay handler para los triggers definido se omite ejecución
        if (!isset(self::$handler)) {
            return null;
        }
        // lanzar handler para el trigger
        return call_user_func_array(self::$handler, func_get_args());
    }

}
