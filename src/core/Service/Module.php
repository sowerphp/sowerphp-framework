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

class Service_Module implements Interface_Service
{

    /**
     * Aplicación.
     *
     * @var App
     */
    protected $app;

    /**
     * Servicio de capas de la aplicación.
     *
     * @var Service_Layers
     */
    protected $layersService;

    /**
     * Servicio de configuración.
     *
     * @var Service_Config
     */
    protected $configService;

    /**
     * Listado de modulos cargados.
     *
     * @var array
     */
    protected $modules = [];

    /**
     * Contructor del servicio.
     *
     * @param App $app
     * @param Service_Layers $layersService
     */
    public function __construct(App $app, Service_Layers $layersService)
    {
        $this->app = $app;
        $this->layersService = $layersService;
    }

    /**
     * Registra el servicio de modulos.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio de modulos.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->configService = $this->app->getService('config');
    }

    /**
     * Finaliza el servicio de módulos.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Registrar un módulo para su uso.
     *
     * @param string|array $module Nombre del módulo o un arreglo de módulos
     * con sus configuraciones.
     * @param array $config Arreglo con configuración del módulo.
     */
    public function registerModule($module, array $config = []): void
    {
        // Si se paso un arreglo se procesa cada uno por separado.
        if (is_array($module)) {
            // Cada elemento del arreglo será un módulo.
            foreach ($module as $name => $conf) {
                // Desregistrar el módulo.
                if ($conf === false) {
                    $this->unregisterModule($name);
                }
                // Registrar el módulo.
                else {
                    $this->registerModule($name, $conf);
                }
            }
        }
        // Procesar un módulo con su configuración.
        else {
            // Asignar opciones por defecto.
            $config = array_merge([
                // Rutas del módulo donde puede ser encontrado (una o más).
                'paths' => [],
                // El módulo no se encuentra cargado.
                'loaded' => false,
            ], $config);
            // Guardar configuración del modulo y cargarlo.
            $this->modules[$module] = $config;
            $this->loadModule($module);
        }
    }

    /**
     * Desregistrar un módulo previamente registrado.
     *
     * @param string|array $module Nombre del módulo a desregistrar.
     */
    public function unregisterModule($module): void
    {
        if (!is_array($module)) {
            $module = [$module];
        }
        foreach ($module as $name) {
            unset($this->modules[$name]);
        }
    }

    /**
     * Cargar e inicializar un módulo.
     *
     * @param string $module Nombre del módulo que se desea cargar.
     * @return array Configuración del módulo cargado.
     */
    public function loadModule(string $module): array
    {
        // Si el módulo ya está cargado se retorna.
        if ($this->modules[$module]['loaded']) {
            return $this->modules[$module];
        }
        // Si no se indicó el path donde se encuentra el módulo se
        // deberá determinar, se buscarán todos los paths donde el
        // módulo pueda existir.
        if (!isset($this->modules[$module]['paths'][0])) {
            $this->modules[$module]['paths'] = [];
            $paths = $this->layersService->getPaths();
            foreach ($paths as &$path) {
                // Verificar que el directorio exista (el reemplazo es
                // para los submódulos).
                $modulePath = $path . '/Module/'
                    . str_replace('.', '/Module/', $module)
                ;
                if (is_dir($modulePath)) {
                    $this->modules[$module]['paths'][] = $modulePath;
                }
            }
        }
        // Si se indicó se verifica que exista y se agrega como único
        // path para el módulo al arreglo.
        else {
            // Si el directorio existe se agrega.
            if (is_dir($this->modules[$module]['paths'])) {
                $this->modules[$module]['paths'] = [
                    $this->modules[$module]['paths'],
                ];
            }
            // Si no existe se elimina el path para generar error
            // posteriormente.
            else {
                $this->modules[$module]['paths'] = [];
            }
        }
        // Si el módulo no fue encontrado se lanza una excepción.
        if (!isset($this->modules[$module]['paths'][0])) {
            throw new \Exception(__(
                'Módulo %s no fue encontrado.',
                $module
            ));
        }
        // Cargar archivos del módulo desde todas sus capas.
        $this->loadFiles($module, [
            '/App/helpers.php',
        ]);
        $this->loadConfigurations($module);
        // Indicar que el módulo fue cargado.
        $this->modules[$module]['loaded'] = true;
        return $this->modules[$module];
    }

