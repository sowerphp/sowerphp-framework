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

use Illuminate\Mail\MailManager;
use Illuminate\Container\Container;
use Illuminate\Mail\Mailer;
use Illuminate\Contracts\Mail\Mailable;

/**
 * Servicio de correo.
 *
 * Gestiona el envío de correos electrónicos, proporcionando métodos
 * para obtener y crear instancias de mailers utilizando Illuminate Mail.
 */
class Service_Mail implements Interface_Service
{

    /**
     * Servicio de configuración.
     *
     * @var Service_Config
     */
    protected $configService;

    /**
     * Instancia de MailManager.
     *
     * @var MailManager
     */
    protected $mailManager;

    /**
     * Constructor de la clase.
     *
     * @param Service_Config $configService Servicio de configuración.
     */
    public function __construct(Service_Config $configService)
    {
        $this->configService = $configService;

        // Crear un contenedor de Illuminate
        $container = new Container();

        // Configurar el contenedor con la configuración de correo
        $container['config'] = [
            'mail.default' => $this->configService->get('mail.default'),
            'mail.mailers' => $this->configService->get('mail.mailers'),
            'mail.from' => $this->configService->get('mail.from'),
        ];

        // Crear una instancia de MailManager
        $this->mailManager = new MailManager($container);

        // Configurar el resolver para usar CustomMailer para diferentes transportes
        $transports = ['smtp', 'ses', 'mailgun', 'postmark', 'sendmail'];
        foreach ($transports as $transport) {
            $this->mailManager->extend($transport, function ($config) use ($container) {
                return new CustomMailer($container['config']->get('mail.mailers.' . $config['transport']), $container);
            });
        }
    }

    /**
     * Registra el servicio de correo.
     *
     * @return void
     */
    public function register(): void
    {
        // Código de registro del servicio.
    }

    /**
     * Inicializa el servicio de correo.
     *
     * @return void
     */
    public function boot(): void
    {
        // Código de inicialización del servicio.
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
     * @return CustomMailer
     */
    public function mailer(?string $name = null): CustomMailer
    {
        return $this->mailManager->mailer($name ?? $this->mailManager->getDefaultDriver());
    }

}

class CustomMailer extends Mailer
{

    /**
     * Constructor de la clase.
     *
     * @param array $config Configuración del remitente.
     * @param Container $container
     */
    public function __construct(array $config, Container $container)
    {
        parent::__construct($container, $container['view'], $config['name']);
        $this->setQueue($container['queue']);
        $this->alwaysFrom($config['from']['address'], $config['from']['name']);
    }

    /**
     * Método personalizado sendMail.
     *
     * @param Mailable $mailable
     * @return void
     */
    public function sendMail(Mailable $mailable): void
    {
        // Implementación del método sendMail
        $this->send($mailable);
    }

    // Añadir otros métodos personalizados si es necesario

}
