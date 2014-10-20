<?php

/**
 * SowerPHP: Minimalist Framework for PHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
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
     * @todo Utilizar shells que estén dentro de módulos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-20
     */
    private static function dispatch ($command, $args)
    {
        // Si el comando fue vacío se retorna estado de error
        if (empty($command)) {
            echo 'SowerPHP shell: debe indicar una orden a ejecutar',"\n";
            return 1;
        }
        // Crear objeto
        $class = \sowerphp\core\App::findClass('Shell_Command_'.$command);
        if (!class_exists($class)) {
            echo 'SowerPHP shell: ',$command,': no se encontró la orden',"\n";
            return 1;
        }
        $shell = new $class();
        // poner modo verbose que corresponda (de 1 a 5)
        $argc = count($args);
        for ($i=0; $i<$argc; $i++) {
            if (in_array($args[$i], ['-v', '-vv', '-vvv', '-vvvv', '-vvvvv'])) {
                $shell->verbose = strlen($args[$i]) - 1;
                unset($args[$i]);
            }
        }
        // Invocar main
        $method = new \ReflectionMethod($shell, 'main');
        if (count($args)<$method->getNumberOfRequiredParameters()) {
            echo 'SowerPHP shell: ',$command,': requiere al menos ',
                $method->getNumberOfRequiredParameters(),' parámetro(s)',"\n";
            echo '   Modo de uso: ',$command,' ';
            foreach($method->getParameters() as &$p) {
                echo ($p->isOptional() ? '['.$p->name.']' : $p->name),' ';
            }
            echo "\n";
            return 1;
        }
        $return = $method->invokeArgs($shell, $args);
        // Retornar estado
        return $return ? $return : 0;
    }

}
