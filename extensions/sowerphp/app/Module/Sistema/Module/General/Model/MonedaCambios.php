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

// namespace del modelo
namespace sowerphp\app\Sistema\General;

/**
 * Clase para mapear la tabla moneda_cambio de la base de datos
 * Comentario de la tabla:
 * Esta clase permite trabajar sobre un conjunto de registros de la tabla moneda_cambio
 * @author SowerPHP Code Generator
 * @version 2015-05-14 01:01:15
 */
class Model_MonedaCambios extends \Model_Plural_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'moneda_cambio'; ///< Tabla del modelo

    private $decimales = [
        'CLP' => 0,
        'CLF' => 2,
        'USD' => 2,
        'EUR' => 2,
    ];

    /**
     * Método que busca los valores de varias monedas al mismo tiempo para un
     * día determinado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-10-29
     */
    public function getValor($monedas, $dia = null)
    {
        if (!$monedas) {
            return [];
        }
        if (!is_array($monedas)) {
            $monedas = [$monedas];
        }
        if (!$dia) {
            $dia = date('Y-m-d');
        }
        $where = ['fecha = :dia'];
        $vars = [':dia' => $dia];
        $i = 1;
        $in = [];
        foreach ($monedas as $m) {
            $in[] = ':moneda'.$i;
            $vars[':moneda'.$i] = $m;
            $i++;
        }
        $where[] = 'desde IN ('.implode(', ', $in).')';
        return $this->db->getAssociativeArray('
            SELECT desde AS moneda, valor
            FROM moneda_cambio
            WHERE '.implode(' AND ', $where).'
            ORDER BY desde
        ', $vars);
    }

    /**
     * Método que busca los valores de varias monedas al mismo tiempo para un
     * rango de días determinados
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2023-02-28
     */
    public function getValores($monedas, $fecha_desde = null, $fecha_hasta)
    {
        if (!$monedas) {
            return [];
        }
        if (!is_array($monedas)) {
            $monedas = [$monedas];
        }
        if (!$fecha_hasta) {
            $fecha_hasta = date('Y-m-d');
        }
        $fecha_desde = \sowerphp\general\Utility_Date::getPrevious($fecha_hasta);
        $where = ['fecha BETWEEN :fecha_desde AND :fecha_hasta'];
        $vars = [':fecha_desde' => $fecha_desde, ':fecha_hasta' => $fecha_hasta];
        $i = 1;
        $in = [];
        foreach ($monedas as $m) {
            $in[] = ':moneda'.$i;
            $vars[':moneda'.$i] = $m;
            $i++;
        }
        $where[] = 'desde IN ('.implode(', ', $in).')';
        return $this->db->getAssociativeArray('
            SELECT desde AS moneda, fecha, valor
            FROM moneda_cambio
            WHERE '.implode(' AND ', $where).'
            ORDER BY desde, fecha
        ', $vars);
    }

    /**
     * Método que convierte un monto de una moneda a otra
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-10-26
     */
    public function convertir($desde, $a, $monto, $fecha = null, $decimales = null)
    {
        if (!$fecha) {
            $fecha = date('Y-m-d');
        }
        if ($decimales===null) {
            $decimales = $this->getDecimales($a);
        }
        $cambio = (new \sowerphp\app\Sistema\General\Model_MonedaCambio($desde, $a, $fecha))->valor;
        if ($cambio) {
            return round($monto * $cambio, $decimales);
        }
        // buscar la combinación al revés
        $cambio_contrario = $this->get($a, $desde, $fecha)->valor;
        if ($cambio_contrario) {
            return round($monto / $cambio_contrario, $decimales);
        }
    }

    /**
     * Método que entrega los decimales asociados a una moneda
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-10-26
     */
    public function getDecimales($moneda)
    {
        return isset($this->decimales[$moneda]) ? $this->decimales[$moneda] : 3;
    }

}
