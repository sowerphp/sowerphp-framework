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

app('config')->set([

    // Menú para el módulo
    'nav.module' => [
        '/comunas/listar' => [
            'name' => 'Comunas',
            'icon' => 'fas fa-map-marker-alt',
        ],
        '/provincias/listar' => [
            'name' => 'Provincias',
            'icon' => 'fa fa-map',
        ],
        '/regiones/listar' => [
            'name' => 'Regiones',
            'icon' => 'fa fa-globe',
        ],
    ],

]);
