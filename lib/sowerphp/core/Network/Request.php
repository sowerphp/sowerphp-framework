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
 * Clase con la solicitud del cliente
 * @author Esteban De la Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-04-22
 */
class Network_Request
{

    public $request = null; ///< URI usada para la consulta (desde la aplicacion, o sea, sin base, iniciando con "/")
    public $base = null; ///< Ruta base de la URL (base + uri arma el total del request)
    public $url = null; ///< URL completa, partiendo desde HTTP o HTTPS según corresponda
    public $params = null; ///< Parámetros pasados que definen que ejecutar

    /**
     * Constructor de la clase
     * @author Esteban De la Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-19
     */
    public function __construct ($setRequest = true)
    {
        if ($setRequest && isset($_SERVER['QUERY_STRING'])) {
            // asignar datos de la solicitud
            $this->request();
            $this->base();
            $this->url();
            if (!defined('_URL')) {
                define ('_REQUEST', $this->request);
                define ('_BASE', $this->base);
                define ('_URL', $this->url);
            }
            // Quitar de lo pasado por get lo que se está solicitando
            unset($_GET[$this->request]);
        }
    }

    /**
     * Método que determina la solicitud utilizada para acceder a la página
     * @return Solicitud completa para la página consultada
     * @author Esteban De la Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-10
     */
    public function request ()
    {
        // Obtener ruta que se uso sin "/" (base) inicial
        $uri = substr($_SERVER['QUERY_STRING'], 1);
        // verificar si se pasaron variables GET
        $pregunta = strpos($uri, '&');
        // Asignar uri
        $this->request = $pregunta===false ? $uri : substr($uri, 0, $pregunta);
        // Agregar slash inicial de la uri
        if(!isset($this->request) || (isset($this->request[0])&&$this->request[0]!='/')) {
            $this->request = '/'.$this->request;
        }
        // Decodificar url
        $this->request = urldecode($this->request);
        return $this->request;
    }

    /**
     * Método que determina los campos base y webroot
     * @return Base de la URL
     * @author Esteban De la Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-09-23
     */
    public function base ()
    {
        if (!isset($_SERVER['REQUEST_URI']))
            return null;
        $parts = explode('?', urldecode($_SERVER['REQUEST_URI']));
        $last = strrpos($parts[0], $this->request);
        $this->base = $last!==FALSE ? substr($parts[0], 0, $last) : $parts[0];
        $pos = strlen($this->base)-1;
        if($pos>=0 && $this->base[$pos] == '/')
            $this->base = substr($this->base, 0, -1);
        return $this->base;
    }

    /**
     * Método que determina la URL utiliza para acceder a la aplicación, esto
     * es: protocolo, dominio y path base
     * contar del webroot)
     * @return URL completa para acceder a la la página
     * @author Esteban De la Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-11
     */
    public function url ()
    {
        if (!$this->base)
            $this->base();
        $this->url = 'http'.(isset($_SERVER['HTTPS'])?'s':null).'://'.$_SERVER['HTTP_HOST'].$this->base;
        return $this->url;
    }

    /**
     * Método que entrega las cabeceras enviadas al servidor web por el cliente
     * @param header Cabecera que se desea recuperar, o null si se quieren traer todas
     * @return Arreglo si no se pidió por una específica o su valor si se pidió (=false si no existe cabecera, =null si no existe función apache_request_headers)
     * @author Esteban De la Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-04-25
     */
    public function header ($header = null)
    {
        if (!function_exists('apache_request_headers'))
            return null;
        $headers = apache_request_headers();
        if ($header) {
            if (isset($headers[$header]))
                return $headers[$header];
            return false;
        }
        return $headers;
    }

}
