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

use \sowerphp\core\Model_Datasource_Session as Session;

/**
 * Clase para escribir y recuperar mensajes desde una sesión.
 */
class Model_Datasource_Session_Message
{

    public static function info(string $message): void
    {
        self::write($message, 'info');
    }

    public static function success(string $message): void
    {
        self::write($message, 'success');
    }

    public static function ok(string $message): void
    {
        self::success($message);
    }

    public static function warning(string $message): void
    {
        self::write($message, 'warning');
    }

    public static function danger(string $message): void
    {
        self::write($message, 'danger');
    }

    public static function error(string $message): void
    {
        self::danger($message);
    }

    /**
     * Método para escribir un mensaje en la sesión.
     * @param message Mensaje que se desea escribir.
     * @param type Tipo de mensaje: info, success (ok), warning o danger (error).
     */
    public static function write(string $message, string $type = 'info'): void
    {
        if ($type == 'ok') {
            $type = 'success';
        }
        else if ($type == 'error') {
            $type = 'danger';
        }
        $messages = self::flush();
        $messages[] =  [
            'text' => $message,
            'type' => $type,
        ];
        Session::write('session.messages', $messages);
    }

    /**
     * Método para recuperar todos los mensajes de la sesión y limpiarlos de la misma.
     */
    public static function flush(): array
    {
        $messages = Session::read('session.messages');
        Session::delete('session.messages');
        return $messages ? (array)$messages : [];
    }

}
