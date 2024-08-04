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

use \Carbon\Carbon;

/**
 * Servicio de casteo de datos.
 */
class Service_Caster implements Interface_Service
{

    /**
     * Registra el servicio de casteo de datos.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio de casteo de datos.
     *
     * @return void
     */
    public function boot(): void
    {
    }

    /**
     * Finaliza el servicio de casteo de datos.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Castea los datos según las reglas definidas.
     *
     * @param array $data Arreglo con los datos que se desean castear.
     * @param array $rules Reglas de casteo a aplicar.
     * @return array Datos casteados.
     */
    public function cast(array $data, array $rules, string $type): array
    {
        $method = 'castValueFor' . ucfirst($type);
        foreach ($data as &$value) {
            foreach ($rules as $rule) {
                $value = $this->$method($value, $rule);
            }
        }
        return $data;
    }

    /**
     * Castea los datos para obtenerlos según las reglas definidas para una
     * operación de lectura de datos desde un origen (ej: base de datos).
     *
     * @param array $data Arreglo con los datos que se desean castear.
     * @param array $rules Reglas de casteo a aplicar.
     * @return array Datos casteados.
     */
    public function castForGet(array $data, array $rules): array
    {
        return $this->cast($data, $rules, 'get');
    }

    /**
     * Castea los datos para asignarlos según las reglas definidas para una
     * operación de escritura de datos hacia un origen (ej: base de datos).
     *
     * @param array $data Arreglo con los datos que se desean castear.
     * @param array $rules Reglas de casteo a aplicar.
     * @return array Datos casteados.
     */
    public function castForSet(array $data, array $rules): array
    {
        return $this->cast($data, $rules, 'set');
    }

    /**
     * Castea un valor para obtenerlo según la regla definida para una lectura.
     *
     * @param mixed $value El valor que se desea castear.
     * @param string $rule La regla de casteo a aplicar.
     * @return mixed El valor casteado.
     */
    protected function castValueForGet($value, string $rule)
    {
        $aux = explode(':', $rule, 2);
        $type = $aux[0];
        $parametersString = $aux[1] ?? null;
        $parameters = $parametersString
            ? split_parameters($parametersString)
            : []
        ;
        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
            case 'decimal':
                return isset($parameters[0])
                    ? (float) number_format($value, (int) $parameters[0])
                    : (float) $value
                ;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
                return json_decode($value);
            case 'array':
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
            case 'collection':
                return collect(
                    is_string($value) ? json_decode($value, true) : $value
                );
            case 'date':
                return Carbon::parse($value)->startOfDay();
            case 'datetime':
                return Carbon::parse($value);
            case 'timestamp':
                return Carbon::createFromTimestamp($value);
            default:
                return $value;
        }
    }

    /**
     * Castea un valor para asignarlo según la regla definida para una escritura.
     *
     * @param mixed $value El valor que se desea castear.
     * @param string $rule La regla de casteo a aplicar.
     * @return mixed El valor casteado.
     */
    protected function castValueForSet($value, string $rule)
    {
        $aux = explode(':', $rule, 2);
        $type = $aux[0];
        $parametersString = $aux[1] ?? null;
        $parameters = $parametersString
            ? split_parameters($parametersString)
            : []
        ;
        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'decimal':
                return isset($parameters[0])
                    ? (float) number_format($value, (int) $parameters[0])
                    : (float) $value
                ;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
                return json_encode($value);
            case 'array':
            case 'json':
                return json_encode($value);
            case 'collection':
                return json_encode($value->toArray());
            case 'date':
                return Carbon::parse($value)->toDateString();
            case 'datetime':
                return Carbon::parse($value)->toDateTimeString();
            case 'timestamp':
                return Carbon::parse($value)->timestamp;
            default:
                return $value;
        }
    }

}
