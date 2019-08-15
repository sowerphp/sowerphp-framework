<?php

/**
 * SowerPHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
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
 * Clase para realizar la transferencia de archivos entre servidores de manera
 * fácil
 * @author Esteban De la Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2019-08-14
 */
class Network_Transfer
{

    protected $config = []; ///< Configuración del servidor

    /**
     * Constructor de la clase de transferencias
     * @author Esteban De la Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-08-14
     */
    public function __construct($uri)
    {
        // configuración del servidor
        $this->config['scheme'] = parse_url($uri, PHP_URL_SCHEME);
        if (empty($this->config['scheme'])) {
            throw new \Exception(__('Debe especificar el método de transferencia (ej: sftp:// o ftp://)'));
        }
        $this->config['user'] = parse_url($uri, PHP_URL_USER);
        $this->config['pass'] = parse_url($uri, PHP_URL_PASS);
        $this->config['host'] = parse_url($uri, PHP_URL_HOST);
        $this->config['port'] = parse_url($uri, PHP_URL_PORT);
        if (empty($this->config['port'])) {
            unset($this->config['port']);
        }
        $this->config['path'] = parse_url($uri, PHP_URL_PATH);
    }

    /**
     * Método que envía un archivo a un servidor según su URI
     * @author Esteban De la Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-08-14
     */
    public function send($local_file, $remote_file)
    {
        // verificar que existe el método para el esquema solicitado
        $method = 'send_'.$this->config['scheme'];
        if (!method_exists($this, $method)) {
            throw new \Exception(__('Método de envío usando '.strtoupper($this->config['scheme']).' no está disponible'));
        }
        // enviar el archivo
        $this->$method($local_file, $remote_file);
    }

    /**
     * Método que envía un archivo a un servidor SFTP
     * Requiere tener instalado en el sistema operativo: php-ssh2
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-08-14
     */
    protected function send_sftp($local_file, $remote_file)
    {
        // configuración por defecto mínima
        $config = array_merge([
            'port' => 22,
        ], $this->config);
        if (empty($config['host'])) {
            throw new \Exception(__('No se especificó el host del servidor SFTP'));
        }
        if (empty($config['user'])) {
            throw new \Exception(__('No se especificó el usuario del servidor SFTP'));
        }
        if (empty($config['path'])) {
            throw new \Exception(__('No se especificó la ruta en el servidor SFTP'));
        }
        // conectar al servidor SSH
        $connection = ssh2_connect($config['host'], $config['port']);
        if (!$connection) {
            throw new \Exception(__('No fue posible conectar al servidor SFTP en %s:%d', $config['host'], $config['port']));
        }
        // autenticar con usuario/contraseña
        if (!empty($config['pass'])) {
            if (!@ssh2_auth_password($connection, $config['user'], $config['pass'])) {
                throw new \Exception(__('No fue posible autenticar con usuario y contraseña: %s', error_get_last()['message']));
            }
        }
        // autenticar con clave pública
        else {
            $pubkey = '/home/'.get_current_user().'/.ssh/id_rsa.pub';
            $prikey = '/home/'.get_current_user().'/.ssh/id_rsa';
            if (!is_readable($pubkey) or !is_readable($prikey)) {
                throw new \Exception(__('No se especificó contraseña para el usuario %s y no se encontró clave pública para autenticar (o no se puede leer)', $config['user']));
            }
            if (!@ssh2_auth_pubkey_file($connection, $config['user'], $pubkey, $prikey)) {
                throw new \Exception(__('No fue posible autenticar con clave pública: %s', error_get_last()['message']));
            }
        }
        // crear conexión SFTP
        $sftp = ssh2_sftp($connection);
        // crear ruta en caso que no exista (se crea recursivamente)
        if (!@ssh2_sftp_stat($sftp, $config['path'])) {
            if (!@ssh2_sftp_mkdir($sftp, $config['path'], 0700, true)) {
                throw new \Exception(__('No fue posible crear el directorio para guardar el archivo: %s', error_get_last()['message']));
            }
        }
        // copiar archivo al servidor remoto
        if (!ssh2_scp_send($connection, $local_file, $config['path'].'/'.$remote_file)) {
            throw new \Exception(__('No fue posible copiar el archivo al servidor remoto en %s/%s: %s', $config['path'], $remote_file, error_get_last()['message']));
        }
        // cerrar conexión
        //ssh2_disconnect($connection); // BUG genera segfault: https://bugs.php.net/bug.php?id=73438
    }

    /**
     * Método que envía un archivo a un servidor FTP con SSL
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-08-14
     */
    protected function send_ftps($local_file, $remote_file)
    {
        $config = array_merge([
            'port' => 990,
        ], $this->config);
        return $this->send_ftp($local_file, $remote_file);
    }

    /**
     * Método que envía un archivo a un servidor FTP
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-08-14
     */
    protected function send_ftp($local_file, $remote_file)
    {
        // configuración por defecto mínima
        $config = array_merge([
            'port' => 21,
        ], $this->config);
        if (empty($config['host'])) {
            throw new \Exception(__('No se especificó el host del servidor FTP'));
        }
        if (empty($config['user'])) {
            throw new \Exception(__('No se especificó el usuario del servidor FTP'));
        }
        if (empty($config['pass'])) {
            throw new \Exception(__('No se especificó la contraseña del servidor FTP'));
        }
        if (empty($config['path'])) {
            throw new \Exception(__('No se especificó la ruta en el servidor FTP'));
        }
        // conectar al servidor FTP
        if ($config['scheme']=='ftp') {
            $connection = ftp_connect($config['host'], $config['port']);
        } else {
            $connection = ftp_ssl_connect($config['host'], $config['port']);
        }
        if (!$connection) {
            throw new \Exception(__('No fue posible conectar al servidor FTP en %s:%d', $config['host'], $config['port']));
        }
        // autenticar con usuario/contraseña
        if (!@ftp_login($connection, $config['user'], $config['pass'])) {
            throw new \Exception(__('No fue posible autenticar con usuario y contraseña: %s', error_get_last()['message']));
        }
        // crear ruta en caso que no exista (se crea recursivamente)
        if (!@ftp_chdir($connection, $config['path'])) {
            if (!@ftp_mkdir($connection, $config['path'])) {
                throw new \Exception(__('No fue posible crear el directorio para guardar el archivo: %s', error_get_last()['message']));
            }
        }
        // copiar archivo al servidor remoto
        if (!@ftp_put($connection, $config['path'].'/'.$remote_file, $local_file, FTP_BINARY)) {
            throw new \Exception(__('No fue posible copiar el archivo al servidor remoto en %s/%s: %s', $config['path'], $remote_file, error_get_last()['message']));
        }
        // cerrar conexión
        ftp_close($connection);
    }

}
