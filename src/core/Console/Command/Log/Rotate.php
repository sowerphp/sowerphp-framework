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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Comando para rotar los archivos de log.
 *
 * Este comando permite archivar los logs antiguos y comenzar nuevos archivos
 * de log.
 *
 * ## Ejemplos de uso:
 *
 * Para ejecutar el comando, usa:
 * ```
 * php bin/console log:rotate
 * ```
 *
 * @package sowerphp\core
 */
class Console_Command_Log_Rotate extends Command
{

    /**
     * Nombre del comando.
     *
     * @var string
     */
    protected static $defaultName = 'log:rotate';

    /**
     * Configuración del comando.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Rota los archivos de log.')
            ->setHelp('Este comando permite archivar los logs antiguos y comenzar nuevos archivos de log.');
    }

    /**
     * Método principal del comando para ser ejecutado.
     *
     * @param InputInterface $input
     * @param OutputInterface $output): int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // TODO: implementar ejecución.
        $output->writeln(__('Comando %s no está implementado.', static::$defaultName));
        return Command::FAILURE;
    }

}
