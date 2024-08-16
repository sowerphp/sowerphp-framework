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
 * Servicio para administrar las capas de la aplicación.
 * Cada capa tiene su propia ubicación y namespace base, que puede o no tener
 * otros namespaces en subniveles. Si existen namespaces inferiores serán
 * módulos de la capa (no se usa PSR-4).
 */
class Service_Layers implements Interface_Service
{

    /**
     * Arreglo con los archivos dentro de una ruta que representan puntos desde
     * donde se puede lanzar la aplicación.
     *
     * Se utilizan para determinar el directorio del proyecto, aquel que
     * contiene al archivo que se encuentre en el backtrace.
     *
     * @var array
     */
    protected $startFiles = [
        '/public/index.php',
    ];

    /**
     * Arreglo con los archivos dentro de una ruta que contienen código PHP que
     * no son clases y que deben ser importados.
     *
     * Por ejemplo archivos con funciones PHP (helpers.php).
     *
     * @var array
     */
    protected $importFiles = [
        '/App/helpers.php',
    ];

    /**
     * Archivo de configuración de las capas de la aplicación.
     *
     * @var string
     */
    protected $configLayersFile = '/config/layers.php';

    /**
     * Arreglo asociativo con los directorios (pueden tener una o más capas).
     *
     * Estos son autodetectados y son fijos:
     *
     *   - framework: directorio dónde se encuentra el framework.
     *   - proyect: directorio dónde se encuentra el proyecto construído.
     *   - storage: directorio para almacenamiento de archivos.
     *   - static: directorio para archivos estáticos accesibles por HTTP.
     *   - tmp: directorio para archivos temporales.
     *   - resource: directorio de recursos (como traducciones).
     *
     * @var array
     */
    protected $directories;

    /**
     * Arreglo asociativo con las capas de la aplicación.
     *
     * Estas capas normalmente estarán en los directorios previamente
     * definidos, sin embargo no es una obligación y una capa podría estar en
     * cualquier directorio.
     * Cada capa define un "grupo de funcionalidades". En estas capas es donde
     * estará todo el código y archivos de la aplicación separado en diferentes
     * rutas según las funcionalidades u orden que se de a la aplicación.
     *
     * @var array
     */
    protected $layers;

    /**
     * Arreglo con las rutas de las capas ordenadas de mayor a menor
     * preferencia.
     *
     * @var array
     */
    protected $paths;

    /**
     * Arreglo con las rutas en las capas ordenadas de menor a mayor
     * preferencia.
     *
     * @var array
     */
    protected $pathsReverse;

    /**
     * Caché para las rutas de archivos que se han determinado.
     */
    protected $filepaths = [];

    /**
     * Registrar el servicio de capas.
     */
    public function register(): void
    {
    }

    /**
     * Inicializar el servicio de capas.
     *
     * @return void
     */
    public function boot(): void
    {
        // Determinar los directorios principales de la aplicación.
        $this->getDirectories();

        // Determinar las capas de la aplicación.
        $this->getLayers();

        // Determinar los paths existentes a partir de las capas.
        $this->getPaths();
        $this->getPathsReverse();

        // Cargar (importar) archivos PHP que no son clases.
        $this->loadFilesReverse($this->importFiles);
    }

    /**
     * Finaliza el servicio de capas.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Obtener todos los directorios de la aplicación.
     *
     * @return array Arreglo asociativo con los directorios.
     */
    public function getDirectories(): array
    {
        // Si no se han cargado los directorios se cargan.
        if (empty($this->directories)) {
            $this->getFrameworkPath();
            $this->getProjectPath();
            $this->getStoragePath();
            $this->getResourcePath();
            define('DIR_STATIC', $this->getStaticPath());
            define('DIR_TMP', $this->getTmpPath());
        }
        // Entregar el arreglo asociativo de directorios.
        return $this->directories;
    }

    /**
     * Obtiene el directorio del framework.
     *
     * @param string $path Ruta que se desea obtener dentro del directorio.
     * @return string La ruta del directorio del framework.
     */
    public function getFrameworkPath(?string $path = null): string
    {
        // Si el directorio ya está asignado se entrega.
        if (!isset($this->directories['framework'])) {
            $this->directories['framework'] = dirname(dirname(dirname(__DIR__)));
        }
        // Retornar el directorio determinado o la ruta dentro del directorio.
        return $this->directories['framework'] . $this->normalizePath($path);
    }

