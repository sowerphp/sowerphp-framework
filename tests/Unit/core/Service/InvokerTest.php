<?php

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
use sowerphp\core\Service_Invoker;

/**
 * Test de Service_Invoker.
 */
class Service_InvokerTest extends TestCase
{
    protected $invokerService;

    protected function setUp(): void
    {
        // Crear instancia de Service_Invoker.
        $appType = 'http';
        $appFullBoot = false;
        $app = App::getInstance($appType, $appFullBoot);
        $this->invokerService = new Service_Invoker($app);
    }

    public function testCall()
    {
        // Crear una instancia de la clase de pruebas.
        $testInstance = new TestClass();

        // Ejecutar test de Service_Invoker::call().
        $result = $this->invokerService->call($testInstance, 'calledMethod');

        // Validar test.
        $this->assertEquals('called result', $result);
    }

    public function testInvoke()
    {
        // Ejecutar test de Service_Invoker::invoke().
        list($testInstance, $result) = $this->invokerService->invoke(
            TestClass::class,
            'invokedMethod'
        );

        // Validar test.
        $this->assertEquals('invoked result', $result);
        $this->assertEquals(TestClass::class, get_class($testInstance));
        $this->assertEquals(3, $testInstance->getInvokedCounter());
    }
}

/**
 * Clase de prueba para las llamadas mediante Service_Invoker.
 */
final class TestClass
{
    protected $invokedCounter = 0;

    public function calledMethod(): string
    {
        return 'called result';
    }

    public function invokedMethod()
    {
        $this->invokedCounter++;
        return 'invoked result';
    }

    public function boot()
    {
        $this->invokedCounter++;
    }

    public function terminate()
    {
        $this->invokedCounter++;
    }

    public function getInvokedCounter()
    {
        return $this->invokedCounter;
    }
}
