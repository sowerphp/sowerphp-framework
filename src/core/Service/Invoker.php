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

namespace sowerphp\core;

/**
 * Servicio para invocar métodos en instancias de clases con inyección de
 * dependencias, proporcionando un enfoque flexible y reutilizable para
 * ejecutar métodos con o sin parámetros predefinidos.
 */
class Service_Invoker
{

    /**
     * Contenedor de servicios para resolver dependencias.
     */
    protected $container;

    /**
     * Constructor del servicio Invoker.
     *
     * @param $container El contenedor de servicios para resolver dependencias.
     */
    public function __construct(App $app)
    {
        $this->container = $app->getContainer();
    }

    /**
     * Resuelve las dependencias de un método y lo ejecuta dinámicamente en
     * una instancia de clase.
     *
     * @param string $class Nombre completo de la clase que contiene el método.
     * @param string $method Nombre del método a invocar en la clase.
     * @param array $parameters Parámetros para resolver los del método.
     * @return mixed Resultado de la invocación del método.
     */
    public function invoke(string $class, string $method, array $parameters = [])
    {
        // Obtener instancia, método principal y parámetros.
        $instance = $this->container->make($class);
        $instanceMethod = new \ReflectionMethod($instance, $method);
        $params = $this->resolveParameters($instanceMethod, $parameters);

        // Ejecutar métodos de inicialización, principal y término.
        $this->invokeIfExists($instance, 'boot', [$method] + $params);
        $result = $instanceMethod->invokeArgs($instance, $params);
        $this->invokeIfExists($instance, 'terminate', [$method] + $params);

        // Entregar instancia y resultado de la ejecución del método principal.
        return [$instance, $result];
    }

    /**
     * Intenta invocar un método si existe en la instancia dada,
     * con o sin parámetros.
     *
     * @param object $instance Instancia de la clase.
     * @param string $methodName Nombre del método a invocar.
     * @param array $params Parámetros para el método.
     */
    protected function invokeIfExists($instance, string $methodName, array $params = [])
    {
        try {
            $method = new \ReflectionMethod($instance, $methodName);
            if (!empty($params)) {
                return $method->invokeArgs($instance, $params);
            } else {
                return $method->invoke($instance);
            }
        } catch (\ReflectionException $e) {
            // Método no existe, no se hace nada.
        }
    }

    /**
     * Resuelve los parámetros para un método utilizando el contenedor.
     *
     * @param \ReflectionMethod $method Método para resolver los parámetros.
     * @param array $parameters Parámetros pasados externamente.
     * @return array Parámetros resueltos para el método.
     */
    protected function resolveParameters(\ReflectionMethod $method, array $parameters)
    {
        $params = [];
        $i = 0;
        foreach ($method->getParameters() as $param) {
            $type = $param->getType() ? $param->getType()->getName() : null;
            if ($type && class_exists($type)) {
                $params[] = $this->container->make($type);
            } else {
                $params[] = $parameters[$param->getName()]
                            ?? $parameters[$i]
                            ?? (
                                $param->isDefaultValueAvailable()
                                    ? $param->getDefaultValue()
                                    : null
                            )
                ;
            }
            $i++;
        }
        return $params;
    }

}
