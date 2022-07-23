<?php

/**
 * SowerPHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
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
 * Clase para enviar correo electrónico mediante SMTP
 * Requiere: PHPMailer
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2020-02-09
 */
class Network_Email_Smtp_Phpmailer
{

    protected $config = null; ///< Configuración para SMTP usando PHPMailer

    /**
     * Constructor de la clase
     * @param config Arreglo con la configuración de PHPMailer
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2022-07-23
     */
    public function __construct($config)
    {
        // verificar soporte PHPMailer
        if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
            throw new \sowerphp\core\Exception('No hay soporte para PHPMailer');
        }
        // valores por defecto para conexión vía SMTP usando Pear
        $config = array_merge([
            'host' => 'localhost',
            'port' => 25,
            'user' => null,
            'pass' => null,
        ], $config);
        // extraer puerto si se pasó en el host
        $url = parse_url($config['host']);
        if (isset($url['port'])) {
            $config['host'] = str_replace(':'.$url['port'], '', $config['host']);
            $config['port'] = $url['port'];
        }
        // si no están los campos mínimos necesarios error
        if (empty($config['host']) || empty($config['port']) || empty($config['user']) || empty($config['pass'])) {
             throw new \sowerphp\core\Exception('Configuración del correo electrónico incompleta');
        }
        // determinar host y seguridad si existe
        if (strpos($config['host'], '://')) {
            list($config['secure'], $config['host']) = explode('://', $config['host']);
        }
        // determinar opciones extras si existen
        if (strpos($config['host'], '/')) {
            $aux = explode('/', $config['host']);
            $config['host'] = array_shift($aux);
            foreach ($aux as $option) {
                if ($option=='novalidate-cert') {
                    $config['verify_ssl'] = false;
                }
            }
        }
        if (!isset($config['secure'])) {
            $config['secure'] = $config['port'] == 25 ? false : null;
        }
        // Configuración para la conexión al servidor
        $this->config = array(
            'host' => $config['host'],
            'port' => $config['port'],
            'auth' => isset($config['auth']) ? (bool)$config['auth'] : true,
            'username' => $config['user'],
            'password' => $config['pass'],
            'secure' => $config['secure'] === false ? null : (!empty($config['secure']) ? $config['secure'] : 'ssl'), // ssl o tls
            'debug' => (int)$config['debug'],
            'verify_ssl' => isset($config['verify_ssl']) ? (bool)$config['verify_ssl'] : true,
        );
    }

    /**
     * Método que envía el correo
     * @param data Arreglo con los datos que se enviarán (texto y adjuntos)
     * @param header Cabeceras del correo
     * @return Arreglo con los estados de retorno por cada correo enviado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2020-02-09
     */
    public function send($data, $header)
    {
        // crear correo
        $mail = self::createEmail($data, $header);
        // agregar opciones al correo
        $mail->isSMTP();
        $mail->Host = $this->config['host'];
        $mail->SMTPAuth = $this->config['auth'];
        $mail->Username = $this->config['username'];
        $mail->Password = $this->config['password'];
        if (!empty($this->config['secure'])) {
            $mail->SMTPSecure = $this->config['secure'];
        }
        $mail->Port = $this->config['port'];
        $mail->SMTPDebug = $this->config['debug'];
        // no validar SSL
        if (!$this->config['verify_ssl']) {
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ]
            ];
        }
        // enviar mensaje
        try {
            $mail->send();
            return true;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            return [
                'type' => 'error',
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Método que crea el correo con PHPMailer (pero no lo envía)
     * @param data Arreglo con los datos que se enviarán (texto y adjuntos)
     * @param header Cabeceras del correo
     * @return Arreglo con los estados de retorno por cada correo enviado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2020-02-09
     */
    public static function createEmail($data, $header)
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        // agregar quien envía el correo
        if (!empty($header['from'])) {
            if (is_array($header['from'])) {
                $mail->setFrom($header['from']['email'], $header['from']['name']);
            } else {
                $mail->setFrom($header['from']);
            }
        }
        // agregar a quien responder el correo
        if (!empty($header['replyTo'])) {
            if (is_array($header['replyTo'])) {
                $mail->addReplyTo($header['replyTo']['email'], $header['replyTo']['name']);
            } else {
                $mail->addReplyTo($header['replyTo']);
            }
        }
        // agregar destinatarios
        if (!empty($header['to'])) {
            foreach ($header['to'] as $to) {
                if (is_array($to)) {
                    $mail->addAddress($to['email'], $to['name']);
                } else {
                    $mail->addAddress($to);
                }
            }
        }
        // agregar destinatarios en copia
        if (!empty($header['cc'])) {
            foreach ($header['cc'] as $cc) {
                if (is_array($cc)) {
                    $mail->addCC($cc['email'], $cc['name']);
                } else {
                    $mail->addCC($cc);
                }
            }
        }
        // agregar destinatarios en copia oculta
        if (!empty($header['bcc'])) {
            foreach ($header['bcc'] as $bcc) {
                if (is_array($bcc)) {
                    $mail->addBCC($bcc['email'], $bcc['name']);
                } else {
                    $mail->addBCC($bcc);
                }
            }
        }
        // asignar asunto
        $mail->Subject = $header['subject'];
        // agregar mensaje
        if (!empty($data['html'])) {
            $mail->isHTML(true);
            $mail->Body = $data['html'];
            $mail->AltBody = $data['text'];
        } else {
            $mail->Body = $data['text'];
        }
        // agregar adjuntos
        if (!empty($data['attach'])) {
            foreach ($data['attach'] as $file) {
                // leer desde archivo
                if (!empty($file['tmp_name'])) {
                    $mail->addAttachment($file['tmp_name'], $file['name']);
                }
                // leer desde datos de una variable
                else if (!empty($file['data'])) {
                    $mail->addStringAttachment($file['data'], $file['name']);
                }
            }
        }
        // entregar objeto del correo
        return $mail;
    }

}
