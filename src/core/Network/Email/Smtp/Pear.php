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

// clases PEAR
require_once('Mail.php');
require_once('Mail/mime.php');

/**
 * Clase para enviar correo electrónico mediante SMTP
 * Requiere:
 *   # pear install Mail Mail_mime Net_SMTP
 */
class Network_Email_Smtp_Pear
{

    protected $config = null; ///< Configuración para SMTP

    /**
     * Constructor de la clase
     * @param config Arreglo con la configuración de Pear/Mail
     */
    public function __construct($config)
    {
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
        // Configuración para la conexión al servidor
        $this->config = [
            'host' => $config['host'],
            'port' => $config['port'],
            'auth' => isset($config['auth']) ? (bool)$config['auth'] : true,
            'username' => $config['user'],
            'password' => $config['pass'],
            'debug' => $config['debug'],
        ];
        // desactivar errores (ya que Mail no pasa validación E_STRICT)
        ini_set('error_reporting', false);
    }

    /**
     * Método que envía el correo
     * @param data Arrelgo con los datos que se enviarán (texto y adjuntos)
     * @param header Cabeceras del correo
     * @return array Arreglo con los estados de retorno por cada correo enviado
     */
    public function send($data, $header)
    {
        // Crear correo
        $mailer = \Mail::factory('smtp', $this->config);
        $mail = new \Mail_mime();
        // Asignar mensaje
        $mail->setTXTBody($data['text']);
        $mail->setHTMLBody($data['html']);
        // Si existen archivos adjuntos agregarlos
        if (!empty($data['attach'])) {
            foreach ($data['attach'] as &$file) {
                $result = $mail->addAttachment(
                    isset($file['tmp_name']) ? $file['tmp_name'] : $file['data'],
                    $file['type'],
                    $file['name'],
                    isset($file['tmp_name']) ? true : false
                );
                if (is_a($result, 'PEAR_Error')) {
                    return [
                        'type' => $result->getType(),
                        'code' => $result->getCode(),
                        'message' => $result->getMessage(),
                    ];
                }
            }
        }
        // cuerpo y cabecera con codificación en UTF-8
        $body = $mail->get([
            'text_encoding' => '8bit',
            'text_charset'  => 'UTF-8',
            'html_charset'  => 'UTF-8',
            'head_charset'  => 'UTF-8',
            'head_encoding' => '8bit',
        ]); // debe llamarse antes de headers
        $headers_data = [
            'From' => is_array($header['from']) ? ($header['from']['name'].' <'.$header['from']['email'].'>') : $header['from'],
            'Subject' => $header['subject'],
        ];
        $to = [];
        if (!empty($header['to'])) {
            $to = array_merge($to, $header['to']);
            $headers_data['To'] = implode(', ', $header['to']);
        }
        if (!empty($header['cc'])) {
            $to = array_merge($to, $header['cc']);
            $headers_data['Cc'] = implode(', ', $header['cc']);
        }
        if (!empty($header['replyTo'])) {
            $headers_data['Reply-To'] = is_array($header['replyTo']) ? ($header['replyTo']['name'].' <'.$header['replyTo']['email'].'>') : $header['replyTo'];
            // WARNING Gmail requiere que se pase como arreglo pero amazon requiere solo el email (?)
            $headers_data['Reply-To'] = [$headers_data['Reply-To']]; // Esto se debería corregir de alguna forma para que sea compatible con ambos (por ahora, solo gmail o similares)
        }
        if (!empty($header['bcc'])) {
            $to = array_merge($to, $header['bcc']);
        }
        $headers = $mail->headers($headers_data);
        $to = implode(', ', $to);
        // Enviar correo a todos los destinatarios
        $result = $mailer->send($to, $headers, $body);
        // retornar estado del envío del mensaje
        if (is_a($result, 'PEAR_Error')) {
            return [
                'type' => $result->getType(),
                'code' => $result->getCode(),
                'message' => $result->getMessage(),
            ];
        } else {
            return true;
        }
    }

}
