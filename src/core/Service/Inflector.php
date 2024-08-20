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

namespace sowerphp\core;

use Doctrine\Inflector\InflectorFactory;

/**
 * Servicio inflector para pluralizar y singularizar palabras.
 */
class Service_Inflector  implements Interface_Service
{

    /**
     * Servicio de configuración.
     *
     * @var Service_Config
     */
    protected $configService;

    /**
     * Instancia del inflector de Doctrine.
     *
     * @var \Doctrine\Inflector\InflectorFactory
     */
    protected $inflector;

    public function __construct(Service_Config $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Registra el servicio de inflector.
     *
     * @return void
     */
    public function register(): void
    {
        $this->inflector = InflectorFactory::create()->build();
    }

    /**
     * Inicializa el servicio de inflector.
     *
     * @return void
     */
    public function boot(): void
    {
        // Asignar el estado inicial.
        Utility_Inflector::reset();
        // Cargar reglas de Inflector para el idioma de la aplicación.
        $inflector_rules = (array)$this->configService->get(
            'inflector.' . $this->configService->get('app.locale')
        );
        foreach ($inflector_rules as $type => $rules) {
            Utility_Inflector::rules($type, $rules);
        }
    }

    /**
     * Finaliza el servicio de inflector.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Pasar un string de plural a singular.
     *
     * @param string $string String en formato plural.
     * @return string String en formato singular.
     */
    public function singularize(string $string): string
    {
        return Utility_Inflector::singularize($string);
    }

    /**
     * Pasar un string de singular a plural.
     *
     * @param string $string String en formato singular.
     * @return string String en formato plural.
     */
    public function pluralize(string $string): string
    {
        return Utility_Inflector::pluralize($string);
    }

    /**
     * Cualquier método que no esté definido en el servicio será llamado en la
     * instancia de inflector.
     *
     * Ejemplos de métodos del administrador de la sesión que se usarán:
     *   - singularize()
     *   - pluralize()
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->inflector, $method], $parameters);
    }

}
