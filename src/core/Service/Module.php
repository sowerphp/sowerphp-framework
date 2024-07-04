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

class Service_Module implements Interface_Service
{

    // Dependencias de otros servicios.
    protected $app;
    protected $layersService;
    protected $configService;

    // Listado de modulos cargados.
    protected $modules = [];

    public function __construct(App $app, Service_Layers $layersService)
    {
        $this->app = $app;
        $this->layersService = $layersService;
    }

    public function register(): void
    {
    }

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
     * Obtener las rutas donde se encuentra un módulo.
     *
     * @param string $module Nombre del módulo.
     * @return array|null Rutas donde el módulo existe.
     */
    public function getModulePaths(string $module): ?array
    {
        if(isset($this->modules[$module])) {
            return $this->modules[$module]['paths'];
        } else {
            return null;
        }
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

}
