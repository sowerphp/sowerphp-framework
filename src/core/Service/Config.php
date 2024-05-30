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

namespace sowerphp\core;

class Service_Config implements Interface_Service
{

    protected $config = [];

    // Dependencias de otros servicios.
    protected $layersService;
    protected $moduleService;

    public function __construct(Service_Layers $layersService, Service_Module $moduleService)
    {
        $this->layersService = $layersService;
        $this->moduleService = $moduleService;
    }

    public function register()
    {
    }

    public function boot()
    {
        $this->loadEnvironmentVariables();
        $this->loadConfigurations();
        $this->configure();
    }

    /**
     * Cargar las variables de entorno desde el archivo .env
     */
    protected function loadEnvironmentVariables(): void
    {
        $env_file = $this->layersService->getProjectDir();
        $env = \Dotenv\Dotenv::createMutable($env_file, '.env');
        try {
            $env->load();
        } catch (\Dotenv\Exception\InvalidPathException $e) {
            die($e->getMessage());
        } catch (\Dotenv\Exception\InvalidFileException $e) {
            die($e->getMessage());
        }
    }

    /**
     * Cargar los archivos de configuración de la aplicación.
     */
    protected function loadConfigurations(): void
    {
        // Cargar las configuraciones que existen en cada ruta.
        $paths = $this->layersService->getPathsReverse();
        foreach ($paths as $path) {
            $filepath = $path . '/App/config.php';
            if (is_readable($filepath)) {
                $this->loadConfiguration($filepath);
            }
        }
        // Cargar las configuraciones del directorio project:/config/
        $configDir = $this->layersService->getProjectDir('/config');
        foreach (glob($configDir . '/*.php') as $filepath) {
            $this->loadConfiguration($filepath);
        }
    }

    /**
     * Cargar un archivo de configuración específico.
     */
    public function loadConfiguration(string $filepath): void
    {
        $key = basename($filepath, '.php');
        $config = require $filepath;
        if ($config === false) {
            return;
        }
        if (!is_array($config)) {
            $message = sprintf(
                'La configuración %s debe retornar un arreglo.',
                $filepath
            );
            die($message);
        }
        if (!empty($config['modules'])) {
            $this->moduleService->registerModule($config['modules']);
            unset($config['modules']);
        }
        if ($key != 'config') {
            $this->set($key, $config);
        } else {
            $this->set($config);
        }
    }

    /**
     * Método que configura la aplicación.
     */
    protected function configure(): void
    {
        // Límites para ejecución (tiempo y memoria).
        if ($this->get('max_execution_time')) {
            ini_set('max_execution_time', $this->get('max_execution_time'));
        }
        if ($this->get('memory_limit')) {
            ini_set('memory_limit', $this->get('memory_limit'));
        }

        // Configuración de errores y su manejo en PHP.
        ini_set('display_errors', $this->get('debug'));
        error_reporting($this->get('error.level'));

        // Definir la zona horaria.
        date_default_timezone_set($this->get('time.zone'));

        // Cargar reglas de Inflector para el idioma de la aplicación.
        $inflector_rules = (array)$this->get(
            'inflector.' . $this->get('language')
        );
        foreach ($inflector_rules as $type => $rules) {
            Utility_Inflector::rules($type, $rules);
        }

        // Asignar handler para triggers de la aplicación.
        Trigger::setHandler($this->get('app.trigger_handler'));
    }

    /**
     * Método que reconfigura la aplicación.
     */
    public function reconfigure(): void
    {
        $this->configure();
    }

    /**
     * Asignar un valor en la configuración.
     * Se puede pasar un arreglo con la configuración como un solo parámetro.
     * @param string|array $config Ubicación de la configuración o arreglo con
     * la configuración.
     * @param mixed $value Valor que se quiere guardar.
     */
    public function set($config, $value = null): void
    {
        // Si config no es arreglo se crea como arreglo.
        if (!is_array($config)) {
            $config = [$config => $value];
        }
        // Guardar cada una de las configuraciones pasadas.
        foreach ($config as $selector => $value) {
            $segments = explode('.', $selector);
            $current = &$this->config;
            foreach ($segments as $segment) {
                if (!isset($current[$segment]) || !is_array($current[$segment])) {
                    $current[$segment] = [];
                }
                $current = &$current[$segment];
            }
            $current = $value;
        }
    }

    /**
     * Leer un valor desde la configuración.
     * @param string $selector Variable / parámetro que se desea leer.
     * @param mixed $default Valor por defecto de la variable buscada.
     * @return mixed Valor determinado de la variable (real, defecto o null).
     */
    public function get(string $selector, $default = null)
    {
        $segments = explode('.', $selector);
        $config = $this->config;
        foreach ($segments as $segment) {
            if (isset($config[$segment])) {
                $config = $config[$segment];
            } else {
                return $default;
            }
        }
        return $config;
    }

}
