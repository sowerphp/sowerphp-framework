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

namespace sowerphp\app\Apps;

/**
 * Modelo para interactuar con la API de Imgur
 */
class Model_Imgur
{

    private $config; ///< Configuración de la conexión
    private $rest; ///< Objeto para la conexión a la API
    private $url = 'https://api.imgur.com/3'; ///< URL base de la API

    /**
     * Constructor del cliente de la API
     * Asigna configuración del cliente
     * Parámetro obligatorio: client_id
     */
    public function __construct(array $config = [])
    {
        // configuración y campos mínimos
        if (empty($config)) {
            $config = config('module.Apps.Imgur');
        }
        $this->config = $config;
        if (empty($this->config['client_id'])) {
            throw new \Exception('client_id is required in configuration for Imgur!');
        }
        // cliente rest para la conexión a la API
        $this->rest = new \sowerphp\core\Network_Http_Rest();
    }

    /**
     * Método que sube una imagen
     */
    public function upload($data)
    {
        $response = $this->post('/image', [
            'image' => base64_encode($data),
        ]);
        return $response['body']['data'];
    }

    /**
     * Método que hace las llamadas reales a la API
     */
    private function consume($method, $resource, array $data = [])
    {
        // hacer llamada
        $response = $this->rest->$method(
            $this->url.$resource,
            $data,
            ['Authorization' => 'Client-ID ' . $this->config['client_id']]
        );
        if ($response['status']['code'] != 200) {
            if (!empty($response['body']['message'])) {
                throw new \Exception($response['body']['message'], $response['status']['code']);
            }
            else if(!empty($response['body'])) {
                throw new \Exception($response['body'], $response['status']['code']);
            }
            else {
                throw new \Exception(
                    'Error '.$response['status']['code'].': '.$response['status']['message'],
                    $response['status']['code']
                );
            }
        }
        return $response;
    }

    /**
     * Wrapper para llamadas GET a la API
     */
    private function get($resource, array $data = [])
    {
        return $this->consume('get', $resource, $data);
    }

    /**
     * Wrapper para llamadas POST a la API
     */
    private function post($resource, array $data = [])
    {
        return $this->consume('post', $resource, $data);
    }

    /**
     * Wrapper para llamadas PUT a la API
     */
    private function put($resource, array $data = [])
    {
        return $this->consume('put', $resource, $data);
    }

    /**
     * Wrapper para llamadas DELETE a la API
     */
    private function delete($resource, array $data = [])
    {
        return $this->consume('delete', $resource, $data);
    }

}