    /**
     * Obtiene el directorio del proyecto rastreando la pila de llamadas y
     * coincidiendo con rutas de archivo específicas.
     *
     * Este método recorre el rastreo de depuración para encontrar las rutas de
     * archivo que coincidan. con los patrones proporcionados. Es útil para
     * determinar el directorio base del proyecto al identificar puntos de
     * entrada específicos como 'public/index.php' o 'console/shell.php'.
     *
     * @param string $path Ruta que se desea obtener dentro del directorio.
     * @return string La ruta del directorio del proyecto.
     */
    public function getProjectPath(?string $path = null): string
    {
        // Si el directorio no está asignado se asigna.
        if (!isset($this->directories['project'])) {
            // Construir el patrón de expresión regular a partir del arreglo de
            // archivos.
            $pattern = '#(' . implode('|', array_map(function($file) {
                return preg_quote($file, '#');
            }, $this->startFiles)) . ')$#';
            // Obtener el rastreo de depuración
            $backtrace = debug_backtrace();
            // Iterar a través del rastreo para encontrar una ruta de archivo
            // coincidente.
            foreach ($backtrace as $caller) {
                if (!isset($caller['file'])) {
                    continue;
                }
                if (preg_match($pattern, $caller['file'])) {
                    $this->directories['project'] = dirname(dirname(
                        $caller['file']
                    ));
                    break;
                }
            }
        }
        // Retornar el directorio determinado o la ruta dentro del directorio.
        return $this->directories['project'] . $this->normalizePath($path);
    }

    /**
     * Obtiene el directorio de almacenamiento de la aplicación.
     *
     * Por defecto es el directorio /storage dentro del proyecto.
     *
     * @param string $path Ruta que se desea obtener dentro del directorio.
     * @return string La ruta del directorio de almacenamiento.
     */
    public function getStoragePath(?string $path = null): string
    {
        // Si el directorio no está asignado se asigna.
        if (!isset($this->directories['storage'])) {
            $this->directories['storage'] = $this->getProjectPath(
                DIRECTORY_SEPARATOR . 'storage'
            );
        }
        // Retornar el directorio determinado o la ruta dentro del directorio.
        return $this->directories['storage'] . $this->normalizePath($path);
    }

    /**
     * Obtiene el directorio de recursos de la aplicación.
     *
     * Por defecto es el directorio /resources dentro del proyecto.
     *
     * @param string $path Ruta que se desea obtener dentro del directorio.
     * @return string La ruta del directorio de recursos.
     */
    public function getResourcePath(?string $path = null): string
    {
        // Si el directorio no está asignado se asigna.
        if (!isset($this->directories['resource'])) {
            $this->directories['resource'] = $this->getProjectPath(
                DIRECTORY_SEPARATOR . 'resources'
            );
        }
        // Retornar el directorio determinado o la ruta dentro del directorio.
        return $this->directories['resource'] . $this->normalizePath($path);
    }

    /**
     * Obtiene el directorio de archivos estáticos de la aplicación.
     *
     * Por defecto es el directorio /storage/static dentro del proyecto.
     *
     * @param string $path Ruta que se desea obtener dentro del directorio.
     * @return string La ruta del directorio de archivos estáticos.
     */
    public function getStaticPath(?string $path = null): string
    {
        // Si el directorio ya está asignado se entrega.
        if (!isset($this->directories['static'])) {
            $this->directories['static'] = $this->getStoragePath(
                DIRECTORY_SEPARATOR . 'static'
            );
        }
        // Retornar el directorio determinado o la ruta dentro del directorio.
        return $this->directories['static'] . $this->normalizePath($path);
    }

    /**
     * Obtiene el directorio de archivos temporales del proyecto.
     *
     * Por defecto es el directorio /storage/tmp dentro del proyecto si existe
     * y tiene permisos de lectura, o bien el directorio de archivos temporales
     * del sistema operativo.
     *
     * @param string $path Ruta que se desea obtener dentro del directorio.
     * @return string La ruta del directorio de archivos temporales.
     */
    public function getTmpPath(?string $path = null): string
    {
        // Si el directorio ya está asignado se entrega.
        if (!isset($this->directories['tmp'])) {
            $this->directories['tmp'] = $this->getStoragePath(
                DIRECTORY_SEPARATOR . 'tmp'
            );
            if (!is_writable($this->directories['tmp'])) {
                $this->directories['tmp'] = sys_get_temp_dir();
            }
        }
        // Retornar el directorio determinado o la ruta dentro del directorio.
        return $this->directories['tmp'] . $this->normalizePath($path);
    }

