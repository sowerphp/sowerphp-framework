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

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Clase con la solicitud del cliente.
 */
class Network_Request extends Request
{

    /**
     * URL completa, partiendo desde HTTP o HTTPS según corresponda.
     *
     * @var string
     */
    protected $fullUrlWithoutQuery;

    /**
     * Ruta base de la URL (base + uri arma el total del request).
     *
     * @var string
     */
    protected $baseUrlWithoutSlash;

    /**
     * URI usada para la consulta desde la aplicacion, o sea, sin base,
     * iniciando con "/".
     *
     * @var string
     */
    protected $requestUriDecoded;

    /**
     * Configuración de la ruta de la solicitud HTTP.
     *
     * @var array
     */
    protected $routeConfig;

    /**
     * Servicio de la sesión del usuario en la solicitud.
     *
     * @var Service_Http_Session
     */
    protected $sessionService;

    /**
     * Capturar el estado HTTP actual y entregar una instancia del objeto.
     *
     * @return Network_Request
     */
    public static function capture(): Network_Request
    {
        $request = parent::capture();
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $request->headers->set(
                'Authorization',
                $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            );
        }
        return $request;
    }

    /**
     * Obtiene la instancia del servicio de sesión.
     *
     * @return Service_Http_Session
     */
    public function session()
    {
        if (!isset($this->sessionService)) {
            $this->sessionService = session();
        }
        return $this->sessionService;
    }

    /**
     * Método que determina la URL utiliza para acceder a la aplicación, esto
     * es: protocolo/esquema, dominio y ruta base dentro del dominio).
     *
     * @return string URL completa para acceder a la aplicación web.
     */
    public function getFullUrlWithoutQuery(): string
    {
        if (!isset($this->fullUrlWithoutQuery)) {
            if (empty($_SERVER['HTTP_HOST'])) {
                $url = (string)config('app.url');
            } else {
                if ($this->headers->get('X-Forwarded-Proto') == 'https') {
                    $scheme = 'https';
                } else {
                    $scheme = 'http' . (isset($_SERVER['HTTPS']) ? 's' : null);
                }
                $url = $scheme . '://' . $_SERVER['HTTP_HOST']
                    . $this->getBaseUrlWithoutSlash()
                ;
            }
            $this->fullUrlWithoutQuery = $url;
        }
        return $this->fullUrlWithoutQuery;
    }

    /**
     * Método que determina la ruta base dentro del dominio.
     *
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
     * Método que determina la solicitud utilizada para acceder a la página.
     *
     * @return string Solicitud completa para la página consultada.
     */
    public function getRequestUriDecoded(): string
    {
        if (!isset($this->requestUriDecoded)) {
            if (!isset($_SERVER['QUERY_STRING'])) {
                $request = '';
            } else {
                // Obtener ruta que se uso sin "/" (base) inicial.
                $uri = (
                    isset($_SERVER['QUERY_STRING'][0])
                    && $_SERVER['QUERY_STRING'][0] == '/'
                )
                    ? substr($_SERVER['QUERY_STRING'], 1)
                    : $_SERVER['QUERY_STRING']
                ;
                if (strpos($_SERVER['REQUEST_URI'], '/?' . $uri) !== false) {
                    $uri = '';
                }
                // Verificar si se pasaron variables GET.
                $inicio_variables_get = strpos($uri, '&');
                // Asignar URI.
                $request = $inicio_variables_get === false
                    ? $uri
                    : substr($uri, 0, $inicio_variables_get)
                ;
                // Agregar slash inicial de la URI.
                if (
                    !isset($request)
                    || (isset($request[0]) && $request[0] != '/')
                ) {
                    $request = '/' . $request;
                }
                // Decodificar URL.
                $request = urldecode($request);
            }
            $this->requestUriDecoded = $request;
            unset($_GET[$this->requestUriDecoded]);
        }
        return $this->requestUriDecoded;
    }

    /**
     * Método que asigna o entrega los parámetros de la solicitud.
     *
     * @param array|null $params
     * @return array
     */
    public function getRouteConfig(): array
    {
        if (!isset($this->routeConfig)) {
            $router = router();
            $this->routeConfig = $router->parse($this);
            $router->checkRouteConfig($this->routeConfig); // Lanza excepción.
        }
        return $this->routeConfig;
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
        $api_prefix = strpos($this->getRequestUriDecoded(), '/api/') === 0;
        $accept_header = $this->headers->get('Accept');
        $accept_json = Str::contains($accept_header, 'application/json');
        return $api_prefix || $accept_json;
    }

    /**
     * Método que entrega los valores de los parámetros solicitados por la URL.
     * Se entregarán siempre y cuando estén presentes en la query de la URL
     * (parámetros GET). Si no existen, se podrán entregar valores por defecto.
     *
     * @param array $params Arreglo con los parámetros, si es param => value,
     * value será el valor por defecto, sino será null el valor por defecto.
     * @return array Arreglo con los parámetros y sus valores.
     */
    public function queries(array $params): array
    {
        $vars = [];
        foreach ($params as $param => $default) {
            if (is_int($param)) {
                $param = $default;
                $default = null;
            }
            $vars[$param] = $this->query($param, $default);
        }
        return $vars;
    }

}
