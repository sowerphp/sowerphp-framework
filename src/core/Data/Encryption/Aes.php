<?php

declare(strict_types=1);

/**
 * SowerPHP: Simple and Open Web Ecosystem Reimagined for PHP.
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

use Illuminate\Encryption\Encrypter as EncrypterIlluminate;

/**
 * Encriptador con la clase oficial de Illuminate.
 */
class Data_Encryption_Aes extends EncrypterIlluminate
{
    /**
     * Método que encripta datos usando mcrypt.
     */
    public function encrypt($value, $serialize = true): string
    {
        // Quitar espacios del string (si es string).
        if (is_string($value)) {
            $value = trim($value);
        }

        // Encriptar con el método padre.
        return parent::encrypt($value, $serialize);
    }

    /**
     * Método que desencripta datos encriptados usando mcrypt.
     */
    public function decrypt($payload, $unserialize = true)
    {
        // Desencriptar con el método padre.
        $value = parent::decrypt($payload, $unserialize);

        // Quitar espacios del string (si es string).
        if (is_string($value)) {
            $value = trim($value);
        }

        // Entregar el valor.
        return $value;
    }
}
