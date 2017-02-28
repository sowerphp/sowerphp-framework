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
 * Utilidad para realizar operaciones sobre datos:
 *  - Encriptar y desencriptar: requiere php5-mcrypt
 *  - Sanitizar/limpiar datos
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2017-02-28
 */
class Utility_Data
{

    /**
     * Método que encripta un texto plano
     * @param plaintext Texto plano a encriptar
     * @param key Clave a usar para encriptar
     * @return Texto encriptado en base64
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2016-01-25
     */
    public static function encrypt($plaintext, $key = null)
    {
        if (!$key)
            return base64_encode($plaintext);
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $plaintext, MCRYPT_MODE_CBC, $iv);
        return base64_encode($iv.$ciphertext);
    }

    /**
     * Método que desencripta un texto encriptado
     * @param $ciphertext_base64 Texto encriptado en base64 a desencriptar
     * @param key Clave a uar para desencriptar
     * @return Texto plano
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2016-01-25
     */
    public static function decrypt($ciphertext_base64, $key = null)
    {
        if (empty($ciphertext_base64))
            return $ciphertext_base64;
        $ciphertext_dec = base64_decode($ciphertext_base64);
        if (!$key)
            return $ciphertext_dec;
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
        $iv_dec = substr($ciphertext_dec, 0, $iv_size);
        $ciphertext_dec = substr($ciphertext_dec, $iv_size);
        $plaintext_dec = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
        return $plaintext_dec;
    }

    /**
     * Método que limpia un texto
     * @param string Texto que se desea limpiar
     * @param options Opciones para la limpieza del texto
     * @return Texto limpio
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-02-28
     */
    public static function sanitize(&$string, array $options = [])
    {
        if (!$string)
            return false;
        $string = trim(strip_tags($string));
        if (!empty($options['l'])) {
            $string = substr($string, 0, $options['l']);
        }
        return true;
    }

}
