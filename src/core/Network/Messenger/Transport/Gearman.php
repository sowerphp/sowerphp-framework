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
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Exception\TransportException;
use GearmanClient;
use GearmanWorker;

/**
 * Clase Network_Messenger_Transport_Gearman
 *
 * Esta clase implementa el transporte de mensajes utilizando Gearman.
 *
 * @link https://www.php.net/manual/en/book.gearman.php
 */
class Network_Messenger_Transport_Gearman implements TransportInterface
{
    /**
     * Cliente Gearman.
     *
     * @var GearmanClient
     */
    protected $client;

    /**
     * Trabajador Gearman.
     *
     * @var GearmanWorker
     */
    protected $worker;

    /**
     * Serializador para los mensajes.
     *
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * DSN (Data Source Name) para la conexión Gearman.
     *
     * @var string
     */
    protected $dsn;

    /**
     * Constructor de la clase.
     *
     * @param string $dsn El DSN para la conexión Gearman.
     * @param SerializerInterface $serializer El serializador para los mensajes.
     */
    public function __construct(string $dsn, SerializerInterface $serializer)
    {
        $this->dsn = $dsn;
        $this->serializer = $serializer;
        $this->client = new GearmanClient();
        $this->worker = new GearmanWorker();

        $host = parse_url($dsn, PHP_URL_HOST);
        $port = parse_url($dsn, PHP_URL_PORT);
        $this->client->addServer($host, $port);
        $this->worker->addServer($host, $port);
    }

    /**
     * Envía un mensaje a través del transporte Gearman.
     *
     * @param Envelope $envelope El mensaje a enviar.
     * @return Envelope El mensaje enviado.
     * @throws TransportException Si falla el envío del mensaje.
     */
    public function send(Envelope $envelope): Envelope
    {
        // Enviar mensaje al servidor Gearman.
        $functionName = 'handleMessage';
        $functionArgs = json_encode($this->serializer->encode($envelope));
        $jobHandle = $this->client->doBackground($functionName, $functionArgs);

        // Verificar si hubo algún error al enviar el trabajo a Gearman.
        if ($this->client->returnCode() != GEARMAN_SUCCESS) {
            throw new TransportException(__(
                'Falló el envío del mensaje a Gearman: %s',
                $this->client->error()
            ));
        }

        // Verificar el estado del trabajo usando el jobHandle.
        /*$jobState = $this->getJobState($jobHandle);
        if ($jobState->state == 'UNKNOWN') {
            throw new TransportException(__(
                'El trabajo no es conocido. Puede que la función no exista o no haya workers disponibles.'
            ));
        }
        if ($jobState->state == 'FAILURE') {
            throw new TransportException(__(
                'El trabajo falló al ser ejecutado. Puede que la función no exista, que no haya workers disponibles o que el worker no haya informado el estado del progreso del trabajo.'
            ));
        }*/

        // Agregar el JobHandleStamp al Envelope.
        $jobStamp = new Network_Messenger_Stamp_GearmanJobSubmitted(
            $jobHandle
        );
        $envelope = $envelope->with($jobStamp);

        // Entregar el Envelope del mensaje enviado a Gearman.
        return $envelope;
    }

    /**
     * Recibe mensajes desde el transporte Gearman.
     *
     * @return iterable Los mensajes recibidos.
     * @throws TransportException Si falla la recepción de mensajes.
     */
    public function get(): iterable
    {
        $this->worker->addFunction('handleMessage', function (\GearmanJob $job) {
            $data = json_decode($job->workload(), true);
            $envelope = $this->serializer->decode($data);

            yield $envelope;
        });

        // TODO: no es correcto dejar pegado acá el worker. Debe retornar.
        while ($this->worker->work()) {
            if ($this->worker->returnCode() != GEARMAN_SUCCESS) {
                throw new TransportException(__(
                    'Falló la recepción del mensaje desde Gearman: %s',
                    $this->worker->error()
                ));
            }
        }
    }

    /**
     * Confirma el procesamiento exitoso de un mensaje.
     *
     * @param Envelope $envelope El mensaje a confirmar.
     */
    public function ack(Envelope $envelope): void
    {
        // Implementa la lógica de confirmación si es necesario.
    }

    /**
     * Rechaza el procesamiento de un mensaje.
     *
     * @param Envelope $envelope El mensaje a rechazar.
     */
    public function reject(Envelope $envelope): void
    {
        // Implementa la lógica de rechazo si es necesario.
    }

    /**
     * Consulta el estado de un trabajo en Gearman.
     *
     * Los posibles estados del trabajo son:
     *
     *   - UNKNOWN: El trabajo no es conocido por el servidor Gearman. Esto
     *     significa que el trabajo no ha sido registrado o ya ha sido removido
     *     del servidor.
     *   - STARTED: El trabajo está siendo procesado por un worker (conocido y
     *     en ejecución).
     *   - PENDING: El trabajo es conocido, no está en ejecución y el progreso
     *     es menor que 100.
     *   - SUCCESS: El trabajo es conocido, no está en ejecución y el progreso
     *     es 100%.
     *   - FAILURE:
     *       - El progreso es null, lo que indica que el worker no ha enviado
     *         información de progreso (considerado fallido por diseño).
     *       - Por defecto, cualquier otro caso no cubierto anteriormente es
     *         considerado como fallo.
     *
     * @param string $jobHandle El identificador del trabajo en Gearman.
     * @return \StdClass El estado del trabajo.
     */
    /*public function getJobState(string $jobHandle): \StdClass
    {
        // Obtener estado del trabajo en Gearman.
        list($known, $running, $numerator, $denominator) =
            $this->client->jobStatus($jobHandle)
        ;
        $progress = ($denominator > 0)
            ? (int) round(($numerator / $denominator) * 100)
            : null
        ;

        // El trabajo no es conocido por el servidor Gearman. Esto generalmente
        // significa que el trabajo no ha sido registrado o ya ha sido
        // removido del servidor.
        // No puede ser PENDING, porque quizás ya fue removido.
        if (!$known) {
            return (object)['state' => 'UNKNOWN', 'progress' => $progress];
        }

        // Si el progreso es nulo, el trabajo es conocido pero no hay
        // información de progreso.
        // Esto es FAILURE solo porque es obligación del worker enviar la
        // información de progreso. Si el worker no la envía, el proceso se
        // asumirá fallido siempre.
        if ($progress === null) {
            return (object)['state' => 'FAILURE', 'progress' => $progress];
        }

        // El trabajo está siendo procesado por un worker.
        // Es un trabajo conocido y en ejecución.
        if ($running) {
            return (object)['state' => 'STARTED', 'progress' => $progress];
        }

        // Si el progreso es menor que 100, podría estar encolado o pendiente.
        if ($progress >= 0 && $progress < 100) {
            return (object)['state' => 'PENDING', 'progress' => $progress];
        }

        // El trabajo se completó.
        // Es un trabajo conocido, no en ejecución y con progreso de 100%.
        if ($progress == 100) {
            return (object)['state' => 'SUCCESS', 'progress' => $progress];
        }

        // Por defecto, asumimos que el trabajo ha fallado.
        return (object)['state' => 'FAILURE', 'progress' => $progress];
    }*/
}
