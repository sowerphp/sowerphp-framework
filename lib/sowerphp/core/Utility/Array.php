<?php

/**
 * SowerPHP: Minimalist Framework for PHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

namespace sowerphp\core;

/**
 * Utilidad para trabajar con arreglos
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-10-01
 */
class Utility_Array
{

    /**
     * Une dos o más arrays recursivamente
     * @param array $array1
     * @param array $array2
     * @return array
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     */
    public static function mergeRecursiveDistinct(array &$array1, array &$array2)
    {
        $merged = $array1;
        foreach ( $array2 as $key => &$value ) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged [$key] = self::mergeRecursiveDistinct (
                    $merged [$key],
                    $value
                );
            } else {
                $merged [$key] = $value;
            }
        }
        return $merged;
    }

    /**
     * Convierte una tabla de Nx2 (N filas 2 columnas) a un arreglo asociativo
     * @param table Tabla de Nx2 (N filas 2 columnas) que se quiere convertir
     * @return Arreglo convertido a asociativo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-02-24
     */
    public static function fromTable($table)
    {
        $array = array();
        foreach($table as &$row) {
            $array[array_shift($row)] = array_shift($row);
        }
        return $array;
    }

    /**
     * http://stackoverflow.com/users/847142/frans-van-asselt
     */
    public static function toXML($array, $root = 'root')
    {
        $xml = new SimpleXMLElement('<'.$root.'/>');
        foreach ($array as $key => $value){
            if (is_array($value)) {
                if (is_numeric($key)) $key = 'item'; // by DeLaF
                    self::toXML($value, $xml->addChild($key));
            } else {
                $xml->addChild($key, $value);
            }
        }
        return $xml->asXML();
    }

    /**
     * Función que extra de un arreglo en formato:
     * array(
     *   'key1' => array(1,2,3),
     *   'key2' => array(4,5,6),
     *   'key3' => array(7,8,9),
     * )
     * Y lo entrega como una "tabla":
     * array (
     *   array (
     *     'key1' => 1,
     *     'key2' => 4,
     *     'key3' => 7,
     *   ),
     *   array (
     *     'key1' => 2,
     *     'key2' => 5,
     *     'key3' => 8,
     *   ),
     *   array (
     *     'key1' => 3,
     *     'key2' => 6,
     *     'key3' => 9,
     *   ),
     * )
     * @param array Arreglo de donde extraer
     * @param keys Llaves que se extraeran
     * @return Tabla con los campos extraídos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-02-19
     */
    public static function groupToTable ($array, $keys = null)
    {
        // determinar llaves y su cantidad
        if ($keys==null) {
            $keys = array_keys ($array);
        }
        $n_keys = count($keys);
        // determinar el arreglo con más elementos y cuantos son
        $n_elementos = count($array[$keys[0]]);
        for ($j=1; $j<$n_keys; ++$j) {
            $aux = count($array[$keys[$j]]);
            if ($aux > $n_elementos)
                $n_elementos = $aux;
        }
        // extrar datos
        $data = array();
        for ($i=0; $i<$n_elementos; ++$i) {
            $d = array();
            for ($j=0; $j<$n_keys; ++$j) {
                if (isset($array[$keys[$j]][$i])) {
                    $d[$keys[$j]] = $array[$keys[$j]][$i];
                } else {
                    $d[$keys[$j]] = null;
                }
            }
            $data[] = $d;
        }
        return $data;
    }

    /**
     * Método que toma un arreglo con un formato de tabla el cual contiene
     * un encabezado y detalle, ejemplo:
     *   $arreglo = array (
     *     array (
     *       'run' => '1-9'
     *       'nombre' => 'Juan Pérez'
     *       'direccion' => 'Dir 1'
     *       'telefono' => 'Tel 1'
     *     ),
     *     array (
     *       'run' => '1-9'
     *       'nombre' => 'Juan Pérez'
     *       'direccion' => 'Dir 2'
     *       'telefono' => 'Tel 2'
     *     ),
     *   );
     * Y con la llamada tableToArrayWithHeaderAndBody($arreglo, 2) lo entrega como:
     *   $arreglo = array (
     *     'run' => '1-9'
     *     'nombre' => 'Juan Pérez'
     *     'detalle' => array (
     *       array (
     *         'direccion' => 'Dir 1'
     *         'telefono' => 'Tel 1'
     *       ),
     *       array (
     *         'direccion' => 'Dir 2'
     *         'telefono' => 'Tel 2'
     *       ),
     *     )
     *   );
     * @param data Arreglo en formato tabla con los datos
     * @param camposEncabezado Cuandos campos (columnas) de la "tabla" son parte del encabezado
     * @param detalle Nombre del índice (key) que se utilizará para agrupar los detalles
     * @return Arreglo con el formato de un encabezado y detalle
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-02-19
     */
    public static function fromTableWithHeaderAndBody ($data, $camposEncabezado, $detalle = 'detalle')
    {
        if (!isset($data[0]))
            return array();
        $id = array_keys ($data[0])[0];
        $item = null;
        $items = array();
        foreach ($data as &$d) {
            if ($item === null) {
                $item = array();
                $i = 0;
                foreach ($d as $key => &$value) {
                    $item[$key] = array_shift($d);
                    if (++$i==$camposEncabezado)
                        break;
                }
                $item[$detalle] = array ();
                $item[$detalle][] = $d;
            } else if ($item[$id] == $d[$id]) {
                $item[$detalle][] = array_slice (
                    $d,
                    $camposEncabezado
                );
            } else {
                $items[] = $item;
                $item = array();
                $i = 0;
                foreach ($d as $key => &$value) {
                    $item[$key] = array_shift($d);
                    if (++$i==$camposEncabezado)
                        break;
                }
                $item[$detalle] = array ();
                $item[$detalle][] = $d;
            }
            unset ($d);
        }
        $items[] = $item;
        return $items;
    }

    /**
     * Método que toma la primera columna de una tabla y la convierte en el
     * índice de un arreglo asociativo, donde las otras columnas o columna son
     * los valores que tiene dicho índice
     * @param table Tabla que se desea convertir
     * @return Arreglo asociativo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-03-31
     */
    public static function tableToAssociativeArray($table)
    {
        $array = [];
        $keys = [];
        foreach ($table as &$row) {
            $key = array_shift($row);
            if (!isset($array[$key]))
                $array[$key] = [];
            if (!isset($keys[$key])) {
                $array[$key] = count($row)==1 ? array_shift($row) : $row;
                // indica que no es un arreglo de datos (hay que hacerlo así
                // porque los datos en si pueden ser un arreglo, entonces no
                // bastaría solamente verificar si $array[$key]  es ya un
                // arreglo)
                $keys[$key] = false;
            } else {
                if (!$keys[$key]) {
                    $array[$key] = [$array[$key]];
                    $keys[$key] = true;
                }
                $array[$key][] = count($row)==1 ? array_shift($row) : $row;
            }
        }
        return $array;
    }

}
