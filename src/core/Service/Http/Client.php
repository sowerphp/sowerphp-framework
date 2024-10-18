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

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;

/**
 * Servicio de cliente HTTP.
 *
 * Gestiona las solicitudes HTTP utilizando Illuminate Http Client.
 */
class Service_Http_Client implements Interface_Service
{
    /**
     * Instancia de Factory.
     *
     * @var Factory
     */
    protected $httpFactory;

    /**
     * Registra el servicio de cliente HTTP.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio de cliente HTTP.
     *
     * @return void
     */
    public function boot(): void
    {
        // Crear una instancia de Factory.
        $this->httpFactory = new Factory();
    }

    /**
     * Finaliza el servicio de cliente HTTP.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Envía una solicitud GET.
     *
     * @param string $url URL a la cual se enviará la solicitud.
     * @param array $queryParams Parámetros de consulta para la solicitud.
     * @param array $headers Cabeceras para la solicitud.
     * @return Response Respuesta de la solicitud.
     */
    public function get(string $url, array $queryParams = [], array $headers = []): Response
    {
        return $this->httpFactory->withHeaders($headers)->get($url, $queryParams);
    }

    /**
     * Envía una solicitud POST.
     *
     * @param string $url URL a la cual se enviará la solicitud.
     * @param array $data Datos que se enviarán en el cuerpo de la solicitud.
     * @param array $headers Cabeceras para la solicitud.
     * @return Response Respuesta de la solicitud.
     */
    public function post(string $url, array $data = [], array $headers = []): Response
    {
        return $this->httpFactory->withHeaders($headers)->post($url, $data);
    }

    /**
     * Envía una solicitud PUT.
     *
     * @param string $url URL a la cual se enviará la solicitud.
     * @param array $data Datos que se enviarán en el cuerpo de la solicitud.
     * @param array $headers Cabeceras para la solicitud.
     * @return Response Respuesta de la solicitud.
     */
    public function put(string $url, array $data = [], array $headers = []): Response
    {
        return $this->httpFactory->withHeaders($headers)->put($url, $data);
    }

    /**
     * Envía una solicitud DELETE.
     *
     * @param string $url URL a la cual se enviará la solicitud.
     * @param array $data Datos que se enviarán en el cuerpo de la solicitud (si es necesario).
     * @param array $headers Cabeceras para la solicitud.
     * @return Response Respuesta de la solicitud.
     */
    public function delete(string $url, array $data = [], array $headers = []): Response
    {
        return $this->httpFactory->withHeaders($headers)->delete($url, $data);
    }

    /**
     * Acceso directo al cliente de Guzzle.
     *
     * @return \GuzzleHttp\Client Cliente de Guzzle.
     */
    public function guzzleClient(): \GuzzleHttp\Client
    {
        return $this->httpFactory->getClient();
    }
}
