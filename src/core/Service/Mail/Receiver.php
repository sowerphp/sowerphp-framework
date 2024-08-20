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

/**
 * Clase para la recepción de correos.
 */
class Service_Mail_Receiver implements Interface_Service
{

    /**
     * Servicio de configuración.
     *
     * @var Service_Config
     */
    protected $configService;

    /**
     * Listado de receivers inicializados.
     *
     * @var array
     */
    protected $receivers = [];

    /**
     * Contructor del servicio.
     *
     * @param Service_Config $configService
     */
    public function __construct(Service_Config $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Registra el servicio de correo.
     *
     * @return void
     */
    public function register(): void
    {
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
     * Obtiene un receptor de correo.
     *
     * @param string|null $name Nombre del receptor.
     * @param array|null $config Configuración del receptor.
     * @return Network_Mail_Mailbox
     */
    public function receiver(
        ?string $name = null,
        ?array $config = []
    ): Network_Mail_Mailbox
    {
        if (!isset($this->receivers[$name])) {
            $this->receivers[$name]['config'] = $this->config($name, $config);
            $this->receivers[$name]['receiver'] = new Network_Mail_Mailbox(
                $this->receivers[$name]['config']['dsn'],
                $this->receivers[$name]['config']['username'],
                $this->receivers[$name]['config']['password'],
                $this->receivers[$name]['config']['attachments_dir']
            );
        }
        return $this->receivers[$name]['receiver'];
    }

    /**
     * Obtiene (y normaliza) la configuración del receiver.
     *
     * @param string|null $name Nombre del receiver que se desea obtener.
     * @return array Configuración normalizada del receiver.
     */
    protected function config(?string $name = null, ?array $config = []): array
    {
        if (!empty($config)) {
            return $config;
        }
        $name = $name ?? $this->configService->get('mail.default_receiver') ?? 'imap';
        if (!empty($this->receivers[$name]['config'])) {
            return $this->receivers[$name]['config'];
        }
        $config = array_merge([
            'name' => $name,
        ], $this->configService->get('mail.receivers.' . $name) ?? []);
        if (!isset($config['dsn'])) {
            $config['dsn'] = $this->generateDsn($config);
        }
        $config['endpoint'] = $this->generateEndpoint($config);
        return $config;
    }

    /**
     * Genera el DSN para el receptor.
     *
     * @param array $config Configuración del receptor.
     * @return string DSN.
     */
    protected function generateDsn(array $config): string
    {
        return sprintf(
            '{%s:%d/%s%s}%s',
            $config['host'],
            $config['port'],
            $config['transport'],
            ($config['encryption'] === 'ssl' ? '/ssl' : '') .
            (isset($config['validate_cert']) && !$config['validate_cert'] ? '/novalidate-cert' : ''),
            $config['mailbox']
        );
    }

    /**
     * Genera el endpoint personalizado.
     *
     * @param array $config Configuración del receiver.
     * @return string Endpoint personalizado.
     */
    protected function generateEndpoint(array $config): string
    {
        return $this->generateDsn($config);
    }

}
