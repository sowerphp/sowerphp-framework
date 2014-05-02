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
 * @file core.php
 * Configuración propia de cada proyecto
 * @version 2014-05-01
 */

// Tema de la página (diseño)
\sowerphp\core\Configure::write('page.layout', 'SimpleLight');

// Textos de la página
\sowerphp\core\Configure::write('page.header.title', 'SowerPHP');
\sowerphp\core\Configure::write('page.body.title', 'SowerPHP');

// Menú principal del sitio web
\sowerphp\core\Configure::write('nav.website', array(
    '/inicio'=>'Inicio',
));

// Configuración para la base de datos
/*\sowerphp\core\Configure::write('database.default', array(
    'type' => 'PostgreSQL',
    'user' => '',
    'pass' => '',
    'name' => '',
));*/

// Configuración para el correo electrónico
/*\sowerphp\core\Configure::write('email.default', array(
    'type' => 'smtp',
    'host' => 'ssl://smtp.gmail.com',
    'port' => 465,
    'user' => '',
    'pass' => '',
    'from' => array('email'=>'', 'name'=>''),
    'to' => '',
));*/
