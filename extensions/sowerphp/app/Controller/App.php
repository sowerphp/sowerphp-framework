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
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace sowerphp\app;

/**
 * Clase que sirve para extender la clase Controller, este archivo
 * deberá ser sobreescrito en cada una de las aplicaciones
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2017-10-10
 */
class Controller_App extends \sowerphp\core\Controller
{

    public $components = ['Auth', 'Api', 'Log']; ///< Componentes usados por el controlador
    public $Cache; ///< Objeto para usar el caché
    public $log_facility = LOG_USER; ///< Origen por defecto de los eventos de los controladores

    /**
     * Constructor de la clase
     * @param request Objeto con la solicitud realizada
     * @param response Objeto para la respuesta que se enviará al cliente
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-16
     */
    public function __construct (\sowerphp\core\Network_Request $request, \sowerphp\core\Network_Response $response)
    {
        parent::__construct ($request, $response);
        $this->Cache = new \sowerphp\core\Cache();
        $this->set('_Auth', $this->Auth);
    }

    /**
     * Método para permitir el acceso a las posibles funcionalidades de la API
     * del controlador que se está ejecutando. Aquí no se validan permisos para
     * la funcionalidad, estos deberán ser validados en cada función
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2023-01-29
     */
    public function beforeFilter()
    {
        if (!empty($this->allowedActions)) {
            call_user_func_array([$this->Auth, 'allow'], $this->allowedActions);
        }
        if (!empty($this->allowedActionsWithLogin)) {
            call_user_func_array([$this->Auth, 'allowWithLogin'], $this->allowedActions);
        }
        $this->Auth->allow('api');
        parent::beforeFilter();
    }

    /**
     * Método que lanza el servicio web que se ha solicitado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-12-02
     */
    public function api($resource, $args = null)
    {
        call_user_func_array([$this->Api, 'run'], func_get_args());
    }

    /**
     * Método que permite consumir por POST o GET un recurso de la misma aplicación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-08-04
     */
    protected function consume($recurso, $datos = [], $assoc = true)
    {
        $rest = new \sowerphp\core\Network_Http_Rest();
        $rest->setAuth($this->Auth->User ? $this->Auth->User->hash : \sowerphp\core\Configure::read('api.default.token'));
        $rest->setAssoc($assoc);
        if ($datos) {
            return $rest->post($this->request->url.$recurso, $datos);
        } else {
            return $rest->get($this->request->url.$recurso);
        }
    }

    /**
     * Método que permite ejecutar un comando en la terminal
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-11-28
     */
    protected function shell($cmd, $log = false, &$output = [])
    {
        if ($log and !is_string($log)) {
            $log = TMP.'/screen_'.$this->Auth->ip().'_'.date('YmdHis').'.log';
        }
        return shell_exec_async($cmd, $log, $output);
    }

}
