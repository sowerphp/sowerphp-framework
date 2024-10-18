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
 * Middleware para autenticación.
 */
class Middleware_Auth implements Interface_Middleware
{
    /**
     * Maneja una solicitud antes de llegar al controlador.
     *
     * @param Network_Request $request
     * @param Closure $next
     * @return Network_Request
     */
    public function handleBefore(
        Network_Request $request,
        Closure $next
    ): Network_Request
    {
        // TODO: se debe refactorizar cómo se hace actualmente.
        // El problema, es que se definen unos permisos previos para acceder
        // sin autenticación o solo estando logueado en los métodos boot() de
        // los controladores, que se ejecutan después que los métodos
        // handleBefore() de los middlewares (o sea este). Y esa definición de
        // permisos se requiere acá (si se mueve la lógica) para poder validar
        // el acceso con esos 2 métodos (sin login o solo con login) antes de
        // pasar a corroborar la autorización específica con el usuario (que si
        // se podría hacer "fácil" acá).
        // Pasar al siguiente middleware.
        return $next($request);
    }

    /**
     * Maneja una solicitud después de que el controlador ha generado una
     * respuesta.
     *
     * @param Network_Request $request
     * @param Network_Response $response
     * @param Closure $next
     * @return Network_Response
     */
    public function handleAfter(
        Network_Request $request,
        Network_Response $response,
        Closure $next
    ): Network_Response
    {
        // Pasar al siguiente middleware.
        return $next($request, $response);
    }
}
