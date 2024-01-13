<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
 * Copyright (C) SowerPHP <https://www.sowerphp.org>
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
 * Esta clase permite trabajar sobre un registro de la tabla moneda_cambio
 */
class Model_MonedaCambio extends \Model_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'moneda_cambio'; ///< Tabla del modelo

    // Atributos de la clase (columnas en la base de datos)
    public $desde; ///< char(3) NOT NULL DEFAULT '' PK
    public $a; ///< char(3) NOT NULL DEFAULT '' PK
    public $fecha; ///< date() NOT NULL DEFAULT '0000-00-00' PK
    public $valor; ///< float(12) NOT NULL DEFAULT ''

    // Información de las columnas de la tabla en la base de datos
    public static $columnsInfo = array(
        'desde' => array(
            'name'      => 'Desde',
            'comment'   => '',
            'type'      => 'char',
            'length'    => 3,
            'null'      => false,
            'default'   => '',
            'auto'      => false,
            'pk'        => true,
            'fk'        => null
        ),
        'a' => array(
            'name'      => 'A',
            'comment'   => '',
            'type'      => 'char',
            'length'    => 3,
            'null'      => false,
            'default'   => '',
            'auto'      => false,
            'pk'        => true,
            'fk'        => null
        ),
        'fecha' => array(
            'name'      => 'Fecha',
            'comment'   => '',
            'type'      => 'date',
            'length'    => null,
            'null'      => false,
            'default'   => '0000-00-00',
            'auto'      => false,
            'pk'        => true,
            'fk'        => null
        ),
        'valor' => array(
            'name'      => 'Valor',
            'comment'   => '',
            'type'      => 'float',
            'length'    => 12,
            'null'      => false,
            'default'   => '',
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),

    );

    // Comentario de la tabla en la base de datos
    public static $tableComment = '';

    public static $fkNamespace = array(); ///< Namespaces que utiliza esta clase

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
