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
 * Cipher con sodium.
 */
class Data_Encryption_Sodium extends Data_Encryption
{

    /**
     * Constructor del encriptador usando sodium.
     *
     * @param string $key Clave de encriptación que se utilizará.
     * @param string $cipher Siempre debe ser 'sodium'. Parámetro se deja por
     * compatibilidad con la clase Illuminate\Encryption\Encrypter.
     */
    public function __construct(string $key, string $cipher = 'sodium')
    {
        // Verificar que exista la función sodium_crypto_secretbox().
        if (!function_exists('sodium_crypto_secretbox')) {
            throw new \Exception(__(
                'La extensión %s de PHP no está disponible.',
                'sodium'
            ));
        }
        // Verificar que exista la función sodium_crypto_secretbox_open().
        if (!function_exists('sodium_crypto_secretbox_open')) {
            throw new \Exception(__(
                'La extensión %s de PHP no está disponible.',
                'sodium'
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
     * Método que encripta datos usando sodium.
     * @link https://stackoverflow.com/a/52688846
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
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext_dec = $nonce . sodium_crypto_secretbox(
            $value,
            $nonce,
            $this->key
        );
        sodium_memzero($value);
        return base64_encode($ciphertext_dec);
    }

    /**
     * Método que desencripta datos usando sodium.
     * @link https://stackoverflow.com/a/52688846
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
        if (mb_strlen($payload, '8bit') < (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
            throw new \Exception(__(
                'El mensaje cifrado está truncado, no es válido.'
            ));
        }
        $nonce = mb_substr($payload, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($payload, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
        $value = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key);
        if ($value === false) {
            throw new \Exception(__(
                'El mensaje fue manipulado después de ser encriptado, no es válido.'
            ));
        }
        sodium_memzero($ciphertext);
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
