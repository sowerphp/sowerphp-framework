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
 * Clase para manejar los errores producidos en la aplicación
 */
class Error
{

    /**
     * Método para manejar los errores ocurridos en la aplicación.
     */
    public static function handler($level, $message, $file, $line)
    {
        // Cerrar la sesión antes de lanzar la excepción si la sesión está activa
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        // Lanzar la excepción personalizada
        throw new Exception_Error($message, $level, $file, $line);
    }

}
