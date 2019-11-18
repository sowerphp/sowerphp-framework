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
 * @version 2019-05-29
 */
class Network_Email_Smtp_Phpmailer
{

    protected $_config = null; ///< Configuración para SMTP
    protected $_header = null; ///< Datos de la cabecera del mensaje
    protected $_data = null; ///< Datos del mensaje (incluyendo adjuntos)

    /**
     * Constructor de la clase
     * @param config Arreglo con la configuración del correo a enviar
     * @param header Cabecerá del correo electrónico
     * @param data Datos (cuerpo) de correo electrónico
     * @param debug =true se muestra debug, =false modo silencioso
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-11-18
     */
    public function __construct($config, $header, $data, $debug = false)
    {
        // verificar soporte PHPMailer
        if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
            throw new \Exception('No hay soporte para PHPMailer');
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
        $this->_config = array(
            'host' => $config['host'],
            'port' => $config['port'],
            'auth' => isset($config['auth']) ? (bool)$config['auth'] : true,
            'username' => $config['user'],
            'password' => $config['pass'],
            'secure' => $config['secure'] === false ? null : (!empty($config['secure']) ? $config['secure'] : 'ssl'), // ssl o tls
            'debug' => (int)$debug,
            'verify_ssl' => isset($config['verify_ssl']) ? (bool)$config['verify_ssl'] : true,
        );
        // Cabecera
        $this->_header = $header;
        // Datos
        $this->_data = $data;
    }

    /**
     * Método que envía el correo
     * @return Arreglo con los estados de retorno por cada correo enviado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-11-18
     */
    public function send()
    {
        // crear correo con su configuración
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $this->_config['host'];
        $mail->SMTPAuth = $this->_config['auth'];
        $mail->Username = $this->_config['username'];
        $mail->Password = $this->_config['password'];
        if (!empty($this->_config['secure'])) {
            $mail->SMTPSecure = $this->_config['secure'];
        }
        $mail->Port = $this->_config['port'];
        $mail->SMTPDebug = $this->_config['debug'];
        $mail->CharSet = 'UTF-8';
        // no validar SSL
        if (!$this->_config['verify_ssl']) {
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ]
            ];
        }
        // agregar quien envía el correo
        if (!empty($this->_header['from'])) {
            if (is_array($this->_header['from'])) {
                $mail->setFrom($this->_header['from']['email'], $this->_header['from']['name']);
            } else {
                $mail->setFrom($this->_header['from']);
            }
        }
        // agregar a quien responder el correo
        if (!empty($this->_header['replyTo'])) {
            if (is_array($this->_header['replyTo'])) {
                $mail->addReplyTo($this->_header['replyTo']['email'], $this->_header['replyTo']['name']);
            } else {
                $mail->addReplyTo($this->_header['replyTo']);
            }
        }
        // agregar destinatarios
        if (!empty($this->_header['to'])) {
            foreach ($this->_header['to'] as $to) {
                if (is_array($to)) {
                    $mail->addAddress($to['email'], $to['name']);
                } else {
                    $mail->addAddress($to);
                }
            }
        }
        // agregar destinatarios en copia
        if (!empty($this->_header['cc'])) {
            foreach ($this->_header['cc'] as $cc) {
                if (is_array($cc)) {
                    $mail->addCC($cc['email'], $cc['name']);
                } else {
                    $mail->addCC($cc);
                }
            }
        }
        // agregar destinatarios en copia oculta
        if (!empty($this->_header['bcc'])) {
            foreach ($this->_header['bcc'] as $bcc) {
                if (is_array($bcc)) {
                    $mail->addBCC($bcc['email'], $bcc['name']);
                } else {
                    $mail->addBCC($bcc);
                }
            }
        }
        // asignar asunto
        $mail->Subject = $this->_header['subject'];
        // agregar mensaje
        if (!empty($this->_data['html'])) {
            $mail->isHTML(true);
            $mail->Body = $this->_data['html'];
            $mail->AltBody = $this->_data['text'];
        } else {
            $mail->Body = $this->_data['text'];
        }
        // agregar adjuntos
        if (!empty($this->_data['attach'])) {
            foreach ($this->_data['attach'] as $file) {
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

}
