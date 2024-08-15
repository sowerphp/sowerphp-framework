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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

/**
 * Comando para listar la configuración actual de la aplicación.
 *
 * Este comando permite mostrar todas las configuraciones actuales de la
 * aplicación.
 *
 * ## Ejemplos de uso:
 *
 * Para ejecutar el comando, usa:
 * ```
 * php bin/console config:list
 * ```
 *
 * @package sowerphp\core
 */
class Console_Command_Config_List extends Command
{

    /**
     * Nombre del comando.
     *
     * @var string
     */
    protected static $defaultName = 'config:list';

    /**
     * Configuración del comando.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Lista la configuración actual de la aplicación.')
            ->setHelp('Este comando permite mostrar todas las configuraciones actuales de la aplicación.')
            ->addArgument('key', InputArgument::OPTIONAL, 'La configuración específica que se desea obtener.')
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
        // Obtener configuración solicitada.
        $key = $input->getArgument('key');
        $config = $key ? app('config')->get($key) : app('config')->all();
        if ($config === null) {
            $output->writeln(__(
                'La configuración "%s" no está definida.',
                $key
            ));
            return Command::INVALID;
        }

        // Ajustar la configuración son su clave vacía si se obtuvo un escalar.
        if (!is_array($config)) {
            $config = ['' => $config];
        }

        // Renderizar tabla con la configuración.
        $table = new Table($output);
        $table->setHeaders(['Config key', 'Config value']);
        foreach ($config as $configKey => $configValue) {
            $columnKey = trim($key . '.' . $configKey, '.');
            $columnValue = $this->formatValue($configValue);
            $table->addRow([$columnKey, $columnValue]);
        }
        $table->render();

        // Todo fue OK con el comando.
        return Command::SUCCESS;
    }

    /**
     * Formatea el valor de la configuración.
     *
     * @param mixed $value
     * @return string
     */
    protected function formatValue($value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT);
        }
        return (string) $value;
    }

}
