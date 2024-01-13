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
 * Utilidad para realizar operaciones sobre datos:
 *  - Encriptar y desencriptar
 *  - Sanitizar/limpiar datos
 */
class Utility_Data
{

    protected static $default_crypt_method = 'sodium'; ///< método por defecto

    /**
     * Método que entrega el método de encriptación que se debe usar
     * @param crypt_method Método forzado si se desea usar uno en específico
     * @return string String con el nombre del método de encriptación
     */
    protected static function getCryptMethod($crypt_method = null)
    {
        // buscar método de encriptación solicitado
        if ($crypt_method) {
            $crypt_method = $crypt_method;
        } else {
            $config_crypt_method = Configure::read('data.crypt.method');
            if ($config_crypt_method) {
                $crypt_method = $config_crypt_method;
            } else {
                $crypt_method = self::$default_crypt_method;
            }
        }
        // verificar que el método solicitado exista
        if (!method_exists('Utility_Data', $crypt_method.'_encrypt') || !method_exists('Utility_Data', $crypt_method.'_decrypt')) {
            throw new \Exception(__('Método de encriptado %s no disponible en Utility_Data', $crypt_method));
        }
        // entregar método de encriptación
        return $crypt_method;
    }

    /**
     * Método que encripta un texto plano
     * @param plaintext Texto plano a encriptar
     * @param key Clave a usar para encriptar
     * @return string Texto encriptado en base64
     */
    public static function encrypt($plaintext, $key = null, $crypt_method = null)
    {
        if (!$key) {
            return base64_encode($plaintext);
        }
        $method = self::getCryptMethod($crypt_method).'_encrypt';
        return base64_encode(self::$method($plaintext, $key));
    }

    /**
     * Método que desencripta un texto encriptado
     * @param $ciphertext_base64 Texto encriptado en base64 a desencriptar
     * @param key Clave a uar para desencriptar
     * @return string Texto plano
     */
    public static function decrypt($ciphertext_base64, $key = null, $crypt_method = null)
    {
        if (empty($ciphertext_base64)) {
            return $ciphertext_base64;
        }
        $ciphertext_dec = base64_decode($ciphertext_base64);
        if ($ciphertext_dec === false) {
            throw new \Exception(__('Error al usar base64_decode() en el mensaje cifrado.'));
        }
        if (!$key) {
            return $ciphertext_dec;
        }
        $method = self::getCryptMethod($crypt_method).'_decrypt';
        return self::$method($ciphertext_dec, $key);
    }

    /**
     * Método que encripta un texto plano usando sodium
     * @link https://stackoverflow.com/a/52688846
     */
    private static function sodium_encrypt($plaintext, $key)
    {
        // verificar que exista la función de mcrypt
        if (!function_exists('sodium_crypto_secretbox')) {
            throw new \Exception(__('La extensión %s de PHP no está disponible.', 'sodium'));
        }
        // encriptar
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext_dec = $nonce.sodium_crypto_secretbox($plaintext, $nonce, $key);
        sodium_memzero($plaintext);
        sodium_memzero($key);
        return $ciphertext_dec;
    }

    /**
     * Método que desencripta un texto encriptado usando sodium
     * @link https://stackoverflow.com/a/52688846
     */
    private static function sodium_decrypt($ciphertext_dec, $key)
    {
        // verificar que exista la función de mcrypt
        if (!function_exists('sodium_crypto_secretbox_open')) {
            throw new \Exception(__('La extensión %s de PHP no está disponible.', 'sodium'));
        }
        // desencriptar
        if (mb_strlen($ciphertext_dec, '8bit') < (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
            throw new \Exception('El mensaje cifrado está truncado, no es válido');
        }
        $nonce = mb_substr($ciphertext_dec, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($ciphertext_dec, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
        if ($plaintext === false) {
            throw new \Exception('El mensaje fue manipulado después de ser encriptado, no es válido.');
        }
        sodium_memzero($ciphertext);
        sodium_memzero($key);
        return $plaintext;
    }

    /**
     * Método que encripta un texto plano usando mcrypt
     * @deprecated https://www.php.net/manual/es/migration71.deprecated.php
     */
    private static function mcrypt_encrypt($plaintext, $key)
    {
        if (mb_strlen($key, '8bit') !== 32) {
            throw new \Exception(__('Se requiere una llave de 256 bits (32 caracteres si es llave ASCII).'));
        }
        // verificar que exista la función de mcrypt
        if (!function_exists('mcrypt_encrypt')) {
            throw new \Exception(__('La extensión %s de PHP no está disponible.', 'mcrypt'));
        }
        // encriptar
        $iv_size = @mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
        $iv = @mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $ciphertext = @mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $plaintext, MCRYPT_MODE_CBC, $iv);
        $ciphertext_dec = $iv.$ciphertext;
        return $ciphertext_dec;
    }

    /**
     * Método que desencripta un texto encriptado usando mcrypt
     * @deprecated https://www.php.net/manual/es/migration71.deprecated.php
     */
    private static function mcrypt_decrypt($ciphertext_dec, $key)
    {
        if (mb_strlen($key, '8bit') !== 32) {
            throw new \Exception(__('Se requiere una llave de 256 bits (32 caracteres si es llave ASCII).'));
        }
        // verificar que exista la función de mcrypt
        if (!function_exists('mcrypt_decrypt')) {
            throw new \Exception(__('La extensión %s de PHP no está disponible.', 'mcrypt'));
        }
        // desencriptar
        $iv_size = @mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
        $iv_dec = substr($ciphertext_dec, 0, $iv_size);
        $ciphertext_dec = substr($ciphertext_dec, $iv_size);
        $plaintext = @mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
        return $plaintext;
    }

    /**
     * Método que limpia datos de tipo texto (string)
     * @param data Datos que se desean limpiar, puede ser un arreglo con los datos
     * @param options Opciones para la limpieza de los datos
     */
    public static function sanitize(&$data, array $options = [])
    {
        if (is_array($data)) {
            foreach ($data as &$d) {
                $d = self::sanitize($d);
            }
            return $data;
        }
        if (!$data || !is_string($data) || is_numeric($data)) {
            return $data;
        }
        if (!empty($options['tags'])) {
            $data = trim(strip_tags($data, $options['tags']));
        } else {
            $data = trim(strip_tags($data));
        }
        if (!empty($options['l'])) {
            $data = substr($data, 0, $options['l']);
        }
        return $data;
    }

    /**
     * Método que obtiene los correos electrónicos desde un string
     * @param string String con el listado de correos
     * @return array Listado de correos que hay en el string
     */
    public static function emails($listado)
    {
        if (!is_array($listado)) {
            $listado = array_filter(
                array_unique(
                    array_map('trim', explode(';', str_replace("\n", ';', $listado)))
                )
            );
        }
        $emails = [];
        foreach ($listado as $e) {
            if (\sowerphp\core\Utility_Data_Validation::check($e, ['notempty', 'email'])) {
                $emails[] = $e;
            }
        }
        return $emails;
    }

}
