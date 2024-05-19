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

/**
 * Clase principal de la aplicación.
 * Se encarga de:
 *   - Coordinar toda la ejecución de la aplicación.
 *      - Inicializar la aplicación.
 *      - Configuraciones iniciales.
 *      - Despachar la ejecución.
 *   - Cargar otras clases y/o archivos de manera automágica.
 */
class App
{

    // Trait para poder usar en la clase un contenedor de servicios.
    use Trait_ServiceContainer;

    // Atributo estático para mantener una sola instancia de esta clase.
    private static $instance;

    // Mapa de capas y su ubicación.
    // Cada capa tiene su propio namespace base, que puede o no tener
    // otros namespaces en subniveles. Si existen namespaces inferiores
    // serán módulos de la capa.
    private $layers;

    // Rutas donde se buscarán los archivos de la aplicación.
    private $paths;
    private $pathsReverse;

    /**
     * Constructor de la clase App de la aplicación.
     * Es protected para evitar la instanciación directa (desde fuera).
     */
    protected function __construct()
    {
        // Sin código, existe para no ser instanciado de afuera.
    }

    /**
     * Método que se asegura de entregar una única instancia de la clase.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Método que ejecuta la aplicación web.
     *  - Inicializa.
     *  - Configura.
     *  - Despacha la aplicación HTTP o CLI.
     */
    public function run(): int
    {
        // Inicializar y configurar aplicación.
        $this->init();
        $this->configure();
        // Ejecutar como aplicación de línea de comandos.
        if (php_sapi_name() === 'cli') {
            global $argv;
            return Shell_Exec::run($argv);
        }
        // Ejecutar como aplicación web (HTTP).
        else {
            Model_Datasource_Session::start(
                $this->getService('config')->get('session.expires')
            );
            Model_Datasource_Session::configure();
            \sowerphp\core\Routing_Dispatcher::dispatch();
            return 0;
        }
    }

    /**
     * Entrega las rutas registradas según las capas definidas.
     * @return array Las rutas registradas.
     */
    public function paths(bool $reverse = false): array
    {
        // Entregar los paths en orden invertido.
        // Desde la capa inferior a la capa superior.
        if ($reverse) {
            if (!isset($this->pathsReverse)) {
                $this->pathsReverse = array_reverse(
                    $this->paths(false)
                );
            }
            return $this->pathsReverse;
        }
        // Entregar los paths en el orden de las capas.
        // Desde la capa superior a la capa inferior.
        else {
            if (!isset($this->paths)) {
                $this->paths = array_map(function($layer) {
                    return $layer['path'];
                }, $this->layers);
            }
            return $this->paths;
        }
    }

    /**
     * Entrega la ruta a la capa solicitada.
     * @param string $layer Capa que se requiere su ruta.
     * @return string|false Ruta hacia la capa.
     */
    public function layer(string $layer)
    {
        $layer = str_replace('/', '\\', $layer);
        return $this->layers[$layer] ?? false;
    }

    /**
     * Método que entrega la ubicación real de un archivo buscando a
     * partir de la ubicación base en una capa en todas las capas
     * disponibles.
     * Si es un módulo, se debe incluir como parte del nombre del
     * archivo la ubicación del módulo. Ya que sólo se busca en las
     * rutas de las capas.
     * @param string $filename Archivo que se está buscando en las capas.
     * @return string|false Ruta del archivo si fue encontrado (falso si no).
     */
    public function location(string $filename)
    {
        foreach ($this->paths() as $path) {
            $filepath = $path . ($filename[0] != '/' ? '/' : '') . $filename;
            if (is_readable($filepath)) {
                return $filepath;
            }
        }
        return false;
    }

    /**
     * Método para autocarga de clases que no están cubiertas por composer.
     * @param string $class Clase que se desea cargar.
     * @param bool $loadAlias =true si no se encuentra la clase se
     * buscará un alias para la misma.
     * @return bool =true si se encontró la clase.
     */
    public function loadClass(string $class, bool $loadAlias = true): bool
    {
        if (strpos($class, '\\') !== false) {
            return class_exists($class);
        }
        if ($loadAlias && self::loadClassAlias($class)) {
            return true;
        }
        return false;
    }

    /**
     * Método que busca una clase en las posibles ubicaciones (capas y módulos)
     * y entrega el nombre de la clase con su namespace correspondiente.
     * @param string $class Clase que se está buscando.
     * @param string $module Módulo donde buscar la clase (si existe uno).
     * @return string Entrega el FQN de la clase (o sea con el namespace completo).
     */
    public function findClass(string $class, ?string $module = null): string
    {
        $file = str_replace ('_', '/', $class) . '.php';
        foreach ($this->layers as $namespace => $layer) {
            if (!$module) {
                $fileLocation = $layer['path'] .'/' . $file;
                if (file_exists($fileLocation)) {
                    return $namespace . '\\' . $class;
                }
            } else {
                $fileLocation = $layer['path']. '/' . '/Module/'
                    . str_replace('.', '/Module/', $module) . '/' . $file
                ;
                if (file_exists($fileLocation)) {
                    return $namespace . '\\'
                        . str_replace('.', '\\', $module) . '\\'
                        . $class
                    ;
                }
            }
        }
        return $class;
    }

