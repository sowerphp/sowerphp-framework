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

use \Symfony\Component\Mailer\Mailer;
use \Symfony\Component\Mailer\Envelope;
use \Symfony\Component\Mime\RawMessage;
use \Symfony\Component\Mime\Address;

/**
 * Servicio de correo.
 *
 * Gestiona los correos electrónicos. Tanto el envío mediante mailer() y la
 * recepción mediante receiver().
 */
class Service_Mail implements Interface_Service
{

    /**
     * Instancia de la aplicación
     *
     * @var App
     */
    protected $app;

    /**
     * Servicio de envío de correo (mailer).
     *
     * @var Service_Mail_Mailer
     */
    protected $mailerService;

    /**
     * Servicio de recepción de correo (receiver).
     *
     * @var Service_Mail_Receiver
     */
    protected $receiverService;

    /**
     * Contructor del servicio.
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Registra el servicio de correo.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->registerService('mailer', Service_Mail_Mailer::class);
        $this->mailerService = $this->app->getService('mailer');
        $this->app->registerService('mail_receiver', Service_Mail_Receiver::class);
        $this->receiverService = $this->app->getService('mail_receiver');
    }

    /**
     * Inicializa el servicio de correo.
     *
     * @return void
     */
    public function boot(): void
    {
    }

    /**
     * Finaliza el servicio de correo.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Obtiene un remitente de correo.
     *
     * @param string|null $name Nombre del remitente.
     * @return Symfony\Component\Mailer\Mailer
     */
    public function mailer(?string $name = null): Mailer
    {
        return $this->mailerService->mailer($name);
    }

    /**
     * Obtiene un receptor de correo.
     *
     * @param string|null $name Nombre del receptor.
     */
    public function receiver(?string $name = null)
    {
        return $this->receiverService->receiver($name);
    }

    /**
     * Entrega el remitente por defecto del servicio de correo electrónico.
     *
     * @param string|null $name Nombre del mailer asociado al remitente.
     * @return Address
     */
    public function getDefaultSender(?string $name = null): Address
    {
        $config = $this->mailerService->config($name);
        $fromAddress = $config['from']['address'];
        $fromName = $config['from']['name'] ?? $fromAddress;
        return new Address($fromAddress, $fromName);
    }

    /**
     * Entrega el destinatario por defecto del servicio de correo electrónico.
     *
     * @param string|null $name Nombre del mailer asociado al destinatario.
     * @return Address
     */
    public function getDefaultRecipient(?string $name = null): Address
    {
        $config = $this->mailerService->config($name);
        $toAddress = $config['to']['address'];
        $toName = $config['to']['name'] ?? $toAddress;
        return new Address($toAddress, $toName);
    }

    /**
     * Envía el correo electrónico.
     *
     * Esta envoltura del método Mailer::send() agrega de manera automática un
     * $envelope al envío si no se especificó uno. Si se quiere evitar este
     * comportamiento usar Mailer::send().
     *
     * @param RawMessage $message
     * @param Envelope|null $envelope
     * @param string|null $name Nombre del mailer que se usará para el envío.
     * @return void
     */
    public function send(
        RawMessage $message,
        ?Envelope $envelope = null,
        ?string $name = null
    ): void
    {
        // Agregar $envelope si no se especificó uno con los datos por defecto
        // de la configuración del correo electrónico.
        if ($envelope === null) {
            $sender = $this->getDefaultSender($name);
            $recipient = $this->getDefaultRecipient($name);
            $envelope = new Envelope($sender, [$recipient]);
        }

        // Enviar mensaje del correo electrónico en el $envelope.
        $this->mailer($name)->send($message, $envelope);
    }

}
