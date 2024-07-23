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

use Illuminate\Http\Response;

/**
 * Clase para generar respuesta al cliente
 */
class Network_Response //extends Response
{

    /**
     * Tipos de datos mime de los archivos según su extensión.
     */
    private $mimeTypes = [
        'tar' => 'application/x-tar',
        'gz' => 'application/x-gzip',
        'zip' => 'application/zip',
        'js' => 'text/javascript',
        'css' => 'text/css',
        'csv' =>  'text/csv',
        'xml' => 'application/xml',
        'json' => 'application/json',
        'pdf' => 'application/pdf',
        'gif' => 'image/gif',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'xls' => 'application/vnd.ms-office',
        'xlsx' => 'application/octet-stream',
        'txt' => 'text/plain',
    ];

    /**
     * Datos que se utilizarán para enviar en la respuesta HTTP al cliente.
     */
    private $responseData = [
        'status' => 200,
        'headers' => [],
        'body' => '',
    ];

    /**
     * Asigna el código de estado de la respuesta HTTP que se enviará o lo
     * recupera.
     *
     * @param int|null $status Estado HTTP que se desea asignar a la respuesta.
     * @return int Código HTTP asignado (puede haber sido ajustado del original).
     */
    public function status(?int $status = null): int
    {
        if ($status !== null) {
            // Si no hay código (es 0), se asigna 200 por defecto.
            if ($status === 0) {
                $status = 200;
            }
            // Si el código es menor que 200, se asume que es un código de
            // error no estándar y se reasigna a 409, con el código original en
            // una cabecera.
            else if ($status < 200) {
                $this->header('X-Original-Error-Code', $status);
                // Error 409 (Conflict): Este código indica que hay un problema
                // con el estado de la solicitud o del recurso que impide su
                // procesamiento.
                $status = 409;
            }
            // Si el código es mayor o igual que 500 se cambia a un código del
            // rango 400, porque no se pueden devolver este tipo de códigos en
            // AWS (por el Health Status de los Load Balancer, ej en AWS EB).
            else if ($status >= 500) {
                $this->header('X-Original-Error-Code', $status);
                // Error 422 (Unprocessable Entity): Este código indica que el
                // servidor entiende el tipo de contenido de la solicitud, y la
                // sintaxis de la solicitud es correcta, pero no pudo procesar
                // las instrucciones contenidas.
                $status = 422;
            }
            // Asignar el código de estado HTTP pasado o determinado.
            $this->responseData['status'] = $status;
        }
        return $this->responseData['status'];
    }

    /**
     * Método que permite asignar el tipo de archivo al que corresponde la
     * respuesta o recuperar el tipo de la respuesta.
     *
     * @param string $mimetype Tipo de dato (mimetype).
     * @param string $charset Juego de caracteres o codificación.
     */
    public function type(?string $mimetype = null, ?string $charset = null): ?string
    {
        if ($mimetype !== null) {
            if ($charset !== null) {
                $this->responseData['headers']['Content-Type'] =
                    $mimetype . '; charset=' . $charset
                ;
            } else {
                $this->responseData['headers']['Content-Type'] = $mimetype;
            }
        }
        return $this->responseData['headers']['Content-Type'] ?? null;
    }

    /**
     * Método que permite asignar cabeceras al cliente o las recupera.
     *
     * @param string $header Cabecera que se desea asignar.
     * @param mixed $value Valor de la cabecera.
     * @return array Arreglo con las cabeceras que están asignadas.
     */
    public function header(?string $header = null, $value = null): array
    {
        if ($header !== null && $value !== null) {
            $this->responseData['headers'][$header] = $value;
        }
        return $this->responseData['headers'];
    }

    /**
     * Método que asigna el cuerpo de la respuesta o lo recupera.
     *
     * @param string $body Contenido a asignar al cuerpo.
     * @return string Contenido de la respuesta está asignado.
     */
    public function body(?string $body = ''): string
    {
        if ($body || $body === null) {
            $this->responseData['body'] = (string)$body;
        }
        return $this->responseData['body'];
    }

