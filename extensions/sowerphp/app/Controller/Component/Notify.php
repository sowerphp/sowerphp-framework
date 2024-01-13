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

namespace sowerphp\app;

/**
 * Componente para proveer de un sistema de notificaciones para la aplicación
 */
class Controller_Component_Notify extends \sowerphp\core\Controller_Component
{

    public $settings = [ ///< Opciones por defecto
        'model' => '\sowerphp\app\Sistema\Usuarios\Model_Usuario',
    ];

    /**
     * Método que envía una notificación
     * @param from Quien genera la notificación (null si es del sistema)
     * @param to A quien va dirigida la notificación (un arreglo sin son varios usuarios)
     * @param message Mensaje que se desea registrar o arreglo con el mensaje y su configuración
     * @param methods Métodos que se deberán utilizar para notificar a los destinatarios
     */
    public function send($from, $to, $message, $methods = 'db')
    {
        if (!is_array($to)) {
            $to = [$to];
        }
        if (!is_array($message)) {
            $message = ['descripcion'=>$message];
        }
        $message = array_merge([
            'fechahora' => date('Y-m-d H:i:s'),
            'gravedad' => LOG_INFO,
            'icono' => 'far fa-lightbulb',
        ], $message);
        if (!is_array($methods)) {
            $methods = [$methods];
        }
        $status = true;
        foreach ($methods as $method) {
            $method = 'send' . ucfirst($method);
            if (!$this->$method($from, $to, $message)) {
                $status = false;
            }
        }
        return $status;
    }

    /**
     * Método que envía la notificación a la base de datos
     * @param from Quien genera la notificación (null si es del sistema)
     * @param to A quien va dirigida la notificación (un arreglo sin son varios usuarios)
     * @param message Mensaje que se desea registrar o arreglo con el mensaje y su configuración
     */
    private function sendDb($from, $to, $message)
    {
        if (!\sowerphp\core\Module::loaded('Sistema.Notificaciones')) {
            return false;
        }
        $Notificacion = new \sowerphp\app\Sistema\Notificaciones\Model_Notificacion();
        foreach ($message as $key => $value) {
            $Notificacion->$key = $value;
        }
        if (!is_object($from)) {
            $from = new $this->settings['model']($from);
        }
        $Notificacion->de = $from ? $from->id : null;
        $status = true;
        foreach ($to as $user) {
            if (!is_object($user)) {
                $user = new $this->settings['model']($user);
            }
            $Notificacion->para = $user->id;
            if (!$Notificacion->save()) {
                $status = false;
            }
        }
        return $status;
    }

    /**
     * Método que envía la notificación por email
     * @param from ID del usuario que envía el mensaje o bien null para que lo envíe el sistema
     * @param to ID del usuario que recibe el mensaje
     * @param message Mensaje que se desea reportar (puede ser un arreglo asociativo)
     */
    private function sendEmail($from, $to, $message)
    {
        $email = new \sowerphp\core\Network_Email();
        if ($from) {
            $From = is_object($from) ? $from : new $this->settings['model']($from);
            $email->replyTo($From->email, $From->nombre);
        } else {
            $aux = \sowerphp\core\Configure::read('email.default')['from'];
            $email->replyTo($aux['email'], $aux['name']);
        }
        $To = is_object($to) ? $to : new $this->settings['model']($to);
        $email->to($To->email);
        $timestamp = microtime(true);
        // asunto
        $email->subject('['.\sowerphp\core\Configure::read('page.header.title').'] Notify '.$this->getFacility($from).'.'.$this->getSeverity($message['gravedad']).' '.$timestamp);
        // mensaje
        $msg = $To->nombre.",\n\nTienes una nueva notificación en la aplicación:\n\n";
        if (is_array($message['descripcion'])) {
            foreach ($message['descripcion'] as $key => $value) {
                $msg .= $key.":\n".$value."\n\n";
            }
        } else {
            $msg .= $message['descripcion']."\n\n";
        }
        // firma
        $msg .= "-- \n";
        if (isset($From)) {
            $msg .= $From->nombre."\n".$From->email."\n";
        }
        $msg .= $this->controller->request->url;
        // enviar email
        return $email->send($msg) === true ? true : false;
    }

    /**
     * Método que recupera la glosa del origen
     * @param severity Origen que se quiere obtener su glosa
     * @return string Glosa del origen
     */
    private function getFacility($facility)
    {
        if (\sowerphp\core\Module::loaded('Sistema.Notificaciones')) {
            return (new \sowerphp\app\Sistema\Notificaciones\Model_Notificacion())->getFacility($facility)->glosa;
        } else {
            return $facility ? 'USER' : 'KERN';
        }
    }

    /**
     * Método que recupera la glosa de la gravedad
     * @param severity Gravedad que se quiere obtener su glosa
     * @return string Glosa de la gravedad
     */
    private function getSeverity($severity)
    {
        if (\sowerphp\core\Module::loaded('Sistema.Notificaciones')) {
            return (new \sowerphp\app\Sistema\Notificaciones\Model_Notificacion())->getSeverity($severity)->glosa;
        } else {
            return $severity;
        }
    }

}
