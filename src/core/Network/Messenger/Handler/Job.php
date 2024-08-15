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

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * Handler para ejecutar trabajos de consola a través de Symfony Messenger.
 */
class Network_Messenger_Handler_Job implements MessageHandlerInterface
{

    /**
     * Servicio de kernel para la ejecución de comandos de la consola.
     *
     * @var Service_Console_Kernel
     */
    protected $kernelService;

    /**
     * La aplicación de consola de Symfony.
     *
     * @var Application
     */
    protected $console;

    /**
     * Método invocado cuando se recibe un mensaje de trabajo.
     *
     * @param Network_Messenger_Message_Job $job El mensaje de trabajo.
     * @return mixed
     */
    public function __invoke(Network_Messenger_Message_Job $job)
    {
        $command = $job->getCommand();
        $arguments = $job->getArguments();
        $options = $job->getOptions();
        return $this->executeCommand($command, $arguments, $options);
    }

    /**
     * Ejecuta un comando de consola con los argumentos proporcionados.
     *
     * @param string $command El nombre del comando a ejecutar.
     * @param array $arguments Los argumentos del comando.
     * @param array $options Las opciones del comando.
     * @return void
     */
    protected function executeCommand(string $command, array $arguments, array $options)
    {
        // Obtener la consola.
        $console = $this->getConsole();

        // Agregar el comando y sus argumentos al input.
        $inputArray = array_merge(['command' => $command], $arguments, $options);
        $input = new ArrayInput($inputArray);

        // Crear la salida como un buffer para poder recuperarla.
        $output = new BufferedOutput();

        // Ejecutar el comando y entregar resultado.
        try {
            $status = $console->run($input, $output);
            return (object)[
                'status' => $status,
                'output' => $output->fetch(),
            ];
        } catch (\Exception $e) {
            $error = __(
                'Error ejecutando el comando %s: %s',
                $command,
                $e->getMessage()
            );
            return (object)[
                'status' => 1,
                'output' => $error,
            ];
        }
    }

    /**
     * Obtiene el kernel para aplicaciones de consola.
     *
     * @return Service_Console_Kernel
     */
    protected function getKernel(): Service_Console_Kernel
    {
        if (!isset($this->kernelService)) {
            app()->registerService(
                Service_Console_Kernel::class,
                Service_Console_Kernel::class
            );
            $this->kernelService = app()->getService(
                Service_Console_Kernel::class
            );
            $this->kernelService->register();
            $this->kernelService->boot();
        }
        return $this->kernelService;
    }

    /**
     * Obtiene la aplicación de consola.
     *
     * @return Application
     */
    protected function getConsole(): Application
    {
        if (!isset($this->console)) {
            $this->console = $this->getKernel()->getConsole();
        }
        return $this->console;
    }

}
