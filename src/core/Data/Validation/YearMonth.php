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

class Data_Validation_YearMonth implements Rule
{
    /**
     * Determina si el valor es un periodo válido en formato YYYYMM.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        // Comprobar si el valor tiene exactamente 6 caracteres y es numérico.
        if (!$value || strlen($value) != 6 || !is_numeric($value)) {
            return false;
        }

        // Validar el mes.
        $month = (int) substr($value, 4, 2);
        if ($month < 1 || $month > 12) {
            return false;
        }

        // Todo ok.
        return true;
    }

    /**
     * Obtiene el mensaje de error de validación.
     *
     * @return string
     */
    public function message(): string
    {
        return __(
            'El campo :attribute debe tener el formato AAAAMM. Ejemplo %s para el año y mes actual.',
            date('Ym')
        );
    }
}
