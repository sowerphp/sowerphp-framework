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

class Service_Module implements Interface_Service
{

    use Trait_Service;

    // Dependencias de otros servicios.
    protected $layersService;
    protected $configService;

    // Listado de modulos cargados.
    private $modules = [];

    public function boot()
    {
        $this->layersService = $this->app->make('layers');
        $this->configService = $this->app->make('config');
    }

    /**
     * Registrar un módulo para su uso.
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
                    // Si se paso solo el nombre del módulo y no su
                    // configuración se convierte al formato requerido de
                    // 'Modulo' => []
                    if (!is_array($conf)) {
                        $name = $conf;
                        $conf = [];
                    }
                    // Registrar el módulo.
                    $this->registerModule($name, $conf);
                }
            }
        }
        // Procesar un módulo con su configuración.
        else {
            // Asignar opciones por defecto
            $config = array_merge([
                // Ruta real del módulo.
                'path' => [],
                // Si se debe cargar el módulo automáticamente.
                'autoLoad' => false,
                // El módulo no se encuentra cargado.
                'loaded' => false,
            ], $config);
            // Guardar configuración del modulo y cargar si es necesario.
            $this->modules[$module] = $config;
            if ($config['autoLoad']) {
                $this->loadModule($module);
            }
        }
    }

    /**
     * Desregistrar un módulo previamente registrado.
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
        if (!isset($this->modules[$module]['path'][0])) {
            $this->modules[$module]['path'] = [];
            $paths = $this->layersService->getPaths();
            foreach ($paths as &$path) {
                // Verificar que el directorio exista (el reemplazo es
                // para los submódulos)
                $modulePath = $path . '/Module/'
                    . str_replace('.', '/Module/', $module)
                ;
                if (is_dir($modulePath)) {
                    $this->modules[$module]['path'][] = $modulePath;
                }
            }
        }
        // Si se indicó se verifica que exista y se agrega como único
        // path para el modulo al arreglo.
        else {
            // Si el directorio existe se agrega
            if (is_dir($this->modules[$module]['path'])) {
                $this->modules[$module]['path'] = [
                    $this->modules[$module]['path'],
                ];
            }
            // Si no existe se elimina el path para generar error
            // posteriormente.
            else {
                $this->modules[$module]['path'] = [];
            }
        }
        // Si el módulo no fue encontrado se crea una excepción.
        if (!isset($this->modules[$module]['path'][0])) {
            throw new Exception_Module_Missing(['module' => $module]);
        }
        // Verificar "archivos cargables", si existen se cargan.
        $files = [
            '/App/helpers.php',
            '/App/routes.php',
        ];
        foreach ($files as &$file) {
            $filepath = $this->getFilePath($module, $file);
            if ($filepath) {
                include $filepath;
            }
        }
        // Cargar configuraciones del módulo.
        $filepath = $this->getFilePath($module, '/App/config.php');
        if ($filepath) {
            $this->configService->loadConfiguration($filepath);
        }
        $this->configService->reconfigure();
        // Indicar que el módulo fue cargado.
        $this->modules[$module]['loaded'] = true;
        return $this->modules[$module];
    }

    /**
     * Obtener la ubicación completa de un archivo en un módulo.
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
     * @param string $module Módulo que se desea verificar si está cargado.
     * @return bool
     */
    public function isModuleLoaded(string $module): bool
    {
        return isset($this->modules[$module]);
    }

    /**
     * Obtener todos los módulos cargados.
     * @return array Listado de módulos cargados.
     */
    public function getLoadedModules(): array
    {
        $modules = array_keys($this->modules);
        sort($modules);
        return $modules;
    }

    /**
     * Determinar si una URL corresponde a un módulo y cargarlo.
     * @param string $url Solicitud realizada (sin la base de la aplicación).
     * @return string Nombre del módulo si es que existe uno en la URL.
     */
    public function findModuleByUrl(string $url): string
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
        // Si $hasta es mayor a -1.
        if ($hasta >= 0) {
            // Armar nombre final del modulo (considerando hasta $hasta partes
            // del arreglo de partes).
            $module = [];
            for($i=0; $i<=$hasta; ++$i) {
                $module[] = Utility_Inflector::camelize($partes[$i]);
            }
            // cargar módulo
            $module = implode('.', $module);
            // retornar nombre del modulo
            return $module;
        } else {
            return '';
        }
    }

    /**
     * Obtener las rutas donde se encuentra un módulo.
     * @param string $module Nombre del módulo.
     * @return array|null Rutas donde el módulo existe.
     */
    public function getModulePaths(string $module): ?array
    {
        if(isset($this->modules[$module])) {
            return $this->modules[$module]['path'];
        } else {
            return null;
        }
    }

}