    /**
     * Método que entrega el tamaño de los datos que se entregarán como
     * respuesta.
     *
     * @return int Tamaño de los datos del cuerpo que se entregarán o -1.
     */
    public function length(): int
    {
        return strlen($this->responseData['body']);
    }

    /**
     * Enviar respuesta HTTP al cliente.
     * Escribe estado HTTP, cabeceras y cuerpo de la respuesta.
     *
     * @param string $body Contenido que se enviará, si no se asigna se enviará
     * el atributo $_body.
     */
    public function send(): int
    {
        // Enviar el código de estado HTTP de la respuesta.
        http_response_code($this->responseData['status']);
        // Enviar las cabeceras de la respuesta.
        foreach ($this->responseData['headers'] as $header => $value) {
            header($header . ': '. $value);
        }
        // Enviar el cuerpo de la respuesta.
        if ($this->responseData['body']) {
            echo $this->responseData['body'];
        }
        // Entregar respuesta para el manejador (y pasar al kernel).
        return $this->responseData['status'] < 400 ? 0 : 1;
    }

    /**
     * Preparar los datos de respuesta a partir de un archivo.
     *
     * El archivo puede ser:
     *   - Archivo estático del sistema de archivos.
     *   - Los datos en memoria de un archivo.
     *   - El acceso a un recurso (resource) abierto que deberá ser leído.
     * Se envía informando que se debe usar caché para este archivo.
     *
     * @param string|array $file Archivo que se desea enviar al cliente o bien
     * un arreglo con los campos: name, type, size y data.
     * @param array $options Arreglo de opciones. Índices: name, charset,
     * disposition y exit).
     */
    public function file($file, array $options = []): self
    {
        // Opciones del archivo para la respuesta.
        $options = array_merge([
            // Codificación del archivo.
            'charset' => 'utf-8',
            // inline o attachement.
            'disposition' => 'inline',
            // Segundos que se recomienda tener el archivo en caché.
            'cache' => 86400,
        ], $options);
        // Si el archivo que se pasó es la ruta se genera el arreglo con los
        // datos del archivo en el formato estándar de $_FILES.
        if (!is_array($file)) {
            $location = $file;
            $file = [];
            $file['name'] = isset($options['name'])
                ? $options['name']
                : basename($location)
            ;
            $extension = substr(
                $file['name'],
                strrpos($file['name'], '.') + 1
            );
            $file['type'] = $this->mimeTypes[$extension]
                ?? 'application/octet-stream'
            ;
            $file['size'] = filesize($location);
            $file['data'] = fread(
                fopen($location, 'rb'),
                $file['size']
            );
        }
        // Si los datos son un recurso se obtiene su contenido.
        if (is_resource($file['data'])) {
            $resource = $file['data'];
            rewind($resource);
            $file['data'] = stream_get_contents($resource);
            fclose($resource);
        }
        // Armar datos de la respuesta.
        $this->responseData['headers']['Content-Type'] =
            $file['type'] . '; charset=' . $options['charset']
        ;
        $this->responseData['headers']['Content-Disposition'] =
            $options['disposition'] . '; filename="' . $file['name'] . '"'
        ;
        $this->responseData['headers']['Cache-Control'] =
            'max-age=' . $options['cache']
        ;
        $this->responseData['headers']['Date'] =
            gmdate('D, d M Y H:i:s', time()).' GMT'
        ;
        $this->responseData['headers']['Expires'] =
            gmdate('D, d M Y H:i:s', time()+$options['cache']).' GMT'
        ;
        $this->responseData['headers']['Pragma'] ='cache';
        $this->responseData['headers']['Content-Length'] = $file['size'];
        $this->responseData['body'] = $file['data'];
        // Retornar objeto de la respuesta.
        return $this;
    }