    /**
     * Método que normaliza un path. Esto lo hace incorporando el "slash"
     * inicial. Con esto el path quedará desde la "raíz". Y esa "raíz" podrá
     * ser la raíz real del sistema de archivos o la raíz de uno de los
     * directorios de las capas.
     *
     * @param string|null $path Path que se está buscando normalizar.
     * @return string
     */
    protected function normalizePath(?string $path = null): string
    {
        if ($path) {
            if ($path[0] != DIRECTORY_SEPARATOR) {
                $path = DIRECTORY_SEPARATOR . $path;
            }
            return $path;
        }
        return '';
    }

    /**
     * Obtener todas las capas de la aplicación.
     *
     * @return array Arreglo asociativo con las capas de la aplicación.
     */
    public function getLayers(): array
    {
        // Si las capas no están definidas se definen.
        if (!isset($this->layers)) {
            $layers = require $this->getProjectPath($this->configLayersFile);
            $this->layers = [];
            foreach ($layers as $layer) {
                $this->layers[$layer['namespace']] = [
                    'path' => $this->deobfuscatePath($layer['directory']),
                ];
            }
        }
        // Entregar listado de capas.
        return $this->layers;
    }

    /**
     * Muestra la ruta completa de un path, sin ocultar la ruta.
     *
     * @param string $path
     * @return string
     */
    public function deobfuscatePath(string $path): string
    {
        return str_replace(
            ['framework:', 'project:'],
            [$this->getFrameworkPath(), $this->getProjectPath()],
            $path
        );
    }

    /**
     * Oculta parte de la ruta de un path, ocultando la ruta real.
     *
     * @param string $path
     * @return string
     */
    public function obfuscatePath(string $path): string
    {
        return str_replace(
            [$this->getFrameworkPath(), $this->getProjectPath(),],
            ['framework:', 'project:',],
            $path
        );
    }

    /**
     * Entrega la información de una capa específica de la aplicación.
     *
     * @param string $layer Capa que se requiere su información.
     * @return array Información de la capa si fue encontrada o null.
     */
    public function getLayer(string $layer): ?array
    {
        $layer = str_replace(DIRECTORY_SEPARATOR, '\\', $layer);
        return $this->layers[$layer] ?? null;
    }

    /**
     * Obtener las rutas registradas según las capas definidas.
     *
     * @return array Las rutas registradas.
     */
    public function getPaths(): array
    {
        // Si las rutas no están definidas se definen.
        if (!isset($this->paths)) {
            $this->paths = array_map(function($layer) {
                return $layer['path'];
            }, $this->getLayers());
        }
        // Entregar el listado de rutas de la aplicación
        return $this->paths;
    }

    /**
     * Obtener las rutas registradas según las capas definidas en orden
     * inverso al que están definidas (de menor a mayor prioridad).
     *
     * @return array Las rutas registradas en orden inverso.
     */
    public function getPathsReverse(): array
    {
        // Si las rutas inversas no están definidias de se definen.
        if (!isset($this->pathsReverse)) {
            $this->pathsReverse = array_reverse($this->getPaths());
        }
        // Entregar el listado de rutas invertidas de la aplicación.
        return $this->pathsReverse;
    }

    /**
     * Obtener la ruta del archivo que tiene la máxima prioridad.
     *
     * O sea, se obtiene la ruta completa del primer archivo que se encuentra
     * buscando en todas las rutas de la aplicación desde mayor a menor
     * prioridad.
     *
     * Este método permite buscar cualquier archivo dentro de las capas de la
     * aplicación.
     *
     * @param string $filename
     * @return string|null Ruta absoluta al archivo si fue encontrado o null.
     */
    public function getFilePath(string $filename): ?string
    {
        $filename = $this->normalizePath($filename);
        // Si la ruta absoluta al archivo no está determinada se busca.
        if (!isset($this->filepaths[$filename])) {
            $paths = $this->getPaths();
            $this->filepaths[$filename] = false;
            foreach ($paths as $path) {
                $filepath = $path . $filename;
                if (is_readable($filepath)) {
                    $this->filepaths[$filename] = $filepath;
                    break;
                }
            }
        }
        return $this->filepaths[$filename] !== false
            ? $this->filepaths[$filename]
            : null
        ;
    }

    /**
     * Método que carga archivos de cada capa, por defecto, en el orden que las
     * capas fueron definidas.
     */
    public function loadFiles(array $files, bool $reverse = false): void
    {
        $paths = $reverse ? $this->getPathsReverse() : $this->getPaths();
        foreach ($files as $file) {
            foreach ($paths as $path) {
                $filepath = $path . $file;
                if (is_readable($filepath)) {
                    include_once $filepath;
                }
            }
        }
    }

    /**
     * Método que carga archivos de cada capa en el orden reverso en que las
     * capas fueron definidas.
     */
    public function loadFilesReverse(array $files): void
    {
        $this->loadFiles($files, true);
    }

}
