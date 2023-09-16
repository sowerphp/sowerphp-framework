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
 * @file core.php
 * Configuración estándar de las páginas o aplicaciones
 * @version 2023-09-16
 */

// IMPORTANTE: ¡¡¡ESTO SE DEBE MODIFICAR EN LA CONFIGURACIÓN DEL PROYECTO!!! (NO ACÁ)

// Errores
Configure::write('debug', true);
Configure::write('error.level', E_ALL & ~E_DEPRECATED & ~E_STRICT);

// Tiempo
Configure::write('time.zone', 'America/Santiago');
Configure::write('time.format', 'Y-m-d H:i:s');

// Lenguaje de la página
Configure::write('language', 'es');

// Extensiones para las páginas que se desean renderizar
Configure::write('page.extensions', ['php', 'md']);

// Página inicial
Configure::write('homepage', '/inicio');

// tiempo de expiración de la sesión en minutos
Configure::write('session.expires', 30);
