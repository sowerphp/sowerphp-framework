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

/**
 * Clase con la solicitud del cliente
 */
class Network_Request
{

    // atributos estáticos, toda instancia de esta clase será de la misma solicitud en una determinada ejecución de PHP
    private static $_request; ///< URI usada para la consulta (desde la aplicacion, o sea, sin base, iniciando con "/")
    private static $_base; ///< Ruta base de la URL (base + uri arma el total del request)
    private static $_url; ///< URL completa, partiendo desde HTTP o HTTPS según corresponda
    private static $_params; ///< Parámetros pasados que definen que ejecutar
    private static $_headers; ///< Cabeceras HTTP de la solicitud

    // atributos de instancia por retrocompatibilidad
    // @deprecated 2023-11-14
    public $request;
    public $base;
    public $url;
    //public $params; ///< WARNING: si se definine genera Excepcion dentro de llamada a la API

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // asignar datos de la solicitud
        $this->request = $this->request();
        $this->base = $this->base();
        $this->url = $this->url();
        // Quitar de lo pasado por get lo que se está solicitando
        unset($_GET[$this->request()]);
    }

    /**
     * Método mágico para retrocompatibilidad con los atributos públicos antiguos
     * Se puede consultar por: request, base, url y params
     */
    public function __get(string $name)
    {
        if (method_exists($this, $name)) {
            return $this->{$name}();
        }
        //throw new \Exception('Atributo Network_Request::$'.$name.' no está definido.');
    }

    /**
     * Método que determina la solicitud utilizada para acceder a la página
     * @return string Solicitud completa para la página consultada
     */
    public function request()
    {
        if (!isset(self::$_request)) {
            if (!isset($_SERVER['QUERY_STRING'])) {
                $request = '';
            } else {
                // Obtener ruta que se uso sin "/" (base) inicial
                $uri = (isset($_SERVER['QUERY_STRING'][0]) && $_SERVER['QUERY_STRING'][0] == '/')
                    ? substr($_SERVER['QUERY_STRING'], 1) : $_SERVER['QUERY_STRING']
                ;
                if (strpos($_SERVER['REQUEST_URI'], '/?'.$uri) !== false) {
                    $uri = '';
                }
                // verificar si se pasaron variables GET
                $inicio_variables_get = strpos($uri, '&');
                // Asignar uri
                $request = $inicio_variables_get===false ? $uri : substr($uri, 0, $inicio_variables_get);
                // Agregar slash inicial de la uri
                if(!isset($request) || (isset($request[0]) && $request[0] != '/')) {
                    $request = '/'.$request;
                }
                // Decodificar url
                $request = urldecode($request);
            }
            self::$_request = $request;
        }
        return self::$_request;
    }

    /**
     * Método que determina los campos base y webroot
     * @return string Base de la URL
     */
    public function base()
    {
        if (!isset(self::$_base)) {
            if (!isset($_SERVER['REQUEST_URI'])) {
                $base = '';
            } else {
                $parts = explode('?', urldecode($_SERVER['REQUEST_URI']));
                $last = strrpos($parts[0], $this->request());
                $base = $last !== false ? substr($parts[0], 0, $last) : $parts[0];
                $position = strlen($base)-1;
                if ($position >= 0 && $base[$position] == '/') {
                    $base = substr($base, 0, -1);
                }
            }
            self::$_base = $base;
        }
        return self::$_base;
    }

    /**
     * Método que determina la URL utiliza para acceder a la aplicación, esto
     * es: protocolo/esquema, dominio y path base a contar del webroot)
     * @return string URL completa para acceder a la la página
     */
    public function url()
    {
        if (!isset(self::$_url)) {
            if (empty($_SERVER['HTTP_HOST'])) {
                $url = (string)Configure::read('app.url');
            } else {
                if ($this->header('X-Forwarded-Proto') == 'https') {
                    $scheme = 'https';
                } else {
                    $scheme = 'http' . (isset($_SERVER['HTTPS']) ? 's' : null);
                }
                $url = $scheme . '://' . $_SERVER['HTTP_HOST'] . $this->base();
            }
            self::$_url = $url;
        }
        return self::$_url;
    }

    /**
     * Método que asigna o entrega los parámetros de la solicitud
     * @param array|null $params
     * @return array
     */
    public function params(): array
    {
        if (!isset(self::$_params)) {
            self::$_params = Routing_Router::parse($this->request());
        }
        return self::$_params;
    }

    /**
     * Método que entrega las cabeceras enviadas al servidor web por el cliente
     * @param string header Cabecera que se desea recuperar, o null si se quieren traer todas
     * @return mixed Arreglo si no se pidió por una específica o su valor si se pidió (=false si no existe cabecera, =null si no existe función apache_request_headers)
     */
    public function header($header = null)
    {
        $headers = $this->headers();
        if ($header === null) {
            return $headers;
        }
        if (isset($headers[$header])) {
            return $headers[$header];
        }
        return false;
    }

    /**
     * Método que entrega las cabeceras enviadas al servidor web por el cliente
     * @return array Arreglo con las cabeceras HTTP recibidas
     */
    public function headers()
    {
        if (!isset(self::$_headers)) {
            if (!function_exists('apache_request_headers')) {
                $headers = [];
            } else {
                $headers = apache_request_headers();
            }
            self::$_headers = $headers;
        }
        return self::$_headers;
    }

    /**
     * Método que indica si la solicitud HTTP es o no a la API.
     * Esto se determina de 2 formas:
     *   - La solicitud parte por /api/
     *   - La solicitud acepta como respuesta un JSON
     *
     * @return boolean
     */
    public function isApiRequest()
    {
        $api_prefix = strpos($this->request, '/api/') === 0;
        $accept_json = $this->header('Accept') == 'application/json'; // WARNING: podría retornar arreglo (?)
        return $api_prefix || $accept_json;
    }

}
