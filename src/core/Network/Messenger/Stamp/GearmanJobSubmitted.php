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

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Sello de Messenger que indica que un trabajo Gearman ha sido enviado.
 *
 * Esta clase implementa StampInterface y se utiliza para adjuntar información
 * adicional a un mensaje en el contexto de Symfony Messenger. En este caso,
 * el sello contiene el identificador del trabajo Gearman enviado.
 */
class Network_Messenger_Stamp_GearmanJobSubmitted implements StampInterface
{

    /**
     * El identificador del trabajo Gearman.
     *
     * @var string
     */
    protected $jobHandle;

    /**
     * Constructor de la clase.
     *
     * @param string $jobHandle El identificador del trabajo Gearman.
     */
    public function __construct(string $jobHandle)
    {
        $this->jobHandle = $jobHandle;
    }

    /**
     * Obtiene el identificador del trabajo Gearman.
     *
     * @return string El identificador del trabajo Gearman.
     */
    public function getJobHandle(): string
    {
        return $this->jobHandle;
    }

}
