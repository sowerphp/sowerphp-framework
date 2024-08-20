<?php

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

class Data_Validation_Cl_Rut implements Rule
{

    /**
     * Determina si el valor es un RUT válido.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return $this->validateRut($value);
    }

    /**
     * Valida un RUT chileno.
     *
     * @param string $rut
     * @return bool
     */
    private function validateRut(string $rut): bool
    {
        // Remover puntos y guiones.
        $rut = preg_replace('/[.\-]/', '', $rut);

        // Verificar longitud mínima.
        if (strlen($rut) < 8) {
            return false;
        }

        // Obtener el dígito verificador.
        $dv = strtoupper(substr($rut, -1));
        $number = substr($rut, 0, -1);

        // Validar dígito verificador.
        return $this->calculateVerificationDigit($number) === $dv;
    }

    /**
     * Calcula el dígito verificador de un RUT.
     *
     * @param string $number
     * @return string
     */
    private function calculateVerificationDigit(string $number): string
    {
        $sum = 0;
        $factor = 2;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $sum += $number[$i] * $factor;
            $factor = $factor == 7 ? 2 : $factor + 1;
        }

        $dv = 11 - ($sum % 11);

        if ($dv == 11) {
            return '0';
        }

        if ($dv == 10) {
            return 'K';
        }

        return (string) $dv;
    }

    /**
     * Obtiene el mensaje de error de validación.
     *
     * @return string
     */
    public function message(): string
    {
        return __('El campo :attribute debe ser un RUT válido.');
    }

}
