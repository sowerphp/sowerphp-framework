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

/**
 * Mensaje para representar un trabajo de consola.
 */
class Network_Messenger_Message_Job
{

    /**
     * El nombre del comando.
     *
     * @var string
     */
    protected $command;

    /**
     * Los argumentos del comando.
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * Las opciones del comando.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Constructor del mensaje de trabajo.
     *
     * @param string $command El nombre del comando.
     * @param array $arguments Los argumentos del comando.
     * @param array $options Las opciones del comando.
     */
    public function __construct(string $command, array $arguments = [], array $options = [])
    {
        $this->command = $command;
        $this->arguments = $arguments;
        $this->options = $options;
    }

    /**
     * Obtiene el nombre del comando.
     *
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * Obtiene los argumentos del comando.
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Obtiene las opciones del comando.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

}
