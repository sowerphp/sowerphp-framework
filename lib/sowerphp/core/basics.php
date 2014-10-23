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

/**
 * @file basics.php
 * Archivo de funciones básicas para la aplicación
 * @version 2014-10-01
 */

/**
 * Función para mostrar el valor de una variable (y su tipo) o un objeto (y su
 * clase)
 * @param var Variable que se desea mostrar
 * @param withtype Si es verdadero se usará "var_dump" sino "print_r"
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-04-03
 */
function debug ($var, $withtype = false)
{
    // solo si es página web se usa TAG pre
    if (isset($_SERVER['REQUEST_URI'])) {
        echo '<pre>';
        if ($withtype) var_dump($var);
        else print_r($var);
        echo '</pre>',"\n";
    } else {
        if ($withtype) var_dump($var);
        else print_r($var);
        echo "\n";
    }
}

/**
 * Función para formatear números
 * @param n Número a formatear
 * @param d Cantidad de decimales
 * @return String Número formateado
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-10-23
 */
function num ($n, $d=0)
{
    return number_format((float)$n, $d, ',', '.');
}

/**
 * Función para traducción de string singulares, en dominio master.
 * @param string Texto que se desea traducir
 * @param args Argumentos para reemplazar en el string, puede ser un arreglo o bien n argumentos a la función
 * @return Texto traducido
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-16
 */
function __ ($string, $args = null)
{
    if (!is_array($args)) {
        $args = array_slice(func_get_args(), 1);
    }
    return __d ('master', $string, $args);
}

/**
 * Función para traducción de string singulares, eligiendo dominio.
 * @param string Texto que se desea traducir
 * @param args Argumentos para reemplazar en el string, puede ser un arreglo o bien n argumentos a la función
 * @return Texto traducido
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-03
 */
function __d ($dominio, $string, $args = null)
{
    // si no hay argumentos solo se retorna el texto traducido
    if (!$args) {
        return \sowerphp\core\I18n::translate($string, $dominio);
    }
    // si los argumentos no son un arreglo se obtiene arreglo a partir
    // de los argumentos pasados a la función
    if (!is_array($args)) {
        $args = array_slice(func_get_args(), 2);
    }
    return vsprintf(\sowerphp\core\I18n::translate($string, $dominio), $args);
}
