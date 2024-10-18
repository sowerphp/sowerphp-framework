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

use Symfony\Component\Messenger\Envelope;

/**
 * Servicio de trabajos en segundo plano (tareas).
 */
class Service_Jobs implements Interface_Service
{
    /**
     * Servicio de mensajes.
     *
     * @var Service_Messenger
     */
    protected $messengerService;

    /**
     * Constructor del servicio de trabajos en segundo plano (tareas).
     *
     * @param Service_Messenger $messengerService
     */
    public function __construct(Service_Messenger $messengerService)
    {
        $this->messengerService = $messengerService;
    }

    /**
     * Registra el servicio de trabajos en segundo plano (tareas).
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio de trabajos en segundo plano (tareas).
     *
     * @return void
     */
    public function boot(): void
    {
    }

    /**
     * Finaliza el servicio de trabajos en segundo plano (tareas).
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Enviar un trabajo a ejecución.
     *
     * Ejemplo:
     *
     *   $envelope = $this->executeCommand('config:list', ['key' => 'layers']);
     *
     * @param string $command
     * @param array $arguments
     * @param array $options
     * @return Envelope
     */
    public function executeCommand(
        string $command,
        array $arguments = [],
        array $options = []
    ): Envelope
    {
        $job = new Network_Messenger_Message_Job(
            $command,
            $arguments,
            $options
        );
        $envelope = $this->messengerService->send($job);

        return $envelope;
    }
}