    /**
     * Cargar archivos del módulo.
     *
     * Se buscan los archivos en todas las rutas posibles del módulo. Las rutas
     * se revisan en orden inverso. De tal forma que primero se cargan los
     * archivos en módulos de capas más bajas y luego los de capas superiores.
     * Esto permite sobrescribir rutas (routes) o configuraciones.
     *
     * @param string $module Módulo para el que se cargarán los archivos.
     * @param array $files Archivos que se desean cargar.
     * @return void
     */
    public function loadFiles(
        string $module,
        array $files,
        bool $reverse = false
    ): void
    {
        $paths = $reverse
            ? array_reverse($this->modules[$module]['paths'])
            : $this->modules[$module]['paths']
        ;
        foreach ($files as &$file) {
            foreach ($paths as $path) {
                $filepath = $path . $file;
                if (file_exists($filepath)) {
                    include $filepath;
                }
            }
        }
    }

    /**
     * Método que carga archivos de cada capa en el orden reverso en que las
     * capas fueron definidas.
     */
    protected function loadFilesReverse(string $module, array $files): void
    {
        $this->loadFiles($module, $files, true);
    }

    /**
     * Cargar los archivos de configuración del módulo.
     */
    protected function loadConfigurations(string $module): void
    {
        $paths = array_reverse($this->modules[$module]['paths']);
        $configLoaded = false;
        foreach ($paths as $path) {
            $filepath = $path . '/App/config.php';
            if (file_exists($filepath)) {
                $this->configService->loadConfiguration($filepath);
                $configLoaded = true;
            }
        }
        if ($configLoaded) {
            $this->configService->reconfigure();
        }
    }

    /**
     * Obtener la ubicación completa de un archivo en un módulo.
     *
     * @param string $module Nombre del módulo sobre el que se buscará.
     * @param string $filename Ruta del archivo buscado dentro del módulo.
     * @return string|null Ruta completa para el archivo solicitado o null si
     * no existe.
     */
    public function getFilePath(string $module, string $filename): ?string
    {
        $filename = ($filename[0] != '/' ? '/' : '') . $filename;
        $filename = '/Module/'
            . str_replace('.', '/Module/', $module) . $filename
        ;
        return app('layers')->getFilePath($filename);
    }

    /**
     * Verificar si un módulo está cargado.
     *
     * @param string $module Módulo que se desea verificar si está cargado.
     * @return bool
     */
    public function isModuleLoaded(string $module): bool
    {
        return isset($this->modules[$module]);
    }

    /**
     * Obtener todos los módulos cargados.
     *
     * @return array Listado de módulos cargados.
     */
    public function getLoadedModules(): array
    {
        $modules = array_keys($this->modules);
        sort($modules);
        return $modules;
    }

    /**
     * Determinar si el recurso de una URL corresponde a un módulo y entregar
     * el nombre del módulo determinado.
     *
     * @param string $url Solicitud realizada (sin la base de la aplicación).
     * @return string|null Nombre del módulo si es que existe uno en la URL.
     */
    public function findModuleByResource(string $url): ?string
    {
        // Separar por "/".
        $partes = explode('/', $url);
        // Quitar primer elemento, ya que si parte con / entonces será vacío.
        if (!strlen($partes[0])) {
            array_shift($partes);
        }
        // Determinar hasta que elemento de la url corresponde a parte de un
        // módulo.
        $npartes = count($partes);
        $hasta = -1;
        for ($i=0; $i<$npartes; ++$i) {
            // Armar nombre del modulo.
            $module = [];
            for ($j=0; $j<=$i; ++$j) {
                $module[] = Utility_Inflector::camelize($partes[$j]);
            }
            $module = implode('.', $module);
            // Determinar si dicho modulo existe.
            if (array_key_exists($module, $this->modules)) {
                $hasta = $i;
            }
        }
        // Si $hasta es mayor a -1 existe un módulo, por lo que se determina
        // su nombre y se entrega.
        if ($hasta >= 0) {
            // Armar nombre final del modulo (considerando hasta $hasta partes
            // del arreglo de partes).
            $module = [];
            for($i=0; $i<=$hasta; ++$i) {
                $module[] = Utility_Inflector::camelize($partes[$i]);
            }
            // Armar nombre del módulo como string.
            $module = implode('.', $module);
            // Retornar nombre del modulo.
            return $module;
        }
        // No se encontró módulo, por lo que se retorna null.
        else {
            return null;
        }
    }

