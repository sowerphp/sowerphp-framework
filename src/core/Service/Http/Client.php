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

use Illuminate\Http\Client\Factory;
use Illuminate\Container\Container;
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
     * Constructor de la clase.
     *
     * @param Service_Config $configService Servicio de configuración.
     */
    public function __construct(Service_Config $configService)
    {
        // Crear un contenedor de Illuminate
        $container = new Container();

        // Configurar el contenedor con la configuración de cliente HTTP
        $container['config'] = [
            'http' => $configService->get('http'),
        ];

        // Crear una instancia de Factory
        $this->httpFactory = new Factory($container);
    }

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
     * @param string $url
     * @param array $options
     * @return Response
     */
    public function get(string $url, array $options = []): Response
    {
        return $this->httpFactory->get($url, $options);
    }

    /**
     * Envía una solicitud POST.
     *
     * @param string $url
     * @param array $data
     * @return Response
     */
    public function post(string $url, array $data = []): Response
    {
        return $this->httpFactory->post($url, $data);
    }

    /**
     * Envía una solicitud PUT.
     *
     * @param string $url
     * @param array $data
     * @return Response
     */
    public function put(string $url, array $data = []): Response
    {
        return $this->httpFactory->put($url, $data);
    }

    /**
     * Envía una solicitud DELETE.
     *
     * @param string $url
     * @param array $options
     * @return Response
     */
    public function delete(string $url, array $options = []): Response
    {
        return $this->httpFactory->delete($url, $options);
    }

    /**
     * Método personalizado exampleMethod.
     *
     * @param string $url
     * @return Response
     */
    public function exampleMethod(string $url): Response
    {
        // Ejemplo de uso directo del cliente de Guzzle a través de Laravel
        return $this->httpFactory->get($url);
    }

    /**
     * Acceso directo al cliente de Guzzle.
     *
     * @return \GuzzleHttp\Client
     */
    public function guzzleClient(): \GuzzleHttp\Client
    {
        return $this->httpFactory->getClient();
    }

}
