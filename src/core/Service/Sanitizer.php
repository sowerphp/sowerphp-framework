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
 * Servicio de sanitización de datos.
 */
class Service_Sanitizer implements Interface_Service
{

    /**
     * Filtros disponibles para usar en la sanitización con filter_var().
     *
     * @var array
     */
    protected $filters = [
        'default' => FILTER_DEFAULT,
        'encoded' => FILTER_SANITIZE_ENCODED,
        'special_chars' => FILTER_SANITIZE_SPECIAL_CHARS,
        'email' => FILTER_SANITIZE_EMAIL,
        'url' => FILTER_SANITIZE_URL,
        'number_int' => FILTER_SANITIZE_NUMBER_INT,
        'number_float' => FILTER_SANITIZE_NUMBER_FLOAT,
        'add_slashes' => FILTER_SANITIZE_ADD_SLASHES,
    ];

    /**
     * Registra el servicio de validación de datos.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio de validación de datos.
     *
     * @return void
     */
    public function boot(): void
    {
    }

    /**
     * Finaliza el servicio de validación de datos.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Sanitiza los datos según las reglas definidas.
     *
     * @param array $data Arreglo con los datos que se desean sanitizar.
     * @param array $rules Reglas de sanitización a aplicar.
     * @return array Datos sanitizados.
     */
    public function sanitize(array $data, array $rules): array
    {
        foreach ($data as &$value) {
            foreach ($rules as $rule) {
                $value = $this->sanitizeValue($value, $rule);
            }
        }
        return $data;
    }

    /**
     * Sanitiza un valor según la regla definida.
     *
     * @param mixed $value Valor que se desea sanitizar.
     * @param string $rule Regla de sanitización a aplicar.
     * @return mixed Valor sanitizado.
     */
    protected function sanitizeValue($value, string $rule)
    {
        if ($value === null) {
            return $value;
        }
        $aux = explode(':', $rule, 2);
        $type = $aux[0];
        $parametersString = $aux[1] ?? null;
        $parameters = $parametersString
            ? split_parameters($parametersString)
            : []
        ;
        array_unshift($parameters, (string)$value);
        return call_user_func_array([$this, $type], $parameters);
    }

    /*
    |--------------------------------------------------------------------------
    | DESDE AQUÍ HACIA ABAJO CADA MÉTODO ES UNA REGLA DE SANITIZACIÓN.
    |--------------------------------------------------------------------------
    */

    protected function remove_non_printable(string $value): string
    {
        return preg_replace('/[[:^print:]]/', '', $value);
    }

    protected function strip_tags(string $value, string $allowed_tags = null): string
    {
        return $allowed_tags
            ? strip_tags($value, $allowed_tags)
            : strip_tags($value)
        ;
    }

    protected function remove_chars(string $value, string $chars): string
    {
        return str_replace(str_split($chars), '', $value);
    }

    protected function spaces(string $value): string
    {
        return preg_replace('/\s+/', ' ', $value);
    }

    protected function trim(string $value, string $characters = null): string
    {
        return $characters ? trim($value, $characters) : trim($value);
    }

    protected function htmlspecialchars(string $value): string
    {
        return htmlspecialchars($value);
    }

    protected function htmlentities(string $value): string
    {
        return htmlentities($value);
    }

    protected function addslashes(string $value): string
    {
        return addslashes($value);
    }

    protected function urlencode(string $value): string
    {
        return urlencode($value);
    }

    protected function rawurlencode(string $value): string
    {
        return rawurlencode($value);
    }

    protected function intval(string $value, int $base): string
    {
        return $base ? intval($value, $base) : intval($value);
    }

    protected function floatval(string $value): string
    {
        return floatval($value);
    }

    protected function strtolower(string $value): string
    {
        return strtolower($value);
    }

    protected function strtoupper(string $value): string
    {
        return strtoupper($value);
    }

    protected function filter_var(string $value, $filter): string
    {
        $filterCode = $this->filters[$filter] ?? $this->filters['default'];
        return filter_var($value, $filterCode);
    }

    protected function substr(string $value, int $length): string
    {
        return $this->mb_substr($value, $length);
    }

    protected function mb_substr(string $value, int $length): string
    {
        return mb_substr($value, 0, $length);
    }

    protected function remove_prefix(string $value, ...$prefixes): string
    {
        foreach ($prefixes as $prefix) {
            if (strpos($value, $prefix) === 0) {
                return mb_substr($value, strlen($prefix));
            }
        }
        return $value;
    }

    protected function remove_suffix(string $value, ...$suffixes): string
    {
        foreach ($suffixes as $suffix) {
            if (substr($value, -strlen($suffix)) === $suffix) {
                return mb_substr($value, 0, -strlen($suffix));
            }
        }
        return $value;
    }

    protected function replace(string $value, string $search, string $replace): string
    {
        return str_replace($search, $replace, $value);
    }

    protected function remove_by_regex(string $value, string $regex)
    {
        return preg_replace($regex, '', $value);
    }

    protected function keep_by_regex(string $value, string $regex)
    {
        preg_match_all($regex, $value, $matches);
        return implode('', $matches[0]);
    }

}
