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

return [

    // Errores.
    'debug' => true,
    'error.level' => E_ALL & ~E_DEPRECATED & ~E_STRICT,
    'error.exception' => true,

    // Tiempo.
    'time.zone' => 'America/Santiago',
    'time.format' => 'Y-m-d H:i:s',

    // Lenguaje de la página.
    'language' => 'es',

    // Extensiones para las páginas que se desean renderizar.
    'page.extensions' => ['php', 'md'],

    // Página inicial.
    'homepage' => '/inicio',

    // tiempo de expiración de la sesión en minutos.
    'session.expires' => 30,

    // Reglas para pasar de la clase Inflector en español.
    'inflector.es' => [
        'singular' => [
            'rules' => [
                '/bles$/i' => 'ble',
                '/ses$/i' => 's',
                '/([r|d|j|n|l|m|y|z])es$/i' => '\1',
                '/as$/i' => 'a',
                '/([ti])a$/i' => '\1a'
            ],
            'irregular' => [],
            'uninflected' => [],
        ],
        'plural' => [
            'rules' => [
                '/([r|d|j|n|l|m|y|z])$/i' => '\1es',
                '/a$/i' => '\1as'
            ],
            'irregular' => ['pais' => 'paises'],
            'uninflected' => [],
        ],
    ],

    // Tema de la página (diseño/layout) por defecto
    'page.layout' => 'bootstrap',

    // Configuración para la base de datos
    /*'database.default' => [
        'type' => 'PostgreSQL',
        'user' => '',
        'pass' => '',
        'name' => '',
    ],*/

    // Configuración para el correo electrónico
    /*'email.default' => [
        'type' => 'smtp',
        'host' => 'ssl://smtp.gmail.com',
        'port' => 465,
        'user' => '',
        'pass' => '',
        'from' => [
            'email' => '',
            'name' => ''
        ],
        'to' => '',
    ],*/

];
