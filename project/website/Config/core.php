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

/**
 * @file core.php
 * Configuración propia de cada proyecto
 * @version 2018-10-15
 */

// Tema de la página (diseño)
\sowerphp\core\Configure::write('page.layout', 'harbor');

// Textos de la página
\sowerphp\core\Configure::write('page.header.title', 'Proyecto con SowerPHP');
\sowerphp\core\Configure::write('page.body.title', 'Proyecto web');
\sowerphp\core\Configure::write('page.footer', [
    'left' => '&copy; <a href="http://sowerphp.org">SowerPHP</a> 2014 - '.date('Y').'<br/><span class="small">Framework PHP hecho en Chile</span>',
    'right' => 'Un proyecto apoyado por <a href="https://sasco.cl">SASCO SpA</a>'
]);

// Menú principal del sitio web
\sowerphp\core\Configure::write('nav.website', [
    //'/contacto' => 'Contacto', // (extensión: sowerphp/general)
]);

// Configuración para el correo electrónico
/*\sowerphp\core\Configure::write('email.default', [
    'type' => 'smtp',
    'host' => 'ssl://smtp.gmail.com',
    'port' => 465,
    'user' => '',
    'pass' => '',
    'from' => ['email'=>'', 'name'=>''],
    'to' => '',
]);*/

// Configuración para la base de datos
/*\sowerphp\core\Configure::write('database.default', [
    'type' => 'PostgreSQL',
    'user' => '',
    'pass' => '',
    'name' => '',
]);*/

// Módulos que se utilizarán en la aplicación
/*\sowerphp\core\Module::uses([
    '',
]);*/

// Menú principal de la aplicación (extensión: sowerphp/app)
/*\sowerphp\core\Configure::write('nav.app', [
    '/sistema'=>'Sistema'
]);*/

// Configuración para autorización secundaria (extensión: sowerphp/app)
/*\sowerphp\core\Configure::write('auth2', [
    'name' => 'Latch',
    'url' => 'https://latch.elevenpaths.com',
    'app_id' => '',
    'app_key' => '',
    'default' => false,
]);*/

// Configuración para reCAPTCHA (extensión: sowerphp/app)
/*\sowerphp\core\Configure::write('recaptcha', [
    'public_key' => '',
    'private_key' => '',
]);*/

// Configuración para auto registro de usuarios (extensión: sowerphp/app)
/*\sowerphp\core\Configure::write('app.self_register', [
    'groups' => [],
]);*/
