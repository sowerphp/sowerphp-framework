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

/**
 * Clase para despachar una Shell
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-04-25
 */
class Shell_Exec
{

    /**
     * Método que ejecuta el comando solicitado
     * @param argv Argumentos pasados al script (incluye comando)
     * @return Resultado de la ejecición del comando
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-09-17
     */
    public static function run ($argv)
    {
        // cambiar tiempo de ejecución a infinito
        set_time_limit(0);
        // Determinar que ejecutar
        if (!empty($argv[1])) {
            $command = ucfirst($argv[1]);
            $args = array_slice($argv, 2);
        } else {
            $command = null;
            $args = array_slice($argv, 1);
        }
        // Despachar shell
        return self::dispatch($command, $args);
    }

    /**
     * Método que ejecuta un comando
     * @param command Comando a ejecutar
     * @param args Argumentos que se pasarán al comando
     * @return Resultado de la ejecución del comando
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2021-01-15
     */
    private static function dispatch ($command, $args)
    {
        // Si el comando fue vacío se retorna estado de error
        if (empty($command)) {
            echo 'SowerPHP shell: debe indicar una orden a ejecutar',"\n";
            return 1;
        }
        // Crear objeto
        $dot = strrpos($command, '.');
        if ($dot) {
            $module = substr($command, 0, $dot);
            $command_real = substr($command, $dot+1);
            $class = \sowerphp\core\App::findClass('Shell_Command_'.$command_real, $module);
        } else {
            $class = \sowerphp\core\App::findClass('Shell_Command_'.$command);
        }
        if (!class_exists($class)) {
            echo 'SowerPHP shell: ',$command,': no se encontró la orden',"\n";
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
                foreach($method->getParameters() as &$p) {
                    echo ($p->isOptional() ? '['.$p->name.' = '.$p->getDefaultValue().']' : $p->name),' ';
                }
                echo "\n";
                exit;
            }
        }
        // Invocar main
        $method = new \ReflectionMethod($shell, 'main');
        if (count($args)<$method->getNumberOfRequiredParameters()) {
            echo 'SowerPHP shell: ',$command,': requiere al menos ',
                $method->getNumberOfRequiredParameters(),' parámetro(s)',"\n";
            echo '   Modo de uso: ',$command,' ';
            foreach($method->getParameters() as &$p) {
                echo ($p->isOptional() ? '['.$p->name.' = '.$p->getDefaultValue().']' : $p->name),' ';
            }
            echo "\n";
            return 1;
        }
        $return = $method->invokeArgs($shell, $args);
        // Retornar estado
        return $return ? $return : 0;
    }

}