    /**
     * Método que envía un contenido al navegador.
     *
     * El método está diseñado para enviar datos (ej: archivos) en memoria e
     * informando que no se use caché. Además una vez envía los datos termina
     * inmediatamente, un comportamiento que debe ser manejado de otra forma
     * en futuras versiones de esta clase.
     *
     * @param string $content Contenido en memoria que que se enviará.
     * @param string $filename Nombre del archivo que se enviará.
     * @param array $options Arreglo de opciones. Índices: mimetype, charset,
     * disposition y exit.
     */
    public function sendAndExit(
        ?string $content = null,
        ?string $filename = null,
        array $options = []
    )
    {
        // Determinar opciones por defecto.
        if (is_string($options)) {
            $aux = explode(';', $options);
            $options = ['mimetype' => $aux[0]];
            if (!empty($aux[1])) {
                $options['charset'] = $aux[1];
            }
        }
        $options = array_merge([
            'mimetype' => null,
            'charset' => null,
            'disposition' => 'attachement', // inline o attachement
        ], $options);
        if (empty($options['mimetype']) && $filename) {
            $extension = substr($filename, strrpos($filename, '.') + 1);
            $options['mimetype'] = $this->mimeTypes[$extension] ?? null;
        }
        // Asignar datos de la respuesta.
        if ($options['mimetype']) {
            if ($options['charset'] !== null) {
                $this->responseData['headers']['Content-Type'] =
                    $options['mimetype'] . '; charset=' . $options['charset']
                ;
            } else {
                $this->responseData['headers']['Content-Type'] =
                    $options['mimetype']
                ;
            }
        }
        if ($filename) {
            $this->responseData['headers']['Content-Disposition'] =
                $options['disposition'].'; filename=' . $filename
            ;
        }
        $this->responseData['headers']['Pragma'] = 'no-cache';
        $this->responseData['headers']['Expires'] = 0;
        if ($content) {
            $this->responseData['body'] = $content;
        }
        $this->send();
        exit(); // TODO: refactorizar para no cerrar acá pues detiene controlador.
    }

    /**
     * Envía una respuesta JSON.
     *
     * @param mixed $data Los datos a codificar como JSON.
     * @param int $status El código de estado HTTP.
     * @param array $headers Encabezados adicionales a enviar con la respuesta.
     * @param int $options Opciones de codificación JSON.
     * @return Network_Response
     */
    public function json(
        $data = [],
        int $status = 200,
        array $headers = [],
        int $options = 0
    ): self
    {
        // Armar respuesta HTTP JSON.
        $this->status($status);
        if (!$this->type()) {
            $this->type('application/json', 'utf-8');
        }
        foreach ($headers as $header => $value) {
            $this->header($header, $value);
        }
        $this->body(json_encode($data, $options) . "\n");
        // Retornar objeto de la respuesta.
        return $this;
    }

    /**
     * Envía una respuesta JSON con una excepción que se haya atrapado.
     *
     * @param \Exception $e Excepción que se desea enviar como respuesta.
     * @return Network_Response
     */
    public function jsonException(\Exception $e): self
    {
        // Asignar valores predeterminados para la respuesta JSON  basada en
        // una excepción.
        $status_code = $e->getCode() ?: 500;
        $body = [
            'status_code' => $status_code,
            'message' => $e->getMessage(),
        ];
        // Agregar datos que son para ambiente no productivo.
        if (config('app.env') != 'production') {
            $body['trace'] = array_filter(array_map(function($caller) {
                $file = $caller['file'] ?? null;
                if ($file === null) {
                    return null;
                }
                return [
                    'file' => app('layers')->obfuscatePath($file),
                    'line' => $caller['line'] ?? null,
                ];
            }, $e->getTrace()), function($trace) {
                return $trace !== null;
            });
        }
        // Entregar respuesta JSON de la excepción.
        return $this->json($body, $status_code);
    }

}
