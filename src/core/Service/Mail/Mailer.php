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
use \Symfony\Component\Mailer\Transport;
use \Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Clase para el envío de correos electrónicos.
 */
class Service_Mail_Mailer implements Interface_Service
{

    /**
     * Servicio de configuración.
     *
     * @var Service_Config
     */
    protected $configService;

    /**
     * Listado de mailers inicializados.
     *
     * @var array
     */
    protected $mailers = [];

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
     * Obtiene un remitente de correo.
     *
     * @param string|null $name Nombre del remitente.
     * @param array|null $config Configuración del remitente.
     * @return \Symfony\Component\Mailer\Mailer
     */
    public function mailer(?string $name = null, ?array $config = []): Mailer
    {
        if (!isset($this->mailers[$name])) {
            $this->mailers[$name]['config'] = $this->config($name, $config);
            $this->mailers[$name]['transport'] = $this->transport(
                $name,
                $this->mailers[$name]['config']
            );
            $this->mailers[$name]['mailer'] = new Mailer(
                $this->mailers[$name]['transport']
            );
        }
        return $this->mailers[$name]['mailer'];
    }

    /**
     * Obtiene un transporte de correo.
     *
     * @param string|null $name Nombre del remitente (mailer) que define el
     * transporte que se utilizará
     * @param array|null $config Configuración del remitente.
     * @return \Symfony\Component\Mailer\Transport\TransportInterface
     */
    public function transport(?string $name = null, ?array $config = []): TransportInterface
    {
        $name = $name ?? $this->configService->get('mail.default') ?? 'smtp';
        if (!isset($this->mailers[$name]['transport'])) {
            $this->mailers[$name]['config'] = $this->config($name, $config);
            $this->mailers[$name]['transport'] = Transport::fromDsn(
                $this->mailers[$name]['config']['dsn']
            );
        }
        return $this->mailers[$name]['transport'];
    }

    /**
     * Obtiene (y normaliza) la configuración del mailer.
     *
     * @param string|null $name Nombre del mailer que se desea obtener.
     * @param array|null $config Configuración del remitente.
     * @return array Configuración normalizada del mailer.
     */
    public function config(?string $name = null, ?array $config = []): array
    {
        if (!empty($config)) {
            return $config;
        }
        $name = $name ?? $this->configService->get('mail.default') ?? 'smtp';
        if (!empty($this->mailers[$name]['config'])) {
            return $this->mailers[$name]['config'];
        }
        $config = array_merge([
            'name' => $name,
            'from' => $this->configService->get('mail.from'),
            'to' => $this->configService->get('mail.to'),
        ], $this->configService->get('mail.mailers.' . $name) ?? []);
        if (!isset($config['dsn'])) {
            $config['dsn'] = $this->generateDsn($config);
        }
        $config['endpoint'] = $this->generateEndpoint($config);
        return $config;
    }

    /**
     * Genera el DSN para Symfony Mailer.
     *
     * @param array $config Configuración del mailer.
     * @return string DSN.
     */
    protected function generateDsn(array $config): string
    {
        switch ($config['transport']) {
            case 'smtp':
                $dsn = sprintf(
                    'smtp://%s:%s@%s:%d',
                    urlencode($config['username']),
                    urlencode($config['password']),
                    $config['host'],
                    $config['port']
                );
                if (isset($config['validate_cert']) && !$config['validate_cert']) {
                    $dsn .= '?verify_peer=0';
                }
                return $dsn;
            case 'ses':
                return sprintf(
                    'ses+https://%s:%s@%s',
                    urlencode($config['key']),
                    urlencode($config['secret']),
                    $config['region']
                );
            case 'mailgun':
                return sprintf(
                    'mailgun+https://%s:%s@%s/%s',
                    urlencode($config['domain']),
                    urlencode($config['secret']),
                    $config['endpoint'],
                    'default'
                );
            case 'postmark':
                return sprintf(
                    'postmark+https://%s',
                    urlencode($config['token'])
                );
            default:
                throw new \InvalidArgumentException(__(
                    'Transporte no suportado: %s.',
                    $config['transport']
                ));
        }
    }

    /**
     * Genera el endpoint personalizado.
     *
     * @param array $config Configuración del mailer.
     * @return string Endpoint personalizado.
     */
    protected function generateEndpoint(array $config): string
    {
        $endpoint = '';
        if (isset($config['encryption'])) {
            $endpoint .= $config['encryption'] . '://';
        }
        $endpoint .= $config['host'];
        if (isset($config['port'])) {
            $endpoint .= ':' . $config['port'];
        }
        if (isset($config['validate_cert']) && !$config['validate_cert']) {
            $endpoint .= '/novalidate-cert';
        }
        return $endpoint;
    }

}
