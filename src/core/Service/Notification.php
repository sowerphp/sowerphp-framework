<?php

/**
 * SowerPHP: Simple and Open Web Ecosystem Reimagined for PHP.
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

use Symfony\Component\Notifier\Notifier;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Part\DataPart;

class Service_Notification implements Interface_Service
{
    /**
     * @var NotifierInterface
     */
    protected $notifier;

    /**
     * Servicio de correo electrónico.
     *
     * @var Service_Mail
     */
    protected $mailService;

    /**
     * Constructor del servicio de notificaciones.
     *
     * @param Service_Mail $mailService
     */
    public function __construct(Service_Mail $mailService)
    {
        $this->mailService = $mailService;
    }

    /**
     * Registra el servicio de notificaciones.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio de notificaciones.
     *
     * @return void
     */
    public function boot(): void
    {
        $channels = [];
        $this->notifier = new Notifier($channels);
    }

    /**
     * Finaliza el servicio de notificaciones.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Envía una notificación a un destinatario por correo electrónico.
     *
     * @param string $subject El asunto del correo.
     * @param string|array $content Un string con el contenido del correo o un
     * arreglo con índices: `text` y `html`.
     * @param string|array $to Un string con el correo electrónico del
     * destinatario o un arreglo con índices: `address` y `name`.
     * @param array $attachments Arreglo con los archivos adjuntos del correo.
     * @param string|array $from Un string con el correo electrónico del
     * remitente o un arreglo con índices: `address` y `name`.
     * @return void
     */
    public function sendEmail(
        string $subject,
        $content,
        $to,
        array $attachments = [],
        $from = null
    ): void {
        // Determinar el remitente.
        $sender = null;
        if ($from !== null) {
            $fromAddress = is_string($from)
                ? $from
                : ($from['address'] ?? null)
            ;
            if (!empty($fromAddress)) {
                $fromName = is_string($from)
                    ? $from
                    : ($from['name'] ?? $fromAddress)
                ;
                $sender = new Address($fromAddress, $fromName);
            }
        }
        $sender = $sender ?? $this->mailService->getDefaultSender();

        // Determinar destinatarios.
        $recipients = [];
        if (is_array($to)) {
            if (!isset($to[0])) {
                $to = [$to];
            }
            foreach ($to as $t) {
                $toAddress = is_string($t)
                    ? $t
                    : ($t['address'] ?? null)
                ;
                if (!empty($toAddress)) {
                    $toName = is_string($t)
                        ? $t
                        : ($t['name'] ?? $toAddress)
                    ;
                    $recipients[] = new Address($toAddress, $toName);
                }
            }
        } else {
            $recipients[] = new Address($to, $to);
        }

        // Crear el Envelope con el remitente y destinatario.
        $envelope = new Envelope($sender, $recipients);

        // Determinar contenido.
        $text = $content['text'] ?? $content;
        $html = $content['html'] ?? str_replace("\n", '<br/>', $text);

        // Crear el mensaje de correo electrónico.
        $emailMessage = (new Email())
            ->from($sender)
            ->subject($subject)
            ->text($text)
            ->html($html)
            ->priority(Email::PRIORITY_HIGH)
        ;

        // Añadir los destinatarios.
        foreach ($recipients as $recipient) {
            $emailMessage->addTo($recipient);
        }

        // Añadir archivos adjuntos si los hay.
        foreach ($attachments as $attachment) {
            // Si el archivo adjunto es un path válido, lo añadimos al correo.
            if (is_string($attachment) && file_exists($attachment)) {
                $emailMessage->attachFromPath($attachment);
            }

            // Si el archivo adjunto es un arreglo viene en el formato $_FILES.
            else if (is_array($attachment)) {
                // Determinar los datos del archivo.
                $filedata = $attachment['data'] ?? null;
                if ($filedata === null) {
                    $filepath = $attachment['tmp_name']
                        ?? $attachment['name']
                        ?? null
                    ;
                    if ($filepath !== null && file_exists($filepath)) {
                        $filedata = file_get_contents($filepath);
                    }
                }
                if (empty($filedata)) {
                    continue;
                }

                // Determinar el nombre del archivo.
                $filename = basename(
                    $attachment['name']
                        ?? $attachment['tmp_name']
                        ?? null
                );
                if (empty($filename)) {
                    continue;
                }

                // Adjuntar el archivo.
                $emailMessage->attach(
                    $filedata,
                    $filename,
                    $attachment['type'] ?? 'application/octet-stream'
                );
            }

            // Si el archivo adjunto es una instancia de DataPart, lo añadimos
            // directamente.
            else if ($attachment instanceof DataPart) {
                $emailMessage->attachPart($attachment);
            }
        }

        // Enviar el mensaje utilizando tu método de envío personalizado.
        $this->mailService->send($emailMessage, $envelope);
    }
}
