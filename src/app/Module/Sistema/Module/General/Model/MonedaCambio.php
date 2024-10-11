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

namespace sowerphp\app\Sistema\General;

use sowerphp\autoload\Model;

/**
 * Modelo singular de la tabla "moneda_cambio" de la base de datos.
 *
 * Permite interactuar con un registro de la tabla.
 */
class Model_MonedaCambio extends Model
{

    /**
     * Metadatos del modelo.
     *
     * @var array
     */
    protected $metadata = [
        'model' => [
            'verbose_name' => 'Tipo de cambio de moneda',
            'verbose_name_plural' => 'Tipos de cambios de monedas',
            'db_table_comment' => 'Tipos de cambio de diferentes monedas, se registra un valor por día.',
            'ordering' => ['-fecha'],
        ],
        'fields' => [
            'desde' => [
                'type' => self::TYPE_CHAR,
                'primary_key' => true,
                'length' => 3,
                'verbose_name' => 'Moneda de origen',
                'help_text' => 'Moneda de origen que se desea cambiar.',
            ],
            'a' => [
                'type' => self::TYPE_CHAR,
                'primary_key' => true,
                'length' => 3,
                'default' => 'CLP',
                'verbose_name' => 'Moneda de destino',
                'help_text' => 'Moneda de destino a la que se cambiará.',
            ],
            'fecha' => [
                'type' => self::TYPE_DATE,
                'primary_key' => true,
                'default' => '__NOW__',
                'verbose_name' => 'Fecha del tipo de cambio',
                'help_text' => 'Fecha del tipo de cambio para la conversión de la moneda de origen a la de destino.',
            ],
            'valor' => [
                'type' => self::TYPE_FLOAT,
                'verbose_name' => '¿Cuánto vale la moneda de origen en la de destino?',
                'help_text' => 'Precio del tipo de cambio de la moneda de origen a la de destino.',
            ],
        ],
        'form' => [
            'layout' => [
                [
                    'rows' => [
                        ['desde', 'a'],
                        ['fecha', 'valor'],
                    ]
                ]
            ],
        ]
    ];

    private static $monedas_aduana = [
        'DOLAR USA' => 'USD',
        'EURO' => 'EUR',
    ]; ///< Conversión entre el nombre de la moneda de Aduana de Chile y el código internacional

    /**
     * Constructor del tipo de cambio
     * Permite utilizar como desde el nombre de la moneda en el formato de la
     * aduana de Chile
     */
    public function __construct($desde = null, $a = null, $fecha = null)
    {
        if (is_array($desde)) {
            list($desde, $a, $fecha) = $desde;
        }
        // buscar moneda
        if ($desde && $a) {
            if (isset(self::$monedas_aduana[$desde])) {
                $desde = self::$monedas_aduana[$desde];
            }
            if (isset(self::$monedas_aduana[$a])) {
                $a = self::$monedas_aduana[$a];
            }
            if (!$fecha) {
                $fecha = date('Y-m-d');
            }
            $desde = mb_strtoupper($desde);
            $a = mb_strtoupper($a);
            parent::__construct($desde, $a, $fecha);
            // si no existe el tipo de cambio, buscar si existe "a" USD y luego desde USD a la moneda $a original
            if (!$this->valor && $a != 'USD') {
                $MonedaCambioUSD = (new Model_MonedaCambios)->get($desde, 'USD', $fecha);
                if ($MonedaCambioUSD->valor) {
                    $USD = (new Model_MonedaCambios)->get('USD', $a, $fecha);
                    if ($USD->valor) {
                        $this->valor = $MonedaCambioUSD->valor * $USD->valor;
                        $this->save();
                    }
                }
            }
        } else {
            parent::__construct();
        }
    }

}
