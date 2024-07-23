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

use Illuminate\Contracts\Validation\Rule;

/**
 * Regla de validación de unicidad en modelos de base de datos con soporte para
 * llaves primarias formadas por múltiples campos.
 */
class Data_Validation_UniqueComposite implements Rule, \JsonSerializable
{

    protected $database;
    protected $table;
    protected $unique;
    protected $ignore;

    /**
     * Crea una nueva instancia de la regla.
     *
     * @param string $database Base de datos de la tabla en la que se realizará
     * la verificación.
     * @param string $table La tabla en la que se realizará la verificación.
     * @param array $unique Las columnas y sus valores que deben ser únicos.
     * @param array $ignoreConditions Las condiciones para ignorar filas
     * específicas.
     */
    public function __construct(
        string $database,
        string $table,
        array $unique,
        array $ignore = []
    )
    {
        $this->database = $database;
        $this->table = $table;
        $this->unique = $unique;
        $this->ignore = $ignore;
    }

    /**
     * Determina si la regla de validación pasa.
     *
     * @param string $attribute El nombre del atributo que se está validando.
     * @param mixed $value El valor del atributo.
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $query = database($this->database)->table($this->table);
        // Añadir las condiciones de las columnas para verificar la unicidad.
        foreach ($this->unique as $column => $value) {
            $query->where($column, $value);
        }
        // Añadir las condiciones para ignorar filas específicas.
        foreach ($this->ignore as $column => $value) {
            $query->where($column, '<>', $value);
        }
        // Validar buscando que no existan coincidencias.
        return !$query->exists();
    }

    /**
     * Obtiene el mensaje de error de validación.
     *
     * @return string
     */
    public function message(): string
    {
        return 'validation.unique';
    }

    /**
     * Método que se llamará cuando se quiera serializar el objeto con
     * json_encode() permite especificar qué atributos se deben serializar y
     * cómomo se debe realizar dicha serialización.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'rule' => 'unique_composite',
            'database' => $this->database,
            'table' => $this->table,
            'unique' => $this->unique,
            'ignore' => $this->ignore,
        ];
    }

}
