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

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Servicio de logging utilizando Monolog.
 */
class Service_Log implements Interface_Service, LoggerInterface
{
    // Usar LoggerTrait para implementar métodos básicos de LoggerInterface.
    use LoggerTrait;

    /**
     * El objeto Logger utilizado para registrar mensajes de log.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Servicio de almacenamiento.
     *
     * @var Service_Storage
     */
    protected $storageService;

    /**
     * Constructor.
     */
    public function __construct(Service_Storage $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Registra el servicio de logs.
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio de logs.
     */
    public function boot(): void
    {
        $channelName = 'app';

        // Corroborar que exista el directorio para logs.
        $diskName = 'local';
        $disk = $this->storageService->disk($diskName);
        if (!$disk->directoryExists('/logs')) {
            $disk->createDirectory('/logs');
        }
        $logsFile = $this->storageService->getFullPath(
            '/logs/' . $channelName . '.log',
            $diskName
        );

        // Crear logger y asignar handler stream.
        $this->logger = new Logger($channelName);
        $this->logger->pushHandler(new StreamHandler($logsFile, Logger::DEBUG));
    }

    /**
     * Finaliza el servicio de logs.
     */
    public function terminate(): void
    {
    }

    /**
     * Registra mensajes con un nivel arbitrario.
     *
     * @param mixed $level El nivel del log (puede ser una cadena o una
     * constante de Monolog).
     * @param string $message El mensaje a registrar.
     * @param array $context Contexto adicional para el mensaje.
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    /**
     * Cualquier método que no esté definido en el servicio será llamado en el
     * logger de Monolog.
     */
    public function __call($method, $parameters)
    {
        try {
            return call_user_func_array([$this->logger, $method], $parameters);
        } catch (\Exception $e) {
            throw new \Exception(__(
                'Error al ejecutar método %s del logger: %s.',
                $method,
                $e->getMessage()
            ));
        }
    }
}
