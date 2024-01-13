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
 * Clase para enviar correo electrónico mediante Mailgun
 * Requiere:
 *   $ composer require mailgun/mailgun-php kriswallsmith/buzz nyholm/psr7
 */
class Network_Email_Api_Mailgun
{

    protected $config = null; ///< Configuración para envíos usando Mailgun
    protected $mg; ///< Objeto de Mailgun para los envíos

    /**
     * Constructor de la clase
     * @param config Arreglo con la configuración de Mailgun
     */
    public function __construct($config)
    {
        // verificar soporte Mailgun
        if (!class_exists('\Mailgun\Mailgun')) {
            throw new \sowerphp\core\Exception('No hay soporte para Mailgun');
        }
        // valores por defecto para conexión vía Mailgun
        $this->config = array_merge([
            'endpoint' => 'api.mailgun.net',
        ], $config);
        // si no están los campos mínimos necesarios error
        if (empty($this->config['domain']) || empty($this->config['secret']) || empty($this->config['endpoint'])) {
            throw new \sowerphp\core\Exception('Configuración del correo electrónico incompleta');
        }
        // crar objeto mg
        $this->mg = \Mailgun\Mailgun::create($this->config['secret'], 'https://'.$this->config['endpoint']);
    }

    /**
     * Método que envía el correo
     * @param data Arrelgo con los datos que se enviarán (texto y adjuntos)
     * @param header Cabeceras del correo
     * @return mixed =true si se envió el correo o arreglo con error
     * @link https://documentation.mailgun.com/en/latest/api-sending.html#sending
     */
    public function send($data, $header)
    {
        $email = [];
        // agregar quien envía el correo
        if (!empty($header['from'])) {
            if (is_array($header['from'])) {
                $email['from'] = $header['from']['name'].' <'.$header['from']['email'].'>';
            } else {
                $email['from'] = $header['from'];
            }
        }
        // agregar a quien responder el correo
        if (!empty($header['replyTo'])) {
            if (is_array($header['replyTo'])) {
                $email['h:Reply-To'] = $header['replyTo']['name'].' <'.$header['replyTo']['email'].'>';
            } else {
                $email['h:Reply-To'] = $header['replyTo'];
            }
        }
        // agregar destinatarios
        foreach (['to', 'cc', 'bcc'] as $dest_type) {
            if (!empty($header[$dest_type])) {
                $dests = [];
                foreach ($header[$dest_type] as &$dest) {
                    if (is_array($dest)) {
                        $dests[] = $dest['name'].' <'.$dest['email'].'>';
                    } else {
                        $dests[] = $dest;
                    }
                }
                $email[$dest_type] = implode(',', $dests);
            }
        }
        // asignar asunto
        $email['subject'] = $header['subject'];
        // agregar mensaje
        if (!empty($data['html'])) {
            $email['html'] = $data['html'];
        }
        if (!empty($data['text'])) {
            $email['text'] = $data['text'];
        }
        // agregar adjuntos
        if (!empty($data['attach'])) {
            $email['attachment'] = [];
            foreach ($data['attach'] as $file) {
                // leer desde archivo
                if (!empty($file['tmp_name'])) {
                    $email['attachment'][] = [
                        'filePath' => $file['tmp_name'],
                        'filename' => $file['name']
                    ];
                }
                // leer desde datos de una variable
                else if (!empty($file['data'])) {
                    $email['attachment'][] = [
                        'fileContent' => $file['data'],
                        'filename' => $file['name']
                    ];
                }
            }
        }
        // enviar mensaje
        try {
            $this->mg->messages()->send($this->config['domain'], $email);
            return true;
        } catch (\Mailgun\Exception\HttpClientException $e) {
            return [
                'type' => 'error',
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        } catch (\Mailgun\Exception\HttpServerException $e) {
            return [
                'type' => 'error',
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

}
