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

namespace sowerphp\app;

/**
 * Clase para trabajar con RUTs (y RUNs) de Chile
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2016-01-30
 */
class Utility_Rut
{

    /**
     * Método que valida el RUT ingresado
     * @param mixed $rut RUT con dígito verificador (puntos son opcionales)
     * @return bool
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-07-04
     */
    public static function check($rut)
    {
        if (!strpos($rut, '-')) {
            return false;
        }
        list($rut, $dv) = explode('-', str_replace('.', '', $rut));
        if (!is_numeric($rut)) {
            return false;
        }
        $real_dv = self::dv($rut);
        return strtoupper($dv) == $real_dv ? $rut : false;
    }

    /**
     * Calcula el dígito verificador de un RUT
     * @param mixed $r RUT al que se calculará el dígito verificador
     * @return string Dígito verificador
     * @author Desconocido
     * @version 2010-05-23
     */
    public static function dv($r)
    {
        $r = str_replace('.', '', $r);
        $r = str_replace(',', '', $r);
        $s=1;
        for ($m = 0; $r != 0; $r/=10) {
            $s = ($s + $r%10 * (9 - $m++ % 6)) % 11;
        }
        return strtoupper(chr($s ? $s + 47 : 75));
    }

    /**
     * Transforma un RUT a un formato con sólo los números o formateado,
     * dependerá de como sea pasado el RUT
     * @param mixed $rut RUT que se quiere transformar
     * @param bool $quitarDV Si es true el dígito verificador se quita, sino se mantiene
     * @return mixed RUT formateado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-11-01
     */
    public static function normalizar($rut, $quitarDV = true)
    {
        if (is_array($rut)) {
            return self::normalizar_array($rut, $quitarDV);
        }
        return self::normalizar_string($rut, $quitarDV);
    }

    private static function normalizar_array($arreglo, $quitarDV = true)
    {
        if (isset($arreglo[1]) and (!empty($arreglo[1]) or $arreglo[1]==='0')) {
            $arreglo[0] .= '-' . strtoupper($arreglo[1]);
        }
        if (!strpos($arreglo[0], '-')) {
            $arreglo[0] = substr($arreglo[0], 0, -1) . '-' . substr($arreglo[0], -1);
        }
        $rut = self::normalizar_string($arreglo[0]);
        $dv = substr($arreglo[0], -1);
        return $quitarDV ? $rut : [$rut, $dv];
    }

    private static function normalizar_string($rut, $quitarDV = true)
    {
        if (!isset($rut[0])) {
            return '';
        }
        $rut = strtoupper(str_replace(['.', ','], '', $rut));
        if (strpos($rut, '-')) {
            if ($quitarDV) {
                $aux = explode('-', $rut);
                return (int)array_shift($aux);
            }
            return (int)str_replace('-', '', $rut);
        } else {
            $rutNew = number_format((int)substr($rut, 0, -1), 0, '', '.');
            return $rutNew . '-' . $rut[strlen($rut)-1];
        }
    }

    /**
     * Agrega un número su dígito verificador y lo formatea como RUT
     * @param mixed $rut RUT en formato número y sin dígito verificador
     * @return string RUT formateado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-11-01
     */
    public static function addDV($rut)
    {
        return self::normalizar_string($rut . self::dv($rut));
    }

}
