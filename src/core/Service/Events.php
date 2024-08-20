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

use Illuminate\Events\Dispatcher as IlluminateEventsDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyEventsDispatcher;

/**
 * Servicio de eventos de la aplicación.
 */
class Service_Events extends IlluminateEventsDispatcher implements Interface_Service
{

    /**
     * Instancia del Dispatcher de Illuminate.
     *
     * @var IlluminateEventsDispatcher
     */
    protected $illuminateDispatcher;

    /**
     * Instancia del EventDispatcher de Symfony.
     *
     * @var SymfonyEventsDispatcher
     */
    protected $symfonyDispatcher;

    /**
     * Instancia servicio de configuración.
     *
     * @var Service_Config
     */
    protected $configService;

    /**
     * Contructor del servicio de eventos.
     *
     * @param App $app
     * @param Service_Config $configService
     */
    public function __construct(App $app, Service_Config $configService)
    {
        $this->configService = $configService;
        parent::__construct($app->getContainer());
    }

    /**
     * Registrar servicio de eventos.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializar servicio de eventos.
     *
     * @return void
     */
    public function boot(): void
    {
        // Cargar los listeners desde la configuración.
        $listeners = (array)$this->configService->get('events.listeners');
        foreach ($listeners as $event => $handlers) {
            foreach ($handlers as $handler) {
                $this->listen($event, $handler);
            }
        }
    }

    /**
     * Finalizar servicio de eventos.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Entrega el despachador de eventos de Illuminate.
     *
     * @return IlluminateEventsDispatcher
     */
    public function getIlluminateDispatcher(): IlluminateEventsDispatcher
    {
        if (!isset($this->illuminateDispatcher)) {
            $this->illuminateDispatcher = $this;
        }
        return $this->illuminateDispatcher;
    }

    /**
     * Entrega el despachador de eventos de Symfony.
     *
     * @return SymfonyEventsDispatcher
     */
    public function getSymfonyDispatcher(): SymfonyEventsDispatcher
    {
        if (!isset($this->symfonyDispatcher)) {
            $this->symfonyDispatcher = new SymfonyEventsDispatcher();
        }
        return $this->symfonyDispatcher;
    }

}
