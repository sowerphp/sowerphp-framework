<?php

declare(strict_types=1);

/**
 * SowerPHP: Simple and Open Web Ecosystem Reimagined for PHP.
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

use PHPUnit\Framework\TestCase;
use sowerphp\core\App;

/**
 * Test para probar el flujo completo de la aplicación renderizando la página
 * de inicio.
 */
class HomeTest extends TestCase
{
    protected $app;

    protected function setUp(): void
    {
        $appType = 'http';
        $appFullBoot = true;
        $this->app = App::getInstance($appType, $appFullBoot);
    }

    public function testHomepage()
    {
        // TODO: escribir Test que pruebe la generación de la página de inicio.
        //$this->app->run();
        $this->assertTrue(true);
    }
}
