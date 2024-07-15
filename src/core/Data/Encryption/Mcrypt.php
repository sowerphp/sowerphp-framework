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

/**
 * Cipher con mcrypt.
 *
 * El método de encriptación (cipher) mcrypt NO debe ser usado, jamás. Exite en
 * en SowerPHP solo por compatibilida con proyectos antiguos que lo usen que
 * aun no hayan migrado a un método seguro: sodium o aes.
 *
 * @warning Obsoleto: https://wiki.php.net/rfc/mcrypt-viking-funeral
 * @deprecated https://www.php.net/manual/es/migration71.deprecated.php
 */
class Data_Encryption_Mcrypt extends Data_Encryption
{

    /**
     * Constructor del encriptador usando mcrypt.
     *
     * @param string $key Clave de encriptación que se utilizará.
     * @param string $cipher Siempre debe ser 'mcrypt'. Parámetro se deja por
     * compatibilidad con la clase Illuminate\Encryption\Encrypter.
     */
    public function __construct(string $key, string $cipher = 'mcrypt')
    {
        // Verificar que exista la función de mcrypt.
        if (!function_exists('mcrypt_encrypt')) {
            throw new \Exception(__(
                'La extensión %s de PHP no está disponible.',
                'mcrypt'
            ));
        }
        // Verificar largo de la clave.
        if (mb_strlen($key, '8bit') !== 32) {
            throw new \Exception(__(
                'Se requiere una llave de 256 bits (32 caracteres si es llave ASCII).'
            ));
        }
        // Llamar al constructor de la clase padre.
        parent::__construct($key, $cipher);
    }

    /**
     * Método que encripta datos usando mcrypt.
     */
    public function encrypt($value, $serialize = true): string
    {
        // Quitar espacios del string (si es string).
        if (is_string($value)) {
            $value = trim($value);
        }
        // Serializar si es necesario.
        if ($serialize && is_serializable($value)) {
            $value = serialize($value);
        }
        // Encriptar.
        $iv_size = @mcrypt_get_iv_size(
            MCRYPT_RIJNDAEL_256,
            MCRYPT_MODE_CBC
        );
        $iv = @mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $ciphertext = @mcrypt_encrypt(
            MCRYPT_RIJNDAEL_256,
            $this->key,
            $value,
            MCRYPT_MODE_CBC,
            $iv
        );
        $ciphertext_dec = $iv . $ciphertext;
        return base64_encode($ciphertext_dec);
    }

    /**
     * Método que desencripta datos encriptados usando mcrypt.
     */
    public function decrypt($payload, $unserialize = true)
    {
        // Decodificar.
        $payload = base64_decode($payload);
        if ($payload === false) {
            throw new \Exception(__(
                'Error al usar base64_decode() en el payload.'
            ));
        }
        // Desencriptar.
        $iv_size = @mcrypt_get_iv_size(
            MCRYPT_RIJNDAEL_256,
            MCRYPT_MODE_CBC
        );
        $iv_dec = substr($payload, 0, $iv_size);
        $payload = substr($payload, $iv_size);
        $value = @mcrypt_decrypt(
            MCRYPT_RIJNDAEL_256,
            $this->key,
            $payload,
            MCRYPT_MODE_CBC,
            $iv_dec
        );
        // Deserializar si es necesario.
        if ($unserialize && is_serialized($value)) {
            $value = unserialize($value);
        }
        // Quitar espacios del string (si es string).
        if (is_string($value)) {
            $value = trim($value);
        }
        // Entregar el valor.
        return $value;
    }

}
