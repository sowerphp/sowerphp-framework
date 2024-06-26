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
 * Clase para un cliente de APIs REST.
 * Permite manejar solicitudes y respuestas HTTP usando JSON.
 */
class Network_Http_Rest
{

    // Métodos HTTP soportados.
    protected $methods = ['get', 'put', 'patch', 'delete', 'post'];

    // Configuración para el cliente HTTP.
    protected $config;

    // Cabecerá que se enviará.
    protected $header;

    // Errores de la consulta HTTP.
    protected $errors = [];

    // Indica si los object de JSON se deben entregar como arreglos
    // asociativos o no.
    protected $assoc = true;

    /**
     * Constructor del cliente REST.
     * @param array $config Arreglo con la configuración del cliente.
     */
    public function __construct(array $config = [])
    {
        // Cargar configuración de la solicitud que se hará.
        if (!is_array($config)) {
            $config = [
                'base' => $config,
            ];
        }
        $this->config = array_merge([
            'base' => '',
            'user' => null,
            'pass' => 'X',
        ], $config);
        // Crear cabecera para la solicitud que se hará.
        $this->header['User-Agent'] = 'SowerPHP Network_Http_Rest';
        $this->header['Content-Type'] = 'application/json';
        if ($this->config['user'] !== null) {
            $this->setAuth($this->config['user'], $this->config['pass']);
        }
    }

    /**
     * Método que asigna la autenticación para la API REST.
     * @param string $user Usuario (o token) con el que se está autenticando.
     * @param string $pass Contraseña con que se está autenticando
     * (se omite si se usa token).
     */
    public function setAuth(string $user, string $pass = 'X'): void
    {
        $this->config['user'] = $user;
        $this->config['pass'] = $pass;
        $this->header['Authorization'] = 'Basic ' . base64_encode(
            $this->config['user'] . ':' . $this->config['pass']
        );
    }

    /**
     * Método que indica si las respuestas JSON se deben entregar como
     * arreglos asociativos o no.
     * @param bool $assoc =true respuestas serán arreglos asociativos,
     * =fale serán objetos.
     */
    public function setAssoc(bool $assoc = true): void
    {
        $this->assoc = $assoc;
    }

    /**
     * Método para realizar solicitud al recurso de la API.
     * @param string $method Nombre del método que se está ejecutando.
     * @param mixed $args Argumentos para el métood de Network_Http_Socket.
     * @return array Arreglo con la respuesta HTTP (índices: status, header y body).
     */
    public function __call($method, $args)
    {
        if (!isset($args[0]) || !in_array($method, $this->methods)) {
            return false;
        }
        $resource = $args[0];
        $data = isset($args[1]) ? $args[1] : [];
        $header = isset($args[2]) ? $args[2] : [];
        $sslv3 = isset($args[3]) ? $args[3] : false;
        $sslcheck = isset($args[4]) ? $args[4] : true;
        if ($data && $method != 'get') {
            if (isset($data['@files'])) {
                $files = $data['@files'];
                unset($data['@files']);
                $data = [
                    '@data' => json_encode($data),
                ];
                foreach ($files as $key => $file) {
                    $data[$key] = $file;
                }
            } else {
                $data = json_encode($data);
                $header['Content-Length'] = strlen($data);
            }
        }
        $response = Network_Http_Socket::$method(
            $this->config['base'].$resource,
            $data,
            array_merge($this->header, $header),
            $sslv3,
            $sslcheck
        );
        if ($response === false) {
            $this->errors[] = Network_Http_Socket::getLastError();
            return false;
        }
        $body = json_decode($response['body'], $this->assoc);
        return [
            'status' => $response['status'],
            'header' => $response['header'],
            'body' => $body ?? $response['body'],
        ];
    }

    /**
     * Método que entrega los errores ocurridos al ejecutar la consulta a REST.
     * @return array Arreglo con los errores.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

}
