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

use Illuminate\Config\Repository;

class Service_Config implements Interface_Service, \ArrayAccess
{

    /**
     * Repositorio de configuración.
     *
     * @var \Illuminate\Config\Repository
     */
    protected $repository;

    // Dependencias de otros servicios.
    protected $layersService;
    protected $moduleService;

    /**
     * Constructor de Service_Config.
     *
     * @param Service_Layers $layersService
     * @param Service_Module $moduleService
     */
    public function __construct(
        Service_Layers $layersService,
        Service_Module $moduleService
    )
    {
        $this->layersService = $layersService;
        $this->moduleService = $moduleService;
        $this->repository = new Repository([]);
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->loadEnvironmentVariables();
        $this->loadConfigurations();
        $this->configure();
    }

    /**
     * Finaliza el servicio de configuración.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Cargar las variables de entorno desde el archivo .env
     */
    protected function loadEnvironmentVariables(): void
    {
        $env_file = $this->layersService->getProjectPath();
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
        $configDir = $this->layersService->getProjectPath('/config');
        foreach (glob($configDir . '/*.php') as $filepath) {
            $this->loadConfiguration($filepath);
        }
    }

    /**
     * Cargar un archivo de configuración específico.
     */
    public function loadConfiguration(string $filepath): void
    {
        // Leer configuración.
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
        // Si la configuración es de módulos se registran.
        if ($key == 'modules') {
            $config = $this->moduleService->normalizeModulesConfig($config);
            $this->moduleService->registerModule($config);
        }
        if (!empty($config['modules'])) {
            $config['modules'] = $this->moduleService->normalizeModulesConfig(
                $config['modules']
            );
            $this->moduleService->registerModule($config['modules']);
        }
        // Guardar configuración en repositorio.
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
        if ($this->get('app.php.max_execution_time')) {
            ini_set('max_execution_time', $this->get('app.php.max_execution_time'));
        }
        if ($this->get('app.php.memory_limit')) {
            ini_set('memory_limit', $this->get('app.php.memory_limit'));
        }

        // Configuración de errores y su manejo en PHP.
        ini_set('display_errors', $this->get('app.debug'));
        error_reporting($this->get('app.php.error_reporting'));

        // Definir la zona horaria.
        date_default_timezone_set($this->get('app.timezone'));

        // Cargar reglas de Inflector para el idioma de la aplicación.
        $inflector_rules = (array)$this->get(
            'inflector.' . $this->get('app.locale')
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
     * Entrega un repositorio con la configuración.
     *
     * Se puede entregar toda la configuración o bien una específica convertida
     * a repositorio de configuración.
     *
     * @param string|null $selector Variable / parámetro que se desea leer.
     * @param mixed $default Valor por defecto de la variable buscada.
     * @return Repository Repositorio con la configuración buscada.
     */
    public function getRepository(?string $selector = null, $default = null)
    {
        // Si no se especificó una configuración se entrega todo el repositorio
        // de la servicio de configuración.
        if ($selector === null) {
            return $this->repository;
        }
        // Entregar una configuración específica.
        $value = $this->get($selector, $default);
        return new Repository([$selector => $value]);
    }

    /**
     * Asignar un valor en la configuración.
     * Se puede pasar un arreglo con la configuración como un solo parámetro.
     *
     * @param string|array $config Ubicación de la configuración o arreglo con
     * la configuración.
     * @param mixed $value Valor que se quiere guardar.
     * @return void
     */
    public function set($config, $value = null): void
    {
        // Si $config es un arreglo se considera cada una de las llaves del
        // arreglo como la llave que se desea guardar y se vuelven a pasar al
        // método set(). Es este caso $value no tendrá un valor que se use.
        if (is_array($config)) {
            foreach ($config as $key => $val) {
                $this->set($key, $val);
            }
        }
        // Guardar la configuración pasada mediante la llave $config.
        else {
            // Si $val es un arreglo, se obtiene su valor para hacer un merge.
            if (is_array($value)) {
                $value = array_merge((array)$this->get($config), $value);
            }
            $this->repository->set($config, $value);
        }
    }

    /**
     * Verifica si un índice existe en el repositorio.
     *
     * @param mixed $offset Índice a verificar.
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->repository[$offset]);
    }

    /**
     * Obtiene el valor de un índice en el repositorio.
     *
     * @param mixed $offset Índice a obtener.
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->repository[$offset];
    }

    /**
     * Asigna un valor a un índice en el repositorio.
     *
     * @param mixed $offset Índice a asignar.
     * @param mixed $value Valor a asignar.
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->repository[$offset] = $value;
    }

    /**
     * Elimina un índice del repositorio.
     *
     * @param mixed $offset Índice a eliminar.
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->repository[$offset]);
    }

    /**
     * Cualquier método que no esté definido en el servicio será llamado en el
     * repositorio de la configuración.
     *
     * Ejemplos de métodos del repositorio que se usarán:
     *
     *   - has()
     *   - get()
     *   - prepend()
     *   - push()
     *   - all()
     *
     * El método set() está definido acá pues su implementación difiere a la
     * del repositorio.
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->repository, $method], $parameters);
    }

}
