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

/**
 * Clase base para los motores de renderizado de plantillas HTML.
 */
abstract class View_Engine
{

    /**
     * Servicio de capas de la aplicación.
     *
     * @var Service_Layers
     */
    protected $layersService;

    /**
     * Servicio de vistas de la aplicación.
     *
     * @var Service_View
     */
    protected $viewService;

    /**
     * Constructor del motor de renderizado de las vistas.
     */
    public function __construct(
        Service_Layers $layersService,
        Service_View $viewService
    )
    {
        $this->layersService = $layersService;
        $this->viewService = $viewService;
        $this->boot();
    }

    /**
     * Inicializar el motor de renderizado de las vistas.
     *
     * @return void
     */
    protected function boot(): void
    {
    }

}
