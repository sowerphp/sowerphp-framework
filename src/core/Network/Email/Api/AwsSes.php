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
 * Clase para enviar correo electrónico mediante AWS SES
 * Requiere:
 *   $ composer require aws/aws-sdk-php
 */
class Network_Email_Api_AwsSes
{

    protected $config = null; ///< Configuración para envíos usando AWS SES
    protected $ses; ///< Objeto de AWS SES para los envíos

    /**
     * Constructor de la clase
     * @param config Arreglo con la configuración de AWS SES
     */
    public function __construct($config)
    {
        // verificar soporte AWS SES
        if (!class_exists('\Aws\Ses\SesClient')) {
            throw new \sowerphp\core\Exception('No hay soporte para AWS SES');
        }
        // valores por defecto para conexión vía AWS SES
        $config = array_merge([
            'region'  => 'us-east-1',
            'version' => '2010-12-01',
            'profile' => 'default',
        ], $config);
        // si no están los campos mínimos necesarios error
        if (empty($config['version']) || empty($config['region']) || (empty($config['profile']) && empty($config['credentials']))) {
            throw new \sowerphp\core\Exception('Configuración del correo electrónico incompleta');
        }
        $this->config = [
            'version' => $config['version'],
            'region'  => $config['region'],
            'profile' => $config['profile'],
            'credentials' => $config['credentials'],
        ];
        if (!empty($this->config['credentials'])) {
            unset($this->config['profile']);
        }
        // crear objeto cliente AWS SES
        $this->ses = new \Aws\Ses\SesClient($this->config);
    }

    /**
     * Método que envía el correo
     * @param data Arrelgo con los datos que se enviarán (texto y adjuntos)
     * @param header Cabeceras del correo
     * @return array Arreglo con los estados de retorno por cada correo enviado
     */
    public function send($data, $header)
    {
        // si hay clase PHPMailer se usa, ya que así hay soporte para archivos
        // adjuntos
        if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
            $mail = Network_Email_Smtp_Phpmailer::createEmail($data, $header);
            if (!$mail->preSend()) {
                die($mail->ErrorInfo);
            }
            $email = [
                'RawMessage'=>[
                    'Data' => $mail->getSentMIMEMessage(),
                ]
            ];
        }
        // si no hay soporte para PHPMailer se crea el correo directo, pero no
        // permite el envío de archivos adjuntos :-(
        // para enviar archivos adjuntos de esta forma, se debe construir el
        // mensaje completo "a mano" y eso no se ha hecho (ver enlace de la
        // documentación del método). Esto debería corregirse en el futuro.
        else {
            $email = $this->createEmailData($data, $header);
        }
        // enviar mensaje
        try {
            if (isset($email['RawMessage'])) {
                $result = $this->ses->sendRawEmail($email);
            } else {
                $result = $this->ses->sendEmail($email);
            }
            return true;
        } catch (\Aws\Ses\AwsException $e) {
            return [
                'type' => 'error',
                'code' => $e->getCode(),
                'message' => $e->getAwsErrorMessage(),
                'full_message' => $e->getMessage(),
            ];
        } catch(\Aws\Ses\Exception\SesException $e) {
            return [
                'type' => 'error',
                'code' => $e->getCode(),
                'message' => $e->getAwsErrorMessage(),
                'full_message' => $e->getMessage(),
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'type' => 'error',
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        } catch (\Aws\Exception\CredentialsException $e) {
            return [
                'type' => 'error',
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Método que crea el arreglo con los datos para enviar el correo.
     * No agrega archivos adjuntos, aunque existan
     * @param data Arreglo con los datos que se enviarán (texto y adjuntos)
     * @param header Cabeceras del correo
     * @return Arreglo con el correo que se enviará
     * @link https://docs.aws.amazon.com/ses/latest/DeveloperGuide/send-using-sdk-php.html
     * @link https://stackoverflow.com/questions/45791673/how-to-send-file-as-attachment-using-aws-ses-latest-sdk-3-33-and-php7
     * @todo Crear mensaje "a mano" con archivos adjuntos para no depender de PHPMailer
     */
    private function createEmailData($data, $header)
    {
        $email = ['Destination'=>[], 'Message'=>[]];
        // agregar quien envía el correo
        if (!empty($header['from'])) {
            if (is_array($header['from'])) {
                $email['Source'] = $header['from']['name'].' <'.$header['from']['email'].'>';
            } else {
                $email['Source'] = $header['from'];
            }
        }
        // agregar a quien responder el correo
        if (!empty($header['replyTo'])) {
            if (is_array($header['replyTo'])) {
                $email['ReplyToAddresses'] = [$header['replyTo']['name'].' <'.$header['replyTo']['email'].'>'];
            } else {
                $email['ReplyToAddresses'] = [$header['replyTo']];
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
                $email['Destination'][ucfirst($dest_type).'Addresses'] = $dests;
            }
        }
        // asignar asunto
        $email['Message']['Subject'] = [
            //'Charset' => '',
            'Data' => $header['subject'],
        ];
        // agregar mensaje
        if (!empty($data['html']) || !empty($data['text'])) {
            if (!empty($data['html'])) {
                $email['Message']['Body']['Html'] = [
                    //'Charset' => '',
                    'Data' => $data['html'],
                ];
            }
            if (!empty($data['text'])) {
                $email['Message']['Body']['Text'] = [
                    //'Charset' => '',
                    'Data' => $data['text'],
                ];
            }
        }
        // entregar arreglo con los datos del email
        return $email;
    }

}
