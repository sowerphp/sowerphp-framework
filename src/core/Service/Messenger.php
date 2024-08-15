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

use Illuminate\Container\Container;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Transport\Sync\SyncTransport;
use Symfony\Component\Messenger\Bridge\Redis\Transport\Connection as RedisConnection;
use Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransport;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\RoutableMessageBus;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\Command\StopWorkersCommand;
use Symfony\Component\Messenger\EventListener\StopWorkerOnRestartSignalListener;

/**
 * Servicio que maneja los mensajes de la aplicación.
 */
class Service_Messenger implements Interface_Service
{

    /**
     * Contenedor para los transportadores de mensajes.
     *
     * @var Container
     */
    protected $container;

    /**
     * El serializador para los transportadores de mensajes.
     *
     * @var PhpSerializer
     */
    protected $serializer;

    /**
     * El bus de mensajes de Symfony Messenger.
     *
     * @var MessageBus
     */
    protected $bus;

    /**
     * Servicio de configuración.
     *
     * @var Service_Config
     */
    protected $configService;

    /**
     * Constructor del servicio de mensajes.
     *
     * @param Service_Config $configService
     */
    public function __construct(Service_Config $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Registra el servicio de mensajes.
     *
     * @return void
     */
    public function register(): void
    {
        $this->container = new Container();
        $this->serializer = new PhpSerializer();
    }

    /**
     * Inicializa el servicio de mensajes.
     *
     * @return void
     */
    public function boot(): void
    {
        // Crear el bus de mensajes con los middlewares.
        $this->bus = new MessageBus([
            $this->getSendMessageMiddleware(),
            $this->getHandleMessageMiddleware(),
        ]);
    }

    /**
     * Carga los senders desde la configuración y crea el SendersLocator con
     * el SendMessageMiddleware.
     *
     * @return SendMessageMiddleware
     */
    protected function getSendMessageMiddleware(): SendMessageMiddleware
    {
        // Crear el mapa con los mensajes y sus senders (transportes).
        $defaultTransportName = $this->getDefaultTransportName();
        $sendersMap = [];
        $routingConfig = (array) $this->configService->get('messenger.routing');
        foreach ($routingConfig as $message => $messageTransports) {
            if (!$messageTransports) {
                $messageTransports = [$defaultTransportName];
            }
            $sendersMap[$message] = [];
            foreach($messageTransports as $messageTransport) {
                $key = 'messenger.transport.' . $messageTransport;
                $sendersMap[$message][] = $key;
            }
        }

        // Registrar los transportes en el contenedor.
        $transports = (array) $this->configService->get('messenger.transports');
        foreach ($transports as $transport => $config) {
            $key = 'messenger.transport.' . $transport;
            $this->container->instance($key, $this->transport($transport));
        }

        // Crear el middleware del localizador de remitentes.
        return new SendMessageMiddleware(
            new SendersLocator($sendersMap, $this->container)
        );
    }

    /**
     * Carga los handlers desde la configuración y crea el HandlersLocator con
     * el HandleMessageMiddleware.
     *
     * @return HandleMessageMiddleware
     */
    protected function getHandleMessageMiddleware(): HandleMessageMiddleware
    {
        $handlers = [];
        $handlersConfig = (array) $this->configService->get('messenger.handlers');
        foreach ($handlersConfig as $message => $messageHandlers) {
            $handlers[$message] = [];
            foreach($messageHandlers as $messageHandler) {
                $handlers[$message][] = new $messageHandler();
            }
        }
        return new HandleMessageMiddleware(
            new HandlersLocator($handlers)
        );
    }

    /**
     * Finaliza el servicio de mensajes.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Obtiene un transporte para los mensajes.
     *
     * @param string|null $name Nombre del transporte.
     */
    public function transport(?string $name = null): TransportInterface
    {
        $name = $name ?? $this->getDefaultTransportName();
        $key = 'messenger.transport.' . $name;
        if (!isset($this->container[$key])) {
            $this->container->instance($key, $this->loadTransport($name));
        }
        return $this->container[$key];
    }

    /**
     * Entrega el nombre del transporte de mensajes por defecto.
     *
     * @return string
     */
    protected function getDefaultTransportName(): string
    {
        return $this->configService->get('messenger.default') ?? 'sync';
    }

    /**
     * Crea y retorna un transportador de mensajes basado en el nombre
     * proporcionado.
     *
     * @param string $name El nombre del transportador a crear.
     * @return TransportInterface
     * @throws \InvalidArgumentException Si el transportador no está soportado.
     */
    protected function loadTransport(string $name): TransportInterface
    {
        $config = $this->configService->get('messenger.transports.' . $name);
        $dsn = $config['dsn'] ?? null;

        switch ($name) {
            case 'sync':
                $syncBus = $this->bus = new MessageBus([
                    new SendMessageMiddleware(
                        new SendersLocator([], $this->container)
                    ),
                    $this->getHandleMessageMiddleware(),
                ]);
                return new SyncTransport($syncBus);
            case 'redis':
                $redisConnection = RedisConnection::fromDsn($dsn);
                return new RedisTransport($redisConnection, $this->serializer);
            case 'gearman':
                return new Network_Messenger_Transport_Gearman($dsn, $this->serializer);
            default:
                throw new \InvalidArgumentException(__(
                    'Transportador %s no soportado en los mensajes',
                    $name
                ));
        }
    }

    /**
     * Envía un mensaje para ser procesado.
     *
     * Este método utiliza el bus de mensajes para el envío. Esta es la opción
     * recomendada para enviar mensajes.
     *
     * @param object $message
     * @return Envelope
     */
    public function send(object $message): Envelope
    {
        return $this->bus->dispatch(new Envelope($message));
    }

    /**
     * Envía un mensaje usando directamente el transporte (sin usar el bus).
     *
     * Este método no utiliza el bus de mensajes para el envío. Hace un envío
     * directo a través del transporte, saltándose los middleware y otras
     * funcionalidades usadas al enviar mediante el bus de mensajes.
     *
     * @param object $message
     * @param string|null $transportName
     * @return Envelope
     */
    public function sendWithoutBus(object $message, ?string $transportName = null): Envelope
    {
        $transport = $this->transport($transportName);
        return $transport->send(new Envelope($message));
    }

    /**
     * Entrega los comandos que este servicio provee a la consola.
     *
     * @return array
     */
    public function getConsoleCommands(): array
    {
        // Crear $routableBus.
        $container = new Container();
        $container->instance(MessageBusInterface::class, $this->bus);
        $routableBus = new RoutableMessageBus($container);

        // Crear $receiverLocator.
        $receiverLocator = new Container();
        $transports = (array) $this->configService->get('messenger.transports');
        foreach ($transports as $name => $config) {
            $transport = $this->transport($name);
            if (!$transport instanceof ReceiverInterface) {
                continue;
            }
            $receiverLocator->instance($name, $transport);
        }

        // Obtener la instancia del EventDispatcher de Symfony.
        $eventDispatcher = app('events')->getSymfonyDispatcher();

        // Crear la instancia del comando para recepción de mensajes.
        $consumeMessagesCommand = new ConsumeMessagesCommand(
            $routableBus,
            $receiverLocator,
            $eventDispatcher,
            null, // Logger, si es necesario.
            [],   // Receiver names.
            null, // Listener de reinicio de servicios, no se usa.
            ['default'] // Bus IDs.
        );

        // Crear la instancia del comando para detener los workers.
        $cacheItemPool = cache()->getCacheItemPool();
        $stopWorkersCommand = new StopWorkersCommand($cacheItemPool);

        // Añadir el listener al EventDispatcher.
        $stopWorkerListener = new StopWorkerOnRestartSignalListener($cacheItemPool);
        $eventDispatcher->addSubscriber($stopWorkerListener);

        // Entregar los comandos del servicio.
        return [$consumeMessagesCommand, $stopWorkersCommand];
    }

}