    /**
     * Determinar si la clase tiene un módulo en su namespace y entregar
     * el nombre del módulo determinado.
     *
     * @param string $class Clase que se desea saber si tiene un módulo.
     * @return string|null Nombre del módulo si es que existe uno en la clase.
     */
    public function findModuleByClass(string $class): ?string
    {
        // Se busca el namespace de la clas y con eso se determina si tiene o
        // no modelo.
        foreach ($this->layersService->getPaths() as $namespace => $path) {
            if (strpos($class, $namespace) === 0) {
                $count = 1;
                $string = substr(str_replace($namespace, '', $class, $count), 1);
                $lastSlashPosition = strrpos($string, '\\');
                $module = str_replace('\\', '.', substr($string, 0, $lastSlashPosition));
                if ($module) {
                    return $module;
                }
            }
        }
        // No se encontró un namespace que coincidera con la clase o no
        // contenía un módulo la clase a partir del namespace de la capa.
        return null;
    }

    /**
     * Obtener las rutas donde se encuentran los módulos o un módulo en
     * específico solicitado.
     *
     * @param string $module Nombre del módulo.
     * @return array|null Rutas de los módulos o del módulo solicitado.
     */
    public function getPaths(?string $module = null): array
    {
        // Si se pidieron los paths de un módulo específico se entregan.
        if ($module !== null) {
            if (isset($this->modules[$module])) {
                return $this->modules[$module]['paths'];
            }
            return [];
        }
        // Si se pidieron todos los paths se determinan.
        $paths = [];
        foreach ($this->modules as $module => $config) {
            $paths[$module] = $config['paths'];
        }
        return $paths;
    }

    /**
     * Obtener el menú de navegación del módulo.
     *
     * @param string $module Nombre del módulo.
     * @return array Menú de navegación.
     */
    public function getModuleNav(string $module): array
    {
        $module_nav_config = config('modules.' . $module . '.nav');
        if (is_string($module_nav_config)) {
            $module_nav = config($module_nav_config);
        } else if (is_callable($module_nav_config)) {
            $module_nav = $module_nav_config();
        } else {
            $module_nav = (array)$module_nav_config;
        }
        if (!isset($module_nav[0])) {
            $module_nav = [['menu' => $module_nav]];
        }
        return $module_nav;
    }

    /**
     * Normalizador de la configuración de un módulo.
     *
     * Se preocupa de dejar la configuración posible de un módulo en el formato
     * estándar utilizado al momento de configurarlo.
     *
     * Permite que un módulo se configure de las siguientes formas:
     *
     *   a) Indicando solo su nombre:
     *      ['Sistema.Usuarios']
     *   b) Indicando su nombre y su ruta exacta donde encontrarlo:
     *      ['Sistema.Usuarios' => '/path/to/module']
     *   c) Indicando su nombre y configuración:
     *      ['Sistema.Usuarios' => ['config1' => 'valor1']]
     *   d) Indicando que un módulo debe ser descargado (si existe):
     *      ['Sistema.Usuarios' => false]
     *
     * @param array $modules
     * @return array
     */
    public function normalizeModulesConfig(array $modules): array
    {
        $normalizedModules = [];
        foreach ($modules as $module => $config) {
            // Solo se pasó nombre de módulo, sin config.
            if (is_numeric($module)) {
                $normalizedModules[$config] = [];
            }
            // Se pasó nombre de módulo como índice y su ruta como
            // configuración.
            else if (is_string($config)) {
                $normalizedModules[$module]['paths'] = [$config];
            }
            // Se pasó nombre de módulo como índice y su configuración.
            // La configuración podría ser:
            //  - array: para registrar el módulo.
            //  - false: para desregistrar el módulo.
            else {
                $normalizedModules[$module] = $config;
            }
        }
        return $normalizedModules;
    }

    /**
     * Obtiene todas las rutas de búsqueda de la aplicación.
     *
     * Por defecto se entregan todas las rutas, incluyendo capas y todos los
     * módulos y submódulos
     *
     * @param string|null $module Nombre del módulo para buscar uno específico.
     * `''` para buscar solo las capas. `null` (por defecto) para buscar todas
     * las posibles rutas de búsqueda.
     * @return array|null Rutas de búsqueda de clases de toda la aplicación.
     */
    public function getSearchPaths(?string $module = null): array
    {
        // Obtener las capas y módulos de la aplicación..
        $layers = $this->layersService->getPaths();
        $modules = $this->getPaths($module);

        // Si se pidió un módulo en específico se normaliza el resultado para
        // poder iterar luego.
        if ($module) {
            $modules = [
                $module => $modules,
            ];
        }

        // Iterar las capas para armar el arreglo de búsqueda con las rutas.
        $search = [];
        foreach ($layers as $layerNamespace => $layerPath) {
            // Agregar la capa como primera prioridad. Siempre lo que esté
            // en la capa tendrá la máxima prioridad (más que un módulo).
            if (!$module) {
                $search[] = [
                    'path' => $layerPath,
                    'module' => null,
                    'namespace' => $layerNamespace,
                ];
            }
            // Iterar los módulos encontrados (si existen) y extraer las rutas
            // que coincidan con la capa que estamos iterando.
            foreach ($modules as $moduleName => $modulePaths) {
                foreach ($modulePaths as $modulePath) {
                    if (strpos($modulePath, $layerPath) === 0) {
                        $moduleNamespace = $layerNamespace . '\\' .
                            str_replace('.', '\\', $moduleName)
                        ;
                        $search[] = [
                            'path' => $modulePath,
                            'module' => $moduleName,
                            'namespace' => $moduleNamespace,
                        ];
                    }
                }
            }
        }

        // Entregar todas las rutas de búsqueda encontradas por prioridad.
        return $search;
    }

