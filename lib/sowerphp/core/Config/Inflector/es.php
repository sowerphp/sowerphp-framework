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
 * @file es.php
 * Reglas de español para clase Inflector
 * @author http://joecabezas.tumblr.com/post/572538183/espanolizando-cakephp-mediante-inflections-version
 * @version 2014-05-10
 */

// reglas para pasar de plural a singular
Utility_Inflector::rules('singular', [
    'rules' => [
        '/bles$/i' => 'ble', // by DeLaF
        '/ses$/i' => 's', // by DeLaF
        '/([r|d|j|n|l|m|y|z])es$/i' => '\1',
        '/as$/i' => 'a',
        '/([ti])a$/i' => '\1a'
    ],
    'irregular' => [],
    'uninflected' => [],
]);

// reglas para pasar de singular a plural
Utility_Inflector::rules('plural', [
    'rules' => [
        '/([r|d|j|n|l|m|y|z])$/i' => '\1es',
        '/a$/i' => '\1as'
    ],
    'irregular' => ['pais'=>'paises'], // by DeLaF
    'uninflected' => [],
]);
