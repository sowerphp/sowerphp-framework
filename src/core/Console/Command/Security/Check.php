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
 * Comando para verificar las vulnerabilidades de seguridad en las dependencias.
 *
 * Este comando permite verificar si las dependencias del proyecto tienen
 * vulnerabilidades conocidas.
 *
 * ## Ejemplos de uso:
 *
 * Para ejecutar el comando, usa:
 * ```
 * php bin/console security:check
 * ```
 *
 * @package sowerphp\core
 */
class Console_Command_Security_Check extends Command
{

    /**
     * Nombre del comando.
     *
     * @var string
     */
    protected static $defaultName = 'security:check';

    /**
     * Configuración del comando.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Verifica las vulnerabilidades de seguridad en las dependencias.')
            ->setHelp('Este comando permite verificar si las dependencias del proyecto tienen vulnerabilidades conocidas.');
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
