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

use Illuminate\Contracts\Validation\Rule;

class Data_Validation_BooleanAsInteger implements Rule
{
    /**
     * Determina si el valor es booleano, que puede ser booleano real o un
     * entero que representa un booleano (1 para true y 0 para false).
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        // Si es booleano true inmediatamente.
        if (is_bool($value)) {
            return true;
        }

        // Si no es numérico false.
        if (!is_numeric($value)) {
            return false;
        }

        // Si es de tipo numérico debe ser solo 0 o 1.
        $value = (int)$value;
        if ($value === 0 || $value === 1) {
            return true;
        }

        // Otros casos no validan.
        return false;
    }

    /**
     * Obtiene el mensaje de error de validación.
     *
     * @return string
     */
    public function message(): string
    {
        return 'El campo :attribute debe ser un valor booleano (1 o 0).';
    }
}
