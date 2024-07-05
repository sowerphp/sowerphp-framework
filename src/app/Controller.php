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

namespace sowerphp\app;

/**
 * Clase que sirve para extender la clase Controller, este archivo
 * deberá ser sobreescrito en cada una de las aplicaciones.
 */
class Controller extends \sowerphp\core\Controller
{

    // Componentes usados por el controlador.
    public $components = ['Auth', 'Api', 'Log'];

    // Objeto para usar el caché.
    public $Cache;

    // Origen por defecto de los eventos de los controladores.
    public $log_facility = LOG_USER;

    /**
     * Constructor de la clase.
     * @param request Objeto con la solicitud realizada.
     * @param response Objeto para la respuesta que se enviará al cliente.
     */
    public function __construct(\sowerphp\core\Network_Request $request, \sowerphp\core\Network_Response $response)
    {
        parent::__construct ($request, $response);
        $this->Cache = cache();
        $this->set('_Auth', $this->Auth);
    }

    /**
     * Método para permitir el acceso a las posibles funcionalidades de la API
     * del controlador que se está ejecutando. Aquí no se validan permisos para
     * la funcionalidad, estos deberán ser validados en cada función.
     */
    public function boot(): void
    {
        if (!empty($this->allowedActions)) {
            call_user_func_array(
                [$this->Auth, 'allow'],
                $this->allowedActions
            );
        }
        if (!empty($this->allowedActionsWithLogin)) {
            call_user_func_array(
                [$this->Auth, 'allowWithLogin'],
                $this->allowedActions
            );
        }
        $this->Auth->allow('api');
        parent::boot();
    }

    /**
     * Método que lanza el servicio web que se ha solicitado.
     */
    public function api($resource, $args = null)
    {
        call_user_func_array([$this->Api, 'run'], func_get_args());
    }

    /**
     * Método que permite consumir por POST o GET un recurso de la misma
     * aplicación.
     */
    protected function consume(string $recurso, $datos = [], bool $assoc = true)
    {
        $hash = $this->Auth->User
            ? $this->Auth->User->hash
            : config('app.api.auth.default_token')
        ;
        $rest = new \sowerphp\core\Network_Http_Rest();
        $rest->setAuth($hash);
        $rest->setAssoc($assoc);
        $url = $this->request->getFullUrlWithoutQuery() . $recurso;
        if ($datos) {
            $response = $rest->post($url, $datos);
        } else {
            $response = $rest->get($url);
        }
        if ($response === false) {
            throw new \Exception(
                'Error al consumir internamente el recurso ' . $recurso
                    . ': ' . implode(' / ', $rest->getErrors())
            );
        }
        return $response;
    }

    /**
     * Método que permite ejecutar un comando en la terminal.
     */
    protected function shell($cmd, $log = false, &$output = [])
    {
        if ($log && !is_string($log)) {
            $log = DIR_TMP . '/screen_' . $this->Auth->ip() . '_' . date('YmdHis') . '.log';
        }
        return shell_exec_async($cmd, $log, $output);
    }

}
