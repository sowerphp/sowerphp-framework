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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

/**
 * Comando para mostrar el estado de las migraciones de la base de datos.
 *
 * Este comando permite ver el estado actual de las migraciones de la base de
 * datos.
 *
 * ## Ejemplos de uso:
 *
 * Para ejecutar el comando, usa:
 * ```
 * php bin/console migrations:status
 * ```
 *
 * @package sowerphp\core
 */
class Console_Command_Migrations_Status extends Command
{

    /**
     * Nombre del comando.
     *
     * @var string
     */
    protected static $defaultName = 'migrations:status';

    /**
     * Configuración del comando.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Muestra el estado de las migraciones de la base de datos.')
            ->setHelp('Este comando permite ver el estado actual de las migraciones de la base de datos.')
            ->addArgument('module', InputArgument::OPTIONAL, 'La configuración específica que se desea obtener.')
        ;
    }

    /**
     * Método principal del comando para ser ejecutado.
     *
     * @param InputInterface $input
     * @param OutputInterface $output): int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Buscar todos los modelos singuales
        $module = $input->getArgument('module');
        $models = model()->getEmptyInstancesFromAllSingularModels($module);
        ksort($models);

        // Renderizar tabla con los modelos encontrados.
        $i = 1;
        $table = new Table($output);
        $table->setHeaders(['#', 'Tabla', 'Modelo', 'Estado']);
        foreach ($models as $model) {
            $instance = $model['instance'];
            $table->addRow([
                $i++,
                $instance->getMetadata('model.db_table'),
                $model['name'],
            ]);
        }
        $table->render();

        // Todo ok con la ejecución.
        return Command::SUCCESS;
    }

}
