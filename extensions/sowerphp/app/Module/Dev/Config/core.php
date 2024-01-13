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

/**
 * Configuración del módulo
 */

// Menú para el módulo
\sowerphp\core\Configure::write('nav.module', array(
    '/bd/tablas' => array(
        'name' => 'Listado de tablas',
        'desc' => 'Información de las tablas de la base de datos',
        'icon' => 'fa fa-database',
    ),
    '/bd/poblar' => array(
        'name' => 'Poblar tablas',
        'desc' => 'Cargar datos a tablas de la base de datos',
        'icon' => 'fa fa-upload',
    ),
    '/bd/descargar' => array(
        'name' => 'Descargar datos de tablas',
        'desc' => 'Descargar datos de tablas de la base de datos',
        'icon' => 'fa fa-download',
    ),
    '/bd/consulta' => array(
        'name' => 'Ejecutar consulta',
        'desc' => 'Ejecutar consulta SQL en la base de datos',
        'icon' => 'fa fa-code',
    ),
));
