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
 * Clase para manejar conexiones HTTP.
 */
class Network_Http_Socket
{

    ///< Métodos HTTP soportados
    protected static $methods = ['get', 'put', 'patch', 'delete', 'post'];

    // Cabeceras por defecto.
    protected static $header = [
        'User-Agent' => 'SowerPHP Network_Http_Socket',
        //'Content-Type' => 'application/x-www-form-urlencoded',
    ];

    // Arrglo para errores de cURL.
    protected static $errors = [];

    /**
     * Método para ejecutar una solicitud a una URL, es la función que
     * realmente contiene las implementaciones para ejecutar GET, POST,
     * PUT, DELETE, etc.
     * @param string $method Método HTTP que se requiere ejecutar sobre la URL.
     * @param string $url URL donde se enviarán los datos.
     * @param mixed $data Datos que se enviarán.
     * @param array $header Cabecera que se enviará.
     * @param bool $sslv3 =true se fuerza sslv3, por defecto es false.
     * @return array Arreglo con la respuesta HTTP (cabecera y cuerpo).
     */
    public static function __callStatic($method, $args)
    {
        if (!isset($args[0]) || !in_array($method, self::$methods)) {
            return false;
        }
        $method = strtoupper($method);
        $url = $args[0];
        $data = isset($args[1]) ? $args[1] : [];
        $header = isset($args[2]) ? $args[2] : [];
        $sslv3 = isset($args[3]) ? $args[3] : false;
        $sslcheck = isset($args[4]) ? $args[4] : true;
        $debug = isset($args[5]) ? $args[5] : false;
        // inicializar curl
        $curl = curl_init();
        // asignar método y datos dependiendo de si es GET u otro método
        if ($method == 'GET') {
            if (is_array($data)) {
                $data = http_build_query($data);
            }
            if ($data) {
                $url = sprintf("%s?%s", $url, $data);
            }
        } else {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        }
        // asignar cabeceras de la solicitud HTTP
        $headers = [];
        $header = array_merge(self::$header, $header);
        foreach ($header as $key => $values) {
            if (!is_array($values)) {
                $values = [$values];
            }
            foreach ($values as $value) {
                $headers[] = $key.': '.$value;
            }
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        // realizar consulta a curl recuperando cabecera y cuerpo
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $sslcheck);
        if ($sslv3) {
            curl_setopt($curl, CURLOPT_SSLVERSION, 3);
        }
        if ($debug) {
            curl_setopt($curl, CURLOPT_VERBOSE, 1);
            curl_setopt($curl, CURLOPT_STDERR, $debug_fd = tmpfile());
        }
        $response = curl_exec($curl);
        if ($debug) {
            fseek($debug_fd, 0);
            self::$errors[] = 'Verbose de cURL en Socket::'.$method.'():'."\n".stream_get_contents($debug_fd);
            fclose($debug_fd);
        }
        if (!$response) {
            self::$errors[] = curl_error($curl);
            return false;
        }
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        // cerrar conexión de curl y entregar respuesta de la solicitud
        $header = self::parseHeader(substr($response, 0, $header_size));
        curl_close($curl);
        return [
            'status' => self::parseStatus($header[0]),
            'header' => $header,
            'body' => substr($response, $header_size),
        ];
    }

    /**
     * Método que procesa la cabecera en texto plano y la convierte a un
     * arreglo con los nombres de la cabecera como índices y sus valores.
     * Si una cabecera aparece más de una vez, por tener varios valores,
     * entonces dicha cabecerá tendrá como valor un arreglo con todos sus
     * valores.
     * @param string $header Cabecera HTTP en texto plano.
     * @return array Arreglo asociativo con la cabecera.
     */
    private static function parseHeader(string $header): array
    {
        $headers = [];
        $lineas = explode("\n", $header);
        foreach ($lineas as &$linea) {
            $linea = trim($linea);
            if (!isset($linea[0])) {
                continue;
            }
            if (strpos($linea, ':')) {
                list($key, $value) = explode(':', $linea, 2);
            } else {
                $key = 0;
                $value = $linea;
            }
            $key = trim($key);
            $value = trim($value);
            if (!isset($headers[$key])) {
                $headers[$key] = $value;
            } else if (!is_array($headers[$key])) {
                $aux = $headers[$key];
                $headers[$key] = [$aux, $value];
            } else {
                $headers[$key][] = $value;
            }
        }
        return $headers;
    }

    /**
     * Método que procesa la línea de respuesta y extrae el protocolo,
     * código de estado y el mensaje del estado.
     * @param string $response_line
     * @return array Arreglo con índices: protocol, code, message.
     */
    private static function parseStatus($response_line): array
    {
        if (is_array($response_line)) {
            $response_line = $response_line[count($response_line)-1];
        }
        $parts = explode(' ', $response_line, 3);
        return [
            'protocol' => $parts[0],
            'code' => $parts[1],
            'message' => !empty($parts[2]) ? $parts[2] : null,
        ];
    }

    /**
     * Método que entrega los errores ocurridos.
     * @return array Arreglo con los strings de los errores de cURL.
     */
    public static function getErrors(): array
    {
        return self::$errors;
    }

    /**
     * Método que entrega el último error de cURL.
     * @return array Arreglo con los strings de los errores de cURL.
     */
    public static function getLastError(): string
    {
        return self::$errors[count(self::$errors)-1];
    }

}
