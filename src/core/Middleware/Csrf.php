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
 * Middleware para validar el Token CSRF en solicitudes que POST.
 */
class Middleware_Csrf implements Interface_Middleware
{

    /**
     * Maneja una solicitud antes de llegar al controlador.
     *
     * @param Network_Request $request
     * @param \Closure $next
     * @return Network_Request
     */
    public function handleBefore(
        Network_Request $request,
        \Closure $next
    ): Network_Request
    {
        // Solo validar el Token CSRF en solicitudes POST.
        if ($request->method() == 'POST') {
            $csfrTokenPost = $request->input('csrf_token');
            if (!$csfrTokenPost) {
                /*return redirect()->back()->withError(__(
                    'No se ha especificado el Token CSRF.'
                ));*/
            }
            /*$csfrTokenSession = session('csrf_token');
            if ($csfrTokenPost != $csfrTokenSession) {
                return redirect()->back()->withError(__(
                    'El Token CSRF es inválido.'
                ));
            }
            dd($csfrTokenPost, $csfrTokenSession);*/
        }
        // Pasar al siguiente middleware.
        return $next($request);
    }

    /**
     * Maneja una solicitud después de que el controlador ha generado una
     * respuesta.
     *
     * @param Network_Request $request
     * @param Network_Response $response
     * @param \Closure $next
     * @return Network_Response
     */
    public function handleAfter(
        Network_Request $request,
        Network_Response $response,
        \Closure $next
    ): Network_Response
    {
        // Pasar al siguiente middleware.
        return $next($request, $response);
    }

}
