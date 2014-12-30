<?php

/**
 * SowerPHP: Minimalist Framework for PHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

namespace sowerphp\core;

/**
 * Clase para un cliente de APIs REST
 * Permite manejar solicitudes y respuestas
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-12-03
 */
class Network_Http_Rest
{

    protected $methods = ['get', 'put', 'patch', 'delete', 'post']; ///< Métodos HTTP soportados
    protected $config; ///< Configuración para el cliente REST
    protected $header; ///< Cabecerá que se enviará

    /**
     * Constructor del cliente REST
     * @param config Arreglo con la configuración del cliente
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-12-02
     */
    public function __construct($config = [])
    {
        // cargar configuración de la solicitud que se hará
        if (!is_array($config))
            $config = ['base'=>$config];
        $this->config = array_merge([
            'base' => '',
            'user' => null,
            'pass' => 'X',
        ], $config);
        // crear cabecera para la solicitud que se hará
        $this->header['User-Agent'] = 'SowerPHP Network_Http_Rest';
        $this->header['Content-Type'] = 'application/json';
        if ($this->config['user']!==null) {
            $this->header['Authorization'] = 'Basic '.base64_encode(
                $this->config['user'].':'.$this->config['pass']
            );
        }
    }

    /**
     * Método que asigna la autenticación para la API REST
     * @param user Usuario (o token) con el que se está autenticando
     * @param pass Contraseña con que se está autenticando (se omite si se usa token)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-12-02
     */
    public function setAuth($user, $pass = 'X')
    {
        $this->config['user'] = $user;
        $this->config['pass'] = $pass;
        $this->header['Authorization'] = 'Basic '.base64_encode(
            $this->config['user'].':'.$this->config['pass']
        );
    }

    /**
     * Método para realizar solicitud al recurso de la API
     * @param method Nombre del método que se está ejecutando
     * @param args Argumentos para el métood de Network_Http_Socket
     * @return Arreglo con la respuesta HTTP (índices: status, header y body)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-12-29
     */
    public function __call($method, $args)
    {
        if (!isset($args[0]) or !in_array($method, $this->methods))
            return false;
        $resource = $args[0];
        $data = isset($args[1]) ? $args[1] : [];
        $header = isset($args[2]) ? $args[2] : [];
        $sslv3 = isset($args[3]) ? $args[3] : false;
        $sslcheck = isset($args[4]) ? $args[4] : true;
        if ($data and $method!='get') {
            $data = json_encode($data);
            $this->header['Content-Length'] = strlen($data);
        }
        $response = Network_Http_Socket::$method(
            $this->config['base'].$resource,
            $data,
            array_merge($this->header, $header),
            $sslv3,
            $sslcheck
        );
        $body = json_decode($response['body'], true);
        return [
            'status' => $response['status'],
            'header' => $response['header'],
            'body' => $body!==null ? $body : $response['body'],
        ];
    }

}
