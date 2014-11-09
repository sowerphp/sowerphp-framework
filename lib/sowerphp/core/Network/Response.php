<?php

/**
 * SowerPHP: Minimalist Framework for PHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

namespace sowerphp\core;

/**
 * Clase para generar respuesta al cliente
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-05-01
 */
class Network_Response
{

    protected $_body = null; ///< Datos que se enviarán al cliente
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
    );

    /**
     * Método que envía cabeceras al cliente
     * @param header Cabecera
     * @param value Valor
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-09-14
     */
    public function header ($header, $value)
    {
        header($header.': '.$value);
    }

    /**
     * Método que asigna el cuerpo de la respuesta
     * @param content Contenito a asignar
     * @return Contenido de la respuesta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-09-14
     */
    public function body ($content = null)
    {
        if (is_null($content)) return $this->_body;
            return $this->_body = $content;
    }

    /**
     * Enviar cuerpo al cliente (lo escribe)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-02-24
     */
    public function send ()
    {
        echo $this->body();
    }

    /**
     * Enviar un archivo (estático) al cliente
     * @param file Archivo que se desea enviar al cliente o bien un arreglo con los campos: name, type, size y data
     * @param options Arreglo de opciones (indices: name, charset, disposition y exit)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-11-09
     */
    public function sendFile ($file, $options = array())
    {
        // opciones
        $options = array_merge(array(
            'charset' => 'utf-8',
            'disposition' => 'inline', // inline o attachement
            'exit' => 0,
            'cache' => 86400,
        ), $options);
        // si no es un arreglo se genera
        if(!is_array($file)) {
            $location = $file;
            $file = array();
            $file['name'] = isset($options['name']) ? $options['name'] : basename($location);
            $ext = substr(strrchr($file['name'], '.'), 1);
            $file['type'] = (isset(self::$_mimeTypes[$ext])?self::$_mimeTypes[$ext]:'application/octet-stream');
            $file['size'] = filesize($location);
            $file['data'] = fread(fopen($location, 'rb'), $file['size']);
        }
        // limpiar buffer salida
        ob_end_clean();
        // Enviar cabeceras para el archivo
        header('Cache-Control: max-age='.$options['cache']);
        header('Date: '.gmdate('D, d M Y H:i:s', time()).' GMT');
        header('Expires: '.gmdate('D, d M Y H:i:s', time()+$options['cache']).' GMT');
        header('Pragma: cache');
        header('Content-Type: '.$file['type'].'; charset='.$options['charset']);
        header('Content-Length: '.$file['size']);
        header('Content-Disposition: '.$options['disposition'].'; filename="'.$file['name'].'"');
        // Enviar cuerpo para el archivo
        if (is_resource($file['data'])) fpassthru($file['data']);
        else print $file['data'];
        // Terminar script
        if($options['exit']!==false) exit((integer)$options['exit']);
    }

    /**
     * Método estático para asignar un nuevo tipo mime a una extensión
     * @param ext Extensión
     * @param type Mimetype que se debe asociar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-09-23
     */
    public static function setMimetype ($ext, $type)
    {
        self::$_mimeTypes[$ext] = $type;
    }

    /**
     * Método estático para obtener un tipo mime de una extensión
     * @param ext Extensión
     * @return Mimetype correspondiente a la extensión
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-09-23
     */
    public static function getMimetype ($ext)
    {
        return self::$_mimeTypes[$ext];
    }

}
