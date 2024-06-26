<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
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

// Instancia del servicio de enrutamiento.
$router = router();

// Rutas para iniciar y cerrar sesión.
$router->connect('/usuarios/ingresar', array(
    'module' => 'Sistema.Usuarios',
    'controller' => 'usuarios',
    'action' => 'ingresar',
));
$router->connect('/usuarios/ingresar/*', array(
    'module' => 'Sistema.Usuarios',
    'controller' => 'usuarios',
    'action' => 'ingresar',
));
$router->connect('/usuarios/salir', array(
    'module' => 'Sistema.Usuarios',
    'controller' => 'usuarios',
    'action' => 'salir',
));

// Rutas del perfil de usuario.
$router->connect('/usuarios/perfil', array(
    'module' => 'Sistema.Usuarios',
    'controller' => 'usuarios',
    'action' => 'perfil',
));

// Rutas para la recuperación de contraseña.
$router->connect('/usuarios/contrasenia/recuperar', array(
    'module' => 'Sistema.Usuarios',
    'controller' => 'usuarios',
    'action' => 'contrasenia_recuperar',
));
$router->connect('/usuarios/contrasenia/recuperar/*', array(
    'module' => 'Sistema.Usuarios',
    'controller' => 'usuarios',
    'action' => 'contrasenia_recuperar',
));

// Ruta para registrar una cuenta de usuario.
$router->connect('/usuarios/registrar', array(
    'module' => 'Sistema.Usuarios',
    'controller' => 'usuarios',
    'action' => 'registrar',
));

// Rutas para preautenticación.
$router->connect('/usuarios/preauth', array(
    'module' => 'Sistema.Usuarios',
    'controller' => 'usuarios',
    'action' => 'preauth',
));
$router->connect('/usuarios/preauth/*', array(
    'module' => 'Sistema.Usuarios',
    'controller' => 'usuarios',
    'action' => 'preauth',
));