    /**
     * Busca todas las clases en un determinado directorio en todas las rutas
     * de búsqueda de la aplicación. Además, realizará la carga (require) de
     * todos los archivos PHP encontrados (sean o no clases).
     *
     * @param string $searchDir Directorio dentro de la ruta de búsqueda. Si no
     * se indica un directorio se buscará en toda la ruta de búsqueda.
     * @param string|null $module Nombre del módulo para buscar uno específico.
     * `''` para buscar solo las capas. `null` (por defecto) para buscar todas
     * las posibles rutas de búsqueda.
     * @return array Arreglo con todas las clases encontradas en el directorio
     * de búsqueda de acuerdo a la prioridad de las rutas de búsqueda.
     */
    public function searchAndLoadClasses(?string $searchDir = null, ?string $module = null): array
    {
        // Si el directorio de búsqueda es el directorio actual se quita para
        // armar el directorio de búsqueda de manera correcta.
        if ($searchDir == '.') {
            $searchDir = '';
        }

        // Se obtienen todos los directorios de búsqueda solicitados.
        $searchPaths = $this->getSearchPaths($module);

        // Iterar buscando las clases en cada ruta de búsqueda.
        $classes = [];
        foreach ($searchPaths as $searchPath) {
            $basePath = $searchPath['path'] . '/' . $searchDir;
            try {
                $directoryIterator = new \RecursiveDirectoryIterator($basePath);
            } catch (\UnexpectedValueException $e) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator($directoryIterator);
            foreach ($iterator as $file) {
                // Si no es un archivo PHP se omite el archivo.
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                // Si el archivo está dentro del directorio App de la ruta de
                // búsqueda se omite, pues ahí no hay clases.
                $filePath = $file->getRealPath();
                $appPath = $basePath . 'App/';
                if (strpos($filePath, $appPath) === 0) {
                    continue;
                }

                // Se arma el nombre base de la clase (sin namespace). Esto
                // asume que es una clase el archivo PHP, y puede no ser
                // cierto, pues es el caso que nos interesa buscar: clases.
                $className = sprintf(
                    '%s_%s',
                    str_replace('/', '_', $searchDir),
                    $file->getBasename('.php')
                );

                // Si la clase ya había sido encontrada en otra ruta se omite.
                if (isset($classes[$className])) {
                    continue;
                }

                // Se incluye el archivo PHP de la posible clase. Esto se hace
                // solo si el archivo no había sido importado previamente.
                // Es necesario esto porque la aplicación podría ya tener el
                // archivo cargado previamente por alguna dependencia, servicio
                // u algo más que haya requerido que se cargase antes de que se
                // ejecute esta búsqueda o porque algun archivo de esta misma
                // búsqueda ya hizo que se cargase el archivo.
                $includedFiles = get_included_files(); // Se debe hacer en for.
                if (!in_array($filePath, $includedFiles)) {
                    // Se incluye el archivo.
                    require $filePath;
                }

                // Se arma el nombre completo de la clase (FQCN).
                $classFqcn = sprintf(
                    '%s\%s',
                    $searchPath['namespace'],
                    $className,
                );

                // Se verifica que la clase eventualmente importada exista.
                // Esto es lo que realmente corrobora que lo que había en el
                // archivo PHP sea una clase y que sea la que estamos buscando.
                if (!class_exists($classFqcn)) {
                    continue;
                }

                // Si la clase existe, se guarda en el arreglo de clases que se
                // han encontrado.
                $classes[$className] = [
                    'name' => $className,
                    'namespace' => $searchPath['namespace'],
                    'fqcn' => $classFqcn,
                    'filepath' => $filePath,
                ];
            }
        }

        // Entregar todas las clases encontradas.
        return $classes;
    }

}
