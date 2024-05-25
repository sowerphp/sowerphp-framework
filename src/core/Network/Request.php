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

namespace sowerphp\core;

use Illuminate\Http\Request;

/**
 * Clase con la solicitud del cliente.
 */
class Network_Request //extends Request
{

    /**
     * URI usada para la consulta desde la aplicacion, o sea, sin base,
     * iniciando con "/".
     */
    private string $requestUriDecoded;

    /**
     * Ruta base de la URL (base + uri arma el total del request).
     */
    private string $baseUrlWithoutSlash;

    /**
     * URL completa, partiendo desde HTTP o HTTPS según corresponda.
     */
    private string $fullUrlWithoutQuery;

    /**
     * Parámetros pasados que definen que ejecutar.
     */
    private array $parsedParams;

    /**
     * Cabeceras HTTP de la solicitud.
     */
    private array $headersList;

    //public $request;  -> getRequestUriDecoded()
    //public $base;     -> getBaseUrlWithoutSlash()
    //public $url;      -> getFullUrlWithoutQuery()
    //public $params;   -> getParsedParams()

    /**
     * Obtener el estado actual de la solicitud HTTP.
     */
    public function __construct()
    {
        // asignar datos de la solicitud
        $this->getRequestUriDecoded();
        $this->getBaseUrlWithoutSlash();
        $this->getFullUrlWithoutQuery();
        // Quitar de lo pasado por get lo que se está solicitando
        unset($_GET[$this->getRequestUriDecoded()]);
    }

    /**
     * Método mágico para retrocompatibilidad con los atributos públicos antiguos.
     * Se puede consultar por: request, base, url y params.
     */
    public function __get(string $name)
    {
        if (method_exists($this, $name)) {
            return $this->{$name}();
        }
        //throw new \Exception('Atributo Network_Request::$'.$name.' no está definido.');
    }

    /**
     * Método que determina la solicitud utilizada para acceder a la página.
     * @return string Solicitud completa para la página consultada.
     */
    public function getRequestUriDecoded(): string
    {
        if (!isset($this->requestUriDecoded)) {
            if (!isset($_SERVER['QUERY_STRING'])) {
                $request = '';
            } else {
                // Obtener ruta que se uso sin "/" (base) inicial
                $uri = (isset($_SERVER['QUERY_STRING'][0]) && $_SERVER['QUERY_STRING'][0] == '/')
                    ? substr($_SERVER['QUERY_STRING'], 1)
                    : $_SERVER['QUERY_STRING']
                ;
                if (strpos($_SERVER['REQUEST_URI'], '/?' . $uri) !== false) {
                    $uri = '';
                }
                // verificar si se pasaron variables GET
                $inicio_variables_get = strpos($uri, '&');
                // Asignar uri
                $request = $inicio_variables_get === false
                    ? $uri
                    : substr($uri, 0, $inicio_variables_get)
                ;
                // Agregar slash inicial de la uri
                if (!isset($request) || (isset($request[0]) && $request[0] != '/')) {
                    $request = '/' . $request;
                }
                // Decodificar url
                $request = urldecode($request);
            }
            $this->requestUriDecoded = $request;
        }
        return $this->requestUriDecoded;
    }

    /**
     * Método que determina los campos base y webroot.
     * @return string Base de la URL.
     */
    public function getBaseUrlWithoutSlash(): string
    {
        if (!isset($this->baseUrlWithoutSlash)) {
            if (!isset($_SERVER['REQUEST_URI'])) {
                $base = '';
            } else {
                $parts = explode('?', urldecode($_SERVER['REQUEST_URI']));
                $last = strrpos($parts[0], $this->getRequestUriDecoded());
                $base = $last !== false
                    ? substr($parts[0], 0, $last)
                    : $parts[0]
                ;
                $position = strlen($base) - 1;
                if ($position >= 0 && $base[$position] == '/') {
                    $base = substr($base, 0, -1);
                }
            }
            $this->baseUrlWithoutSlash = $base;
        }
        return $this->baseUrlWithoutSlash;
    }

    /**
     * Método que determina la URL utiliza para acceder a la aplicación, esto
     * es: protocolo/esquema, dominio y path base a contar del webroot).
     * @return string URL completa para acceder a la la página.
     */
    public function getFullUrlWithoutQuery(): string
    {
        if (!isset($this->fullUrlWithoutQuery)) {
            if (empty($_SERVER['HTTP_HOST'])) {
                $url = (string)config('app.url');
            } else {
                if ($this->getSingleHeader('X-Forwarded-Proto') == 'https') {
                    $scheme = 'https';
                } else {
                    $scheme = 'http' . (isset($_SERVER['HTTPS']) ? 's' : null);
                }
                $url = $scheme . '://' . $_SERVER['HTTP_HOST'] . $this->getBaseUrlWithoutSlash();
            }
            $this->fullUrlWithoutQuery = $url;
        }
        return $this->fullUrlWithoutQuery;
    }

    /**
     * Método que asigna o entrega los parámetros de la solicitud.
     * @param array|null $params
     * @return array
     */
    public function getParsedParams(): array
    {
        if (!isset($this->parsedParams)) {
            $this->parsedParams = Routing_Router::parse($this->getRequestUriDecoded());
        }
        return $this->parsedParams;
    }

    /**
     * Método que entrega las cabeceras enviadas al servidor web por el cliente.
     * @param string header Cabecera que se desea recuperar, o null si se quieren traer todas.
     * @return mixed Arreglo si no se pidió por una específica o su valor si se pidió (=false si no existe cabecera, =null si no existe función apache_request_headers).
     */
    public function getSingleHeader(?string $header = null)
    {
        $headers = $this->getAllHeaders();
        if ($header === null) {
            return $headers;
        }
        if (isset($headers[$header])) {
            return $headers[$header];
        }
        return false;
    }

    /**
     * Método que entrega las cabeceras enviadas al servidor web por el cliente.
     * @return array Arreglo con las cabeceras HTTP recibidas.
     */
    public function getAllHeaders(): array
    {
        if (!isset($this->headersList)) {
            if (!function_exists('apache_request_headers')) {
                $headers = [];
            } else {
                $headers = apache_request_headers();
            }
            $this->headersList = $headers;
        }
        return $this->headersList;
    }

    /**
     * Método que indica si la solicitud HTTP es o no a la API.
     * Esto se determina de 2 formas:
     *   - La solicitud parte por /api/
     *   - La solicitud acepta como respuesta un JSON
     *
     * @return boolean
     */
    public function isApiRequest(): bool
    {
        $api_prefix = strpos($this->request, '/api/') === 0;
        $accept_json = $this->getSingleHeader('Accept') == 'application/json'; // WARNING: podría retornar arreglo (?)
        return $api_prefix || $accept_json;
    }

}
