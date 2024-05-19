<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
 * Copyright (C) SowerPHP <https://www.sowerphp.org>
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

trait Trait_ServiceContainer
{

    /**
     * Arreglo para almacenar los servicios registrados.
     */
    protected $serviceContainer = [];

    /**
     * Registra un servicio en el contenedor.
     *
     * @param string $key Identificador del servicio.
     * @param mixed $service Instancia del servicio.
     */
    public function registerService($key, $service) {
        $this->serviceContainer[$key] = $service;
    }

    /**
     * Obtiene un servicio del contenedor.
     *
     * @param string $key Identificador del servicio.
     * @return mixed Retorna el servicio registrado bajo la clave especificada.
     * @throws Exception Si el servicio solicitado no existe.
     */
    public function getService(string $key)
    {
        if (!isset($this->serviceContainer[$key])) {
            throw new \Exception(__(
                'El servicio %s no está registrado en el contenedor.',
                $key
            ));
        }
        return $this->serviceContainer[$key];
    }

}
