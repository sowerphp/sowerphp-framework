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
 * Clase para trabajar con conexiones FTP
 * @author Esteban De la Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2015-06-16
 */
class Network_Ftp
{

    private $config; ///< Configuración de la conexión FTP
    private $link; ///< Enlace a la conexión FTP

    /**
     * Constructor de la clase para conexiones FTP
     * @param config Configuración para el servidor FTP (índices: host, port, timeout, user, pass y passive)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-06-16
     */
    public function __construct($config = [])
    {
        // definir configuración
        if (is_string($config))
            $config = ['host'=>$config];
        $this->config = array_merge([
            'host' => 'localhost',
            'port' => 21,
            'timeout' => 90,
            'user' => 'anonymous',
            'pass' => null,
            'passive' => true,
        ], $config);
        $this->link = ftp_connect($this->config['host'], $this->config['port'], $this->config['timeout']);
        if ($this->link) {
            if ($this->config['user']) {
                $login = $this->login($this->config['user'], $this->config['pass']);
                if ($login and $this->config['passive']) {
                    $this->pasv($this->config['passive']);
                }
            }
        }
    }

    /**
     * Destructor de la clase para conexiones FTP
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-06-16
     */
    public function __destruct()
    {
        $this->close();
    }


    /**
     * Método que permite obtener los archivos de un directorio
     * @param dir Directorio que se desea escanear
     * @return Arreglo con los archivos agrupados por tipo (ej: d y -)
     * @warning Método sólo funciona con sistemas like Unix
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-06-16
     */
    public function scandir($dir = '/')
    {
        // método sólo funciona con FTP en sistemas like Unix
        if ($this->systype()!='UNIX') {
            throw new Exception(
                'Método '.__CLASS__.'::scandir() sólo funciona con sistemas like Unix'
            );
        }
        // obtener archivos
        $files = [];
        $aux = $this->rawlist($dir);
        foreach ($aux as $f) {
            $info = preg_split('/[\s]+/', $f, 9);
            if (!isset($files[$info[0][0]]))
                $files[$info[0][0]] = [];
            $files[$info[0][0]][] = [
                'name' => $info[8],
                'size' => $info[4],
                'type' => $info[0][0],
                'perm' => substr($info[0], 1),
                'date' => $info[6].' '.$info[5].' '.$info[7],
            ];
        }
        return $files;
    }

    /**
     * Método que entrega la URI del servidor FTP
     * @return URI completa del servidor FTP
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-06-16
     */
    public function getUri()
    {
        $auth = $this->config['user'] ? $this->config['user'].':'.$this->config['pass'].'@' : '';
        return 'ftp://'.$auth.$this->config['host'].':'.$this->config['port'];
    }

    /**
     * Método mágico para ejecutar funciones ftp_*
     * @return URI completa del servidor FTP
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-06-16
     */
    public function __call($func, $args)
    {
        // verificar que la función exista
        if (!function_exists('ftp_'.$func)) {
            throw new Exception_Object_Method_Missing([
                'class' => __CLASS__,
                'method' => $func,
            ]);
        }
        // ejecutar functión FTP correspondiente
        array_unshift($args, $this->link);
        return call_user_func_array('ftp_'.$func, $args);
    }

}