    /**
     * Método que carga clases buscándola en las layers y creando un
     * alias para el namespace donde se encuentra la clase.
     * @param string $class Clase que se quiere cargar usando un alias.
     * @return bool =true si se encontró la clase.
     */
    private function loadClassAlias(string $class): bool
    {
        // Si no se encontró o se solicitó una clase de forma directa
        // (sin el namespace) buscar si existe en alguna capa y crear
        // un alias para la clase.
        $realClass = $this->findClass($class);
        if ($realClass) {
            if ($this->loadClass($realClass, false)) {
                class_alias($realClass, $class);
                return true;
            }
        }
        // Si definitivamente no se encontró retornar falso.
        return false;
    }

    /**
     * Método que inicializa la aplicación.
     */
    private function init(): void
    {
        // Iniciar buffer.
        ob_start();

        // Asignar nivel de error máximo (para reportes previo a que se
        // asigne el valor real con $this->configure()).
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        // Definir el tiempo de inicio del script.
        define('TIME_START', microtime(true));

        // Rastreo de llamadas para encontrar el archivo de origen.
        $backtrace = debug_backtrace();
        $caller = $backtrace[count($backtrace) - 1]['file'];

        // Definir constantes con los directorios principales de la aplicación.
        define('DIR_FRAMEWORK', dirname(dirname(__DIR__)));
        define('DIR_PROJECT', dirname(dirname($caller)));
        define('DIR_STORAGE', DIR_PROJECT . '/storage');
        define('DIR_STATIC', DIR_STORAGE . '/static');

        // definir directorio temporal
        if (is_writable(DIR_STORAGE . '/tmp')) {
            define('TMP', DIR_STORAGE . '/tmp');
        } else {
            define('TMP', sys_get_temp_dir());
        }

        // Registrar el autocargador mágico de las clases.
        spl_autoload_register([$this, 'loadClass']);

        // Cargar las capas.
        $layers = require DIR_PROJECT . '/config/layers.php';
        $this->loadLayers($layers);

        // TODO: hay que dejar de usar esta constante y que sea libre
        // el directorio principal de la aplicación.
        define(
            'DIR_WEBSITE',
            $this->layer('website')['path'] ?? DIR_PROJECT . '/website'
        );

        // Registrar los proveedores de servicios de la clase App.
        $this->registerService('config', new Configure());
    }

    /**
     * Método que agrega las capas de la aplicación.
     * @param extensions Arreglo con las capas de la aplicación.
     */
    private function loadLayers(array $layers): void
    {
        $this->layers = [];
        foreach ($layers as $layer) {
            switch($layer['location']) {
                case 'framework':
                    $dir = DIR_FRAMEWORK;
                    break;
                case 'project':
                    $dir = DIR_PROJECT;
                    break;
                case 'path':
                    $dir = '';
                    break;
            }
            $this->layers[$layer['namespace']] = [
                'path' => $dir . '/' . $layer['directory'],
            ];
        }
    }

    /**
     * Método que configura la aplicación.
     */
    private function configure(): void
    {
        // Servicio de configuración:
        $config = $this->getService('config');

        // Cargar variables de entorno.
        $env = \Dotenv\Dotenv::createMutable(DIR_PROJECT, '.env');
        try {
            $env->load();
        } catch (\Dotenv\Exception\InvalidPathException $e) {
            die($e->getMessage());
        } catch (\Dotenv\Exception\InvalidFileException $e) {
            die($e->getMessage());
        }

        // Cargar los archivos de la App de cada capa.
        $this->loadFiles();

        // Setear parámetros de errores
        ini_set('display_errors', $config->get('debug'));
        error_reporting($config->get('error.level'));
        if ($config->get('error.exception')) {
            set_error_handler('sowerphp\core\Error::handler');
        }
        set_exception_handler('sowerphp\core\Exception::handler');

        // Definir la zona horaria
        date_default_timezone_set($config->get('time.zone'));

        // cargar reglas de Inflector para el idioma de la aplicación
        $inflector_rules = (array)$config->get(
            'inflector.' . $config->get('language')
        );
        foreach ($inflector_rules as $type => $rules) {
            Utility_Inflector::rules($type, $rules);
        }

        // asignar handler para triggers de la app
        Trigger::setHandler($config->get('app.trigger_handler'));
    }

    /**
     * Método que carga los archivos del directorio App de cada capa.
     */
    private function loadFiles(): void
    {
        // Archivos que se buscarán para cargar.
        $files = [
            'functions.php',
            'bootstrap.php',
            'config.php',
            'routes.php',
        ];

        // Cargar los paths en orden reverso para poder sobrescribir
        // con los archivos que se cargarán.
        $paths = $this->paths(true);

        // Incluir los archivos que existen en la carpeta App de cada capa.
        foreach ($files as $file) {
            foreach ($paths as $path) {
                $filepath = $path . '/App/' . $file;
                if (is_readable($filepath)) {
                    include $filepath;
                }
            }
        }
    }

}
