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
 * Clase para enviar correo electrónico mediante Postmark
 * Requiere:
 *   $ composer require wildbit/postmark-php
 */
class Network_Email_Api_Postmark
{

    protected $config = null; ///< Configuración para envíos usando Postmark
    protected $pm; ///< Objeto de Postmark para los envíos

    /**
     * Constructor de la clase
     * @param config Arreglo con la configuración de Postmark
     */
    public function __construct($config)
    {
        // verificar soporte Postmark
        if (!class_exists('\Postmark\PostmarkClient')) {
            throw new \Exception('No hay soporte para Postmark.');
        }
        // si no están los campos mínimos necesarios error
        if (empty($config['token'])) {
            throw new \Exception('Configuración del correo electrónico incompleta.');
        }
        $this->config = $config;
        // crear objeto pm
        $this->pm = new \Postmark\PostmarkClient($this->config['token']);
    }

    /**
     * Método que envía el correo
     * @param data Arrelgo con los datos que se enviarán (texto y adjuntos)
     * @param header Cabeceras del correo
     * @return mixed =true si se envió el correo o arreglo con error
     * @link https://postmarkapp.com/developer/user-guide/sending-email/sending-with-api
     */
    public function send($data, $header)
    {
        // agregar quien envía el correo
        $from = null;
        if (!empty($header['from'])) {
            if (is_array($header['from'])) {
                $from = $header['from']['name'].' <'.$header['from']['email'].'>';
            } else {
                $from = $header['from'];
            }
        }
        // agregar a quien responder el correo
        $replyTo = null;
        if (!empty($header['replyTo'])) {
            if (is_array($header['replyTo'])) {
                $replyTo = $header['replyTo']['name'].' <'.$header['replyTo']['email'].'>';
            } else {
                $replyTo = $header['replyTo'];
            }
        }
        // agregar destinatarios
        $to = $cc = $bcc = null;
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
                if ($dests) {
                    $$dest_type = implode(',', $dests);
                }
            }
        }
        // asignar asunto
        $subject = $header['subject'];
        // agregar mensaje
        $htmlBody = !empty($data['html']) ? $data['html'] : null;
        $textBody = !empty($data['text']) ? $data['text'] : null;
        // agregar adjuntos
        $attachments = null;
        if (!empty($data['attach'])) {
            $attachments = [];
            foreach ($data['attach'] as $file) {
                // leer desde archivo
                if (!empty($file['tmp_name'])) {
                    $attachments[] = \Postmark\Models\PostmarkAttachment::fromRawData(
                        file_get_contents($file['tmp_name']),
                        $file['name'],
                        $file['type']
                    );
                }
                // leer desde datos de una variable
                else if (!empty($file['data'])) {
                    $attachments[] = \Postmark\Models\PostmarkAttachment::fromRawData(
                        $file['data'],
                        $file['name'],
                        $file['type']
                    );
                }
            }
        }
        // otras opciones del mensaje
        $tag = null;
        $trackOpens = true;
        $headers = null;
        $trackLinks = null;
        $metadata = null;
        // enviar mensaje
        try {
            $result = $this->pm->sendEmail(
                $from, $to, $subject, $htmlBody, $textBody, $tag, $trackOpens,
                $replyTo, $cc, $bcc, $headers, $attachments, $trackLinks, $metadata
            );
            return true;
        } catch (\Postmark\Models\PostmarkException $e) {
            return [
                'type' => 'error',
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

}
