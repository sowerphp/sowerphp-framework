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
 * Middleware para autenticación.
 */
class Middleware_Log implements Interface_Middleware
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
        // Registrar log de la API (todas las llamadas).
        // TODO: refactorizar cuando se vea Component_Log.
        /*$cacheStats = cache()->getStats();
        $msg = $Api->method.' '.$Api->getResource().' '.$Api->controller->response->status().' '.$Api->controller->response->length();
        $msg .= ' '.round(microtime(true)-TIME_START, 2);
        $msg .= ' '.round(memory_get_usage()/1024/1024,2);
        $msg .= ' '.database()->getStats()['queries'];
        $msg .= ' '.$cacheStats['assigned'].' '.$cacheStats['retrieved'];
        $Log->write($msg, LOG_INFO, $Api->settings['log']);*/
        // Registrar log si la aplicación tuvo un error. O sea, si se renderizó
        // a través de la acción Controller_App::error()
        /*$Log->write([
            'exception' => $data['exception'],
            'message' => $data['message'],
            'trace' => $data['trace'],
            'code' => $data['code'],
        ], $data['severity']);*/
        // Pasar al siguiente middleware.
        return $next($request, $response);
    }

}
