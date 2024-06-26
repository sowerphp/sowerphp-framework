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
 * Facade para escribir y recuperar mensajes de estado de una sesión de
 * usuario de la aplicación.
 */
class Facade_Session_Message
{

    /**
     * Plantilla para el renderizado de los mensajes de la sesión.
     *
     * @var string
     */
    protected static $messageTemplate = '<div class="alert alert-%s" role="alert"><div class="float-end"><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button></div><i class="%s fa-fw me-2" aria-hidden="true"></i><span class="visually-hidden">%s: </span>%s</div>';

    /**
     * Configuración de los mensajes según su tipo.
     *
     * Se indica el estilo (style) de Bootstrap y el icono (icon) de Font
     * Awesome para ser utilizado en el renderizado.
     *
     * @var array
     */
    protected static $config = [
        'info' => [
            'style' => 'info',
            'icon' => 'fa-solid fa-info-circle',
        ],
        'success' => [
            'style' => 'success',
            'icon' => 'fa-solid fa-check-circle',
        ],
        'warning' => [
            'style' => 'warning',
            'icon' => 'fa-solid fa-exclamation-triangle',
        ],
        'error' => [
            'style' => 'danger',
            'icon' => 'fa-solid fa-exclamation-circle',
        ],
    ];

    /**
     * Escribir un mensaje informativo en la sesión del usuario.
     *
     * @param string $message Mensaje informativo que se desea escribir.
     * @return void
     */
    public static function info(string $message): void
    {
        self::write($message, 'info');
    }

    /**
     * Escribir un mensaje satisfactorio (ok) en la sessión del usuario.
     *
     * @param string $message Mensaje satisfactorio que se desea escribir.
     * @return void
     */
    public static function success(string $message): void
    {
        self::write($message, 'success');
    }

    /**
     * Escribir un mensaje de advertencia en la sesión del usuario.
     *
     * @param string $message Mensaje de advertencia que se desea escribir.
     * @return void
     */
    public static function warning(string $message): void
    {
        self::write($message, 'warning');
    }

    /**
     * Escribir un mensaje de error (danger) en la sesión del usuario.
     *
     * @param string $message Mensaje de error que se desea escribir.
     * @return void
     */
    public static function error(string $message): void
    {
        self::write($message, 'error');
    }

    /**
     * Método para escribir un mensaje en la sesión.
     * @param string $message Mensaje que se desea escribir.
     * @param string $type Tipo de mensaje: info, success, warning o error.
     */
    public static function write(string $message, string $type = 'info'): void
    {
        if ($type == 'ok') {
            $type = 'success';
        }
        else if ($type == 'danger') {
            $type = 'error';
        }
        $messages = session()->get('status.messages', []);
        $messages[] = [
            'timestamp' => microtime(true),
            'type' => $type,
            'text' => $message,
        ];
        session()->flash('status.messages', $messages);
    }

    /**
     * Obtiene todos los mensajes de la sesión.
     *
     * @return array Arreglo con el listado de mensajes y su configuración.
     */
    public static function readAll(): array
    {
        $messages = session('status.messages', []);
        $messages = array_map(function($message) {
            return array_merge($message, self::$config[$message['type']]);
        }, $messages);
        return $messages;
    }

    /**
     * Generar una cadena de texto con todos los mensajes de estado que
     * actualmente están en la sesión.
     *
     * @return string
     */
    public static function getMessagesAsString(): string
    {
        $messages = self::readAll();
        $buffer = '';
        foreach ($messages as $message) {
            $buffer .= sprintf(
                self::$messageTemplate,
                $message['style'],
                $message['icon'],
                $message['type'],
                message_format($message['text'])
            );
        }
        return $buffer;
    }

}
