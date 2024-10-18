<?php

declare(strict_types=1);

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

use Closure;

/**
 * Interfaz para los middlewares.
 */
interface Interface_Middleware
{
    /**
     * Maneja una solicitud antes de llegar al controlador.
     *
     * @param Network_Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handleBefore(
        Network_Request $request,
        Closure $next
    ): Network_Request;

    /**
     * Maneja una solicitud después de que el controlador ha generado una
     * respuesta.
     *
     * @param Network_Request $request
     * @param Network_Response $response
     * @param Closure $next
     * @return mixed
     */
    public function handleAfter(
        Network_Request $request,
        Network_Response $response,
        Closure $next
    ): Network_Response;
}
