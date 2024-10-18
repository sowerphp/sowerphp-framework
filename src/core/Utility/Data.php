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

/**
 * Utilidad para realizar operaciones sobre datos:
 *  - Sanitizar/limpiar datos.
 */
class Utility_Data
{
    /**
     * Método que limpia datos de tipo texto (string).
     *
     * @param $data Datos que se desean limpiar, puede ser un arreglo de datos.
     * @param array $options Opciones para la limpieza de los datos.
     * @deprecated Utilizar Service_Sanitizer con app('sanitizer').
     */
    public static function sanitize(&$data, array $options = [])
    {
        if (is_array($data)) {
            foreach ($data as &$d) {
                $d = self::sanitize($d);
            }
            return $data;
        }
        if (!$data || !is_string($data) || is_numeric($data)) {
            return $data;
        }
        if (!empty($options['tags'])) {
            $data = trim(strip_tags($data, $options['tags']));
        } else {
            $data = trim(strip_tags($data));
        }
        if (!empty($options['l'])) {
            $data = substr($data, 0, $options['l']);
        }

        return $data;
    }

    /**
     * Método que obtiene los correos electrónicos desde un string.
     *
     * @param string $listado Listado de correos electrónicos.
     * @return array Listado de correos que hay en el listado.
     * @deprecated Utilizar Service_Caster con app('caster').
     */
    public static function emails($listado): array
    {
        if (!is_array($listado)) {
            $listado = array_filter(
                array_unique(
                    array_map('trim', explode(';', str_replace("\n", ';', $listado)))
                )
            );
        }
        $emails = [];
        foreach ($listado as $e) {
            if (\sowerphp\core\Utility_Data_Validation::check($e, ['notempty', 'email'])) {
                $emails[] = $e;
            }
        }

        return $emails;
    }
}
