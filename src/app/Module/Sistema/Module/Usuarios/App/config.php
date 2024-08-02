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

return [

    'modules.Sistema.Usuarios' => [

        // Título del módulo.
        'title' => 'Administración de usuarios',

        // Menú para el módulo.
        'nav' => [
            '/usuarios' => [
                'name' => 'Usuarios',
                'desc' => 'Usuarios del sistema',
                'icon' => 'fa fa-user',
            ],
            '/grupos' => [
                'name' => 'Grupos',
                'desc' => 'Grupos del sistema',
                'icon' => 'fa fa-users',
            ],
            '/usuario_grupos' => [
                'name' => 'Usuarios y grupos',
                'desc' => 'Pertenencia de usuarios a grupos',
                'icon' => 'fa fa-users',
            ],
            '/auths' => [
                'name' => 'Autorización',
                'desc' => 'Autorización y control de acceso sobre recursos',
                'icon' => 'fa fa-lock',
            ],
            '/email/grupos' => [
                'name' => 'Enviar email a grupos',
                'desc' => 'Enviar correo electrónico a todos los usuarios de uno o varios grupos',
                'icon' => 'fas fa-paper-plane',
            ],
        ],

    ],

];
