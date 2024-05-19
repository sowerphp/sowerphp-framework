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
 * Clase para generar respuesta al cliente
 */
class Network_Response
{

    protected static $_mimeTypes = array( ///< Tipos de datos mime
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
    );

    // datos a enviar en la respuesta
    protected $_status = null;
    protected $_type = [];
    protected $_headers = [];
    protected $_body = null; ///< Datos que se enviarán al cliente

    /**
     * Asigna código de estado de la respuesta HTTP
     * @param status Estado que se desea asignar
     */
    public function status($status = null)
    {
        if ($status !== null) {
            $this->_status = $status;
        }
        return $this->_status;
    }

    /**
     * Método que permite asignar el tipo de archivo al que corresponde la
     * respuesta
     * @param mimetype Tipo de dato (mimetype)
     * @param charset Juego de caracteres o codificación
     */
    public function type($mimetype = null, $charset = null)
    {
        if ($mimetype !== null) {
            $this->_type = [
                'mimetype' => $mimetype,
                'charset' => $charset,
            ];
        }
        return $this->_type;
    }

    /**
     * Método que permite asignar cabeceras al cliente
     * @param header Cabecera
     * @param value Valor
     */
    public function header($header = null, $value = null)
    {
        if ($header !== null && $value !== null) {
            $this->_headers[] = $header.': '.$value;
        }
        return $this->_headers;
    }

    /**
     * Método que asigna el cuerpo de la respuesta
     * @param content Contenito a asignar
     * @return string Contenido de la respuesta
     */
    public function body($body = null)
    {
        if ($body !== null) {
            $this->_body = $body;
        }
        return $this->_body;
    }

    /**
     * Método que entrega el tamaño de los datos que se entregarán como respuesta
     * @return int Tamaño de los datos del cuerpo que se entregarán o -1
     */
    public function length()
    {
        return $this->_body !== null ? strlen($this->_body) : -1;
    }

    /**
     * Enviar respuesta al cliente (escribe estado HTTP, cabecera y cuerpo de la respuesta)
     * @param body Contenido que se enviará, si no se asigna se enviará el atributo $_body
     * @param exit Estado de salida del envío de datos
     */
    public function send($body = null, $exit = 0)
    {
        // enviar estado HTTP
        if ($this->_status !== null) {
            http_response_code($this->_status);
        }
        // agregar tipo de respuesta a las cabeceras
        if (!empty($this->_type['mimetype'])) {
            if (!empty($this->_type['charset'])) {
                $this->header('Content-Type', $this->_type['mimetype'].'; charset='.$this->_type['charset']);
            } else {
                $this->header('Content-Type', $this->_type['mimetype']);
            }
        }
        // enviar cabeceras de la respuesta
        foreach ($this->_headers as $header) {
            header($header);
        }
        // enviar cuerpo de la respuesta
        if ($body !== null) {
            echo $body;
        } else {
            echo $this->_body;
        }
        // terminar el script
        if ($exit !== false) {
            exit((integer)$exit); // este debería ser el único exit en la app
        }
    }

    /**
     * Enviar un archivo (estático) al cliente
     * Envía un archivo existente en el sistema de archivos o bien desde un
     * recurso abierto. Se envía informando que se debe usar caché para este archivo
     * @param file Archivo que se desea enviar al cliente o bien un arreglo con los campos: name, type, size y data
     * @param options Arreglo de opciones (indices: name, charset, disposition y exit)
     */
    public function sendFile($file, $options = [])
    {
        // opciones
        $options = array_merge([
            'charset' => 'utf-8',
            'disposition' => 'inline', // inline o attachement
            'exit' => 0,
            'cache' => 86400, // segundos que el archivo se recomienda tener en caché
        ], $options);
        // si no es un arreglo se genera
        if (!is_array($file)) {
            $location = $file;
            $file = [];
            $file['name'] = isset($options['name']) ? $options['name'] : basename($location);
            $ext = substr(strrchr($file['name'], '.'), 1);
            $file['type'] = (isset(self::$_mimeTypes[$ext])?self::$_mimeTypes[$ext]:'application/octet-stream');
            $file['size'] = filesize($location);
            $file['data'] = fread(fopen($location, 'rb'), $file['size']);
        }
        // si los datos son un recurso se obtiene su contenido
        if (is_resource($file['data'])) {
            $resource = $file['data'];
            rewind($resource);
            $file['data'] = stream_get_contents($resource);
            fclose($resource);
        }
        // limpiar buffer salida
        ob_end_clean();
        // crear cabeceras para el archivo
        $this->type($file['type'], $options['charset']);
        $this->header('Content-Length', $file['size']);
        $this->header('Content-Disposition', $options['disposition'].'; filename="'.$file['name'].'"');
        $this->header('Cache-Control', 'max-age='.$options['cache']);
        $this->header('Date', gmdate('D, d M Y H:i:s', time()).' GMT');
        $this->header('Expires', gmdate('D, d M Y H:i:s', time()+$options['cache']).' GMT');
        $this->header('Pragma', 'cache');
        // enviar archivo
        $this->send($file['data'], $options['exit']);
    }

    /**
     * Método que envía un contenido al navegador, la ventaja es que está diseñado
     * para enviar archivos en memoria e informando que no se use caché
     * @param content Contenido en memoria del archivo que se enviará
     * @param options Arreglo de opciones (indices: mimetype, charset, disposition y exit)
     */
    public function sendContent($content, $filename, $options = [])
    {
        // opciones
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
            'exit' => 0,
        ], $options);
        if (empty($options['mimetype'])) {
            $options['mimetype'] = self::getMimetype(substr($filename, strrpos($filename, '.')+1));
        }
        if ($options['mimetype']) {
            $this->type($options['mimetype'], $options['charset']);
        }
        $this->header('Content-Disposition', $options['disposition'].'; filename='.$filename);
        $this->header('Pragma', 'no-cache');
        $this->header('Expires', 0);
        $this->send($content, $options['exit']);
    }

    /**
     * Método estático para asignar un nuevo tipo mime a una extensión
     * @param ext Extensión
     * @param type Mimetype que se debe asociar
     */
    public static function setMimetype($ext, $type)
    {
        self::$_mimeTypes[$ext] = $type;
    }

    /**
     * Método estático para obtener un tipo mime de una extensión
     * @param ext Extensión
     * @return string Mimetype correspondiente a la extensión
     */
    public static function getMimetype($ext)
    {
        return !empty(self::$_mimeTypes[$ext]) ? self::$_mimeTypes[$ext] : null;
    }

}
