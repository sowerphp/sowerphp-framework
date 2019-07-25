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

namespace sowerphp\core;

/**
 * Utilidad para trabajar con arreglos
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2019-07-25
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
     * @version 2019-07-25
     */
    public static function fromTableWithHeaderAndBody($data, $camposEncabezado, $detalle = 'detalle')
    {
        if (!isset($data[0])) {
            return [];
        }
        $id = array_keys($data[0])[0];
        $item = null;
        $items = [];
        foreach ($data as &$d) {
            // primer item de datos
            if ($item === null) {
                $item = [];
                // armar cabecera del item, se van sacando los elementos de la
                // cabecera del item, al final quedará en los datos del item
                // original sólo el detalle
                $i = 0;
                foreach ($d as $key => $value) {
                    $item[$key] = array_shift($d);
                    if (++$i==$camposEncabezado) {
                        break;
                    }
                }
                // agregar primer detalle del item
                $item[$detalle] = [];
                $hayDatos = false;
                foreach ($d as $key => $value) {
                    if (isset($value)) {
                        $hayDatos = true;
                        break;
                    }
                }
                if ($hayDatos) {
                    $item[$detalle][] = $d;
                }
            }
            // el item es igual a uno previamente guardado
            // en este caso se extrae sólo el detalle
            else if ($item[$id] == $d[$id]) {
                $item[$detalle][] = array_slice ($d, $camposEncabezado);
            }
            // es un nuevo item
            else {
                // se agrega último item calculado al listado de items
                $items[] = $item;
                // se repite lo del primer item de datos (FIXME código duplicado)
                $item = [];
                $i = 0;
                foreach ($d as $key => $value) {
                    $item[$key] = array_shift($d);
                    if (++$i==$camposEncabezado) {
                        break;
                    }
                }
                $item[$detalle] = [];
                $hayDatos = false;
                foreach ($d as $key => $value) {
                    if (isset($value)) {
                        $hayDatos = true;
                        break;
                    }
                }
                if ($hayDatos) {
                    $item[$detalle][] = $d;
                }
            }
            unset($d);
        }
        // se agrega el último item encontrado
        $items[] = $item;
        // se entregan los items
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

    /**
     * Método que toma un arreglo asociativo de items, donde cada item contiene
     * un campo que hace referencia a otro item. De esta forma crea un árbol
     * jerárquico con los items
     * @param items Listado de items que se deben buscar y procesar
     * @param field_parent Nombre del campo en el item que tiene el "enlace" al item padre
     * @param field_childs Nombre del campo en el item donde se deben colocar los hijos del item
     * @param parent Índice del item padre (primer nivel es =null)
     * @return Arreglo asociativo con el árbol
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-07-25
     */
    public static function toTree($items, $field_parent, $field_childs, $parent = null)
    {
        // agregar items del nivel parent al árbol
        $tree_level = [];
        foreach ($items as $key => $item) {
            if ($item[$field_parent] == $parent) {
                unset($item[$field_parent]);
                $tree_level[$key] = $item;
                unset($items[$key]);
            }
        }
        // agregar subitems
        foreach ($tree_level as $key => &$item) {
            $item[$field_childs] = self::toTree($items, $field_parent, $field_childs, $key);
        }
        // entregar nivel
        return $tree_level;
    }

    /**
     * Método que convierte un árbol a un listado jerárquico
     * Listo para usar en un campo select y con la jerarquía del árbol
     * @param tree Árbol
     * @param field_name Nombre del campo que contiene el nombre/glosa del item del árbol
     * @param field_childs Nombre del campo en el item de donde se deben extraer los hijos del item
     * @return Arreglo asociativo con el árbol
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-07-25
     */
    public static function treeToList($tree, $field_name, $field_childs, $level = 0, $spaces = 3, array &$list = [])
    {
        foreach ($tree as $key => $item) {
            $name = str_repeat('&nbsp;',$level*$spaces).$item[$field_name];
            $list[$key] = $name;
            if (!empty($item[$field_childs])) {
                self::treeToList($item[$field_childs], $field_name, $field_childs, $level+1, $spaces, $list);
            }
            unset($tree[$key]);
        }
        return $list;
    }

    /**
     * Método que convierte un árbol a arreglo asociativo con la glosa con los espacios del nivel
     * Listo para usar en un campo select y con la jerarquía del árbol pero con todos los datos
     * @param tree Árbol
     * @param field_name Nombre del campo que contiene el nombre/glosa del item del árbol
     * @param field_childs Nombre del campo en el item de donde se deben extraer los hijos del item
     * @return Arreglo asociativo con el árbol y todos sus datos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-07-25
     */
    public static function treeToAssociativeArray($tree, $field_name, $field_childs, $level = 0, array &$list = [])
    {
        foreach ($tree as $key => $item) {
            if (!empty($item[$field_childs])) {
                $childs = $item[$field_childs];
            }
            unset($item[$field_childs]);
            $item['level'] = $level;
            $list[$key] = $item;
            if (!empty($childs)) {
                self::treeToAssociativeArray($childs, $field_name, $field_childs, $level+1, $list);
            }
            unset($tree[$key]);
        }
        return $list;
    }

}
