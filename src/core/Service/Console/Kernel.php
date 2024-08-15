<?php

/**
 * SowerPHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
* Debería haber recibido una copia de la Licencia Pública General
 * Affero de GNU junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace sowerphp\core;

use Symfony\Component\Console\Application;

/**
 * Kernel para la aplicación de consola y ejecutar sus comandos.
 */
class Service_Console_Kernel implements Interface_Service
{

    /**
     * Aplicación de consola.
     *
     * @var Application
     */
    protected $console;

    /**
     * Comandos del núcleo Console que son cargados por defecto.
     *
     * @var array
     */
    protected $defaultCoreCommands = [
        Console_Command_App_Check::class,
        Console_Command_App_Deploy::class,
        Console_Command_App_Run::class,
        Console_Command_App_Setup::class,
        Console_Command_Assets_Collect::class,
        Console_Command_Backup_Run::class,
        Console_Command_Cache_Clear::class,
        Console_Command_Cache_Warmup::class,
        Console_Command_Config_Cache::class,
        Console_Command_Config_List::class,
        Console_Command_Database_Seed::class,
        Console_Command_Jobs_Running::class,
        Console_Command_Jobs_Scheduled::class,
        Console_Command_Lint_Twig::class,
        Console_Command_Log_Clear::class,
        Console_Command_Log_Rotate::class,
        Console_Command_Migrations_Status::class,
        Console_Command_Migrations_Make::class,
        Console_Command_Migrations_Migrate::class,
        Console_Command_Migrations_Rollback::class,
        Console_Command_Router_List::class,
        Console_Command_Router_Match::class,
        Console_Command_Security_Check::class,
        Console_Command_Session_Clear::class,
        Console_Command_Translation_List::class,
        Console_Command_Translation_Update::class,
        Console_Command_User_Create::class,
    ];

    /**
     * Registra el servicio de Console kernel.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio de Console kernel.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->console = new Application();
        $this->console->setAutoExit(false);
        $this->loadCommands();
    }

    /**
     * Carga y agrega los comandos definidos a la aplicación de la consola.
     *
     * @return void
     */
    protected function loadCommands(): void
    {
        // Cargar comandos del núcleo.
        foreach ($this->defaultCoreCommands as $command) {
            $this->console->add(new $command());
        }

        // Cargar comandos de los servicios registrados en el contenedor.
        $this->loadCommandsFromServices();

        // Cargar comandos personalizados de la configuración.
        $this->loadCommandsFromConfig();
    }

    /**
     * Carga los comandos desde los servicios que implementan comandos.
     */
    protected function loadCommandsFromServices(): void
    {
        $container = app()->getContainer();
        $services = array_keys($container->getBindings());
        $loaded = [];
        foreach ($services as $key) {
            $service = $container->make($key);
            $serviceClass = get_class($service);
            if (
                $service instanceof Interface_Service
                && !isset($loaded[$serviceClass])
                && method_exists($service, 'getConsoleCommands')
            ) {
                $commands = $service->getConsoleCommands();
                foreach ($commands as $command) {
                    $this->console->add($command);
                }
                $loaded[$serviceClass] = true;
            }
        }
    }

    /**
     * Carga los comandos que están definidos en la configuración.
     */
    protected function loadCommandsFromConfig(): void
    {
        // TODO: implementar (o quizás autodescubrir).
    }

    /**
     * Finaliza el servicio de Console kernel.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Método que ejecuta el comando solicitado.
     *
     * @return int Resultado de la ejecución del comando.
     */
    public function handle(): int
    {
        $status = $this->console->run();
        $this->showStats();
        return $status;
    }

    /**
     * Método para mostrar estadísticas finales de la ejecución del comando.
     */
    protected function showStats(): void
    {
        /*// Obtener las estadísticas de la aplicación.
        $stats = app()->getStats();
        $time = $stats['time']['duration'];

        // Imprimir el tiempo que tomó la ejecución del comando.
        if ($time < 60) {
            echo 'Proceso ejecutado en '.num($time, 1).' segundos.'."\n";
        } elseif ($time < 3600) {
            echo 'Proceso ejecutado en '.num($time / 60, 1).' minutos.'."\n";
        } else {
            echo 'Proceso ejecutado en '.num($time / 3600, 1).' horas.'."\n";
        }*/
    }

    /**
     * Fallback.
     *
     * Symfony Console maneja las excepciones internamente y proporciona un
     * manejo de errores estándar. Por lo que este método jamás debería ser
     * ejecutado por la App.
     *
     * @param \Throwable $throwable
     * @return void
     */
    public function handleThrowable(\Throwable $throwable): void
    {
        echo get_class($throwable) , "\n";
        echo $throwable->getMessage() , "\n";
        echo $throwable->getTraceAsString();
        exit(1);
    }

    /**
     * Entrega la instancia de la aplicación de consola.
     *
     * @return Application
     */
    public function getConsole(): Application
    {
        return $this->console;
    }

}
