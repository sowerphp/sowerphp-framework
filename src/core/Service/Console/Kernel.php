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
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace sowerphp\core;

class Service_Console_Kernel implements Interface_Service
{

    public function register()
    {
    }

    public function boot()
    {
    }

    /**
     * Método que ejecuta el comando solicitado.
     * @return int Resultado de la ejecución del comando.
     */
    public function handle(): int
    {
        global $argv;
        set_time_limit(0);
        if (empty($argv[1])) {
            echo 'SowerPHP shell: debe indicar un comando a ejecutar.' , "\n";
            return 1;
        }
        $command = ucfirst($argv[1]);
        $args = array_slice($argv, 2);
        return $this->dispatch($command, $args);
    }

    /**
     * Método que busca y ejecuta un comando.
     * @param string $command Comando a ejecutar.
     * @param array $args Argumentos que se pasarán al comando.
     * @return int Resultado de la ejecución del comando.
     */
    private function dispatch(string $command, array $args): int
    {
        // Crear objeto
        $dot = strrpos($command, '.');
        if ($dot) {
            $module = substr($command, 0, $dot);
            $class = 'Shell_Command_' . substr($command, $dot + 1);
            if ($module) {
                $class = str_replace('.', '\\', $module) . '\\' . $class;
            }
            $class = '\\sowerphp\\magicload\\' . $class;
        } else {
            $class = 'Shell_Command_' . $command;
        }
        if (!class_exists($class)) {
            echo 'SowerPHP shell: ',$command,': no se encontró la orden (',$class,').',"\n";
            return 1;
        }
        $shell = new $class();
        // revisar posibles flags especiales
        $argc = count($args);
        for ($i=0; $i<$argc; $i++) {
            // poner modo verbose que corresponda (de 1 a 5)
            if (preg_match('/^\-v+$/', $args[$i])) {
                $shell->verbose = strlen($args[$i]) - 1;
                unset($args[$i]);
            }
            // mostrar ayuda (y no ejecutar comando)
            else if ($args[$i] == '-h') {
                $method = new \ReflectionMethod($shell, 'main');
                echo '   Modo de uso: ',$command,' ';
                foreach ($method->getParameters() as &$p) {
                    echo ($p->isOptional() ? '['.$p->name.' = '.$p->getDefaultValue().']' : $p->name),' ';
                }
                echo "\n";
                exit;
            }
        }
        // validar parámetros que se están pasando
        $method = new \ReflectionMethod($shell, 'main');
        if (count($args) < $method->getNumberOfRequiredParameters()) {
            echo 'SowerPHP shell: ',$command,': requiere al menos ',
                $method->getNumberOfRequiredParameters(),' parámetro(s)',"\n";
            echo '   Modo de uso: ',$command,' ';
            foreach($method->getParameters() as &$p) {
                echo ($p->isOptional() ? '['.$p->name.' = '.$p->getDefaultValue().']' : $p->name),' ';
            }
            echo "\n";
            return 1;
        }
        // validar que el proceso pueda ser ejecutado
        if (!$shell->canHaveMultipleInstances()) {
            // obtener procesos que tienen "/shell.php " en su comando
            $procesos = $this->getCurrentProcesses('/shell.php ');
            if (is_numeric($procesos)) {
                echo 'SowerPHP shell: no fue posible obtener los procesos en ejecución.' , "\n";
                return $procesos;
            }
            // buscar otros procesos que existan en ejecución con los mismos argumentos
            global $argv;
            $cmd_actual = implode(' ', $argv);
            $cmd_actual = trim(mb_substr($cmd_actual, strpos($cmd_actual, '/shell.php ') + 1));
            $pid_actual = getmypid();
            $ppid_actual = posix_getppid();
            $otros_procesos = [];
            foreach ($procesos as $p) {
                $cmd = $p['cmd'];
                $cmd = trim(mb_substr($cmd, strpos($cmd, '/shell.php ') + 1));
                if (!in_array($p['pid'], [$pid_actual, $ppid_actual]) && $cmd == $cmd_actual) {
                    $otros_procesos[] = $p;
                }
            }
            unset($procesos);
            // mostrar error en caso de existir otros procesos en ejecución
            if (!empty($otros_procesos)) {
                echo 'SowerPHP shell: no es posible ejecutar el comando, existen otras instancias en',"\n";
                echo 'ejecución que coinciden con: ',$cmd_actual,"\n";
                foreach ($otros_procesos as $p) {
                    echo ' - PID ',$p['pid'],' ejecutándose desde ',$p['start_time'],"\n";
                }
                return 1;
            }
        }
        // invocar main
        $return = $method->invokeArgs($shell, $args);
        // Retornar estado
        return $return ? $return : 0;
    }

    /**
     * Método que entrega el listado de procesos en ejecución en el sistema
     * con sus argumentos y otros datos
     */
    private function getCurrentProcesses(?string $filter = null)
    {
        $cols = [
            'pid',
            'start_time',
            'etimes',
            'user',
            'stat',
            'psr',
            'sgi_p',
            'cputimes',
            'pcpu',
            'pmem',
            'rsz',
            'vsz',
            'cmd',
        ];
        $cmd = 'ps -eo '.implode(',', $cols).' --sort -etimes --no-headers';
        if ($filter !== null) {
            $cmd .= ' | grep "'.$filter.'"';
        }
        exec($cmd, $lines, $rc);
        if ($rc) {
            return $rc;
        }
        $processes = [];
        foreach ($lines as $line) {
            $line = preg_replace('!\s+!', ' ', trim($line));
            $processes[] = array_combine(
                $cols,
                explode(' ', $line, 13),
            );
        }
        return $processes;
    }

    public function handleThrowable(\Throwable $throwable): void
    {
        $stdout = new Shell_Output('php://stdout');
        $stdout->write("\n".'<error>'.get_class($throwable).':</error>', 2);
        $stdout->write('<error>'.$throwable->getMessage().'</error>', 2);
        $stdout->write('<error>'.$throwable->getTraceAsString().'</error>', 2);
        exit($throwable->getCode());
    }

}
