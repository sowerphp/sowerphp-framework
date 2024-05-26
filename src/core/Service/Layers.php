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
     * Se utilizan para determinar el directorio del proyecto, aquel que
     * contiene al archivo que se encuentre en el backtrace.
     *
     * @var array
     */
    private array $start_files = [
        'public/index.php',
        'console/shell.php',
    ];

    /**
     * Arreglo asociativo con los directorios (pueden tener una o más capas).
     * Estos son autodetectados y son fijos:
     *   - framework: directorio dónde se encuentra el framework.
     *   - proyect: directorio dónde se encuentra el proyecto construído.
     *   - storage: directorio para almacenamiento de archivos.
     *   - static: directorio para archivos estáticos accesibles por HTTP.
     *   - tmp: directorio para archivos temporales.
     *
     * @var array
     */
    private array $directories;

    /**
     * Arreglo asociativo con las capas de la aplicación.
     * Estas capas normalmente estarán en los directorios previamente
     * definidos, sin embargo no es una obligación y una capa podría estar en
     * cualquier directorio.
     * Cada capa define un "grupo de funcionalidades". En estas capas es donde
     * estará todo el código y archivos de la aplicación separado en diferentes
     * rutas según las funcionalidades u orden que se de a la aplicación.
     *
     * @var array
     */
    private array $layers;

    /**
     * Arreglo con las rutas de las capas ordenadas de mayor a menor
     * preferencia.
     *
     * @var array
     */
    private $paths;

    /**
     * Arreglo con las rutas en las capas ordenadas de menor a mayor
     * preferencia.
     *
     * @var array
     */
    private $pathsReverse;

    /**
     * Caché para las rutas de archivos que se han determinado.
     */
    private $filepaths = [];

    /**
     * Registrar el autocargador mágico de las clases.
     */
    public function register()
    {
        spl_autoload_register([$this, 'loadClass']);
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
        $this->loadFiles([
            '/App/helpers.php',
        ]);
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
            $this->getFrameworkDir();
            $this->getProjectDir();
            $this->getStorageDir();
            define('DIR_STATIC', $this->getStaticDir());
            define('DIR_TMP', $this->getTmpDir());
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
    public function getFrameworkDir(?string $path = null): string
    {
        // Si el directorio ya está asignado se entrega.
        if (!isset($this->directories['framework'])) {
            $this->directories['framework'] = dirname(dirname(dirname(__DIR__)));
        }
        // Retornar el directorio determinado o la ruta dentro del directorio.
        return $this->directories['framework'] . ($path ?? '');
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
    public function getProjectDir(?string $path = null): string
    {
        // Si el directorio no está asignado se asigna.
        if (!isset($this->directories['project'])) {
            // Construir el patrón de expresión regular a partir del arreglo de
            // archivos.
            $pattern = '#(' . implode('|', array_map(function($file) {
                return preg_quote($file, '#');
            }, $this->start_files)) . ')$#';
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
        return $this->directories['project'] . ($path ?? '');
    }

    /**
     * Obtiene el directorio de almacenamiento de la aplicación.
     * Por defecto es el directorio /storage dentro del proyecto.
     *
     * @param string $path Ruta que se desea obtener dentro del directorio.
     * @return string La ruta del directorio de almacenamiento.
     */
    public function getStorageDir(?string $path = null): string
    {
        // Si el directorio ya está asignado se entrega.
        if (!isset($this->directories['storage'])) {
            $this->directories['storage'] = $this->getProjectDir('/storage');
        }
        // Retornar el directorio determinado o la ruta dentro del directorio.
        return $this->directories['storage'] . ($path ?? '');
    }

    /**
     * Obtiene el directorio de archivos estáticos de la aplicación.
     * Por defecto es el directorio /storage/static dentro del proyecto.
     *
     * @param string $path Ruta que se desea obtener dentro del directorio.
     * @return string La ruta del directorio de archivos estáticos.
     */
    public function getStaticDir(?string $path = null): string
    {
        // Si el directorio ya está asignado se entrega.
        if (!isset($this->directories['static'])) {
            $this->directories['static'] = $this->getStorageDir('/static');
        }
        // Retornar el directorio determinado o la ruta dentro del directorio.
        return $this->directories['static'] . ($path ?? '');
    }

    /**
     * Obtiene el directorio de archivos temporales del proyecto.
     * Por defecto es el directorio /storage/tmp dentro del proyecto si existe
     * y tiene permisos de lectura, o bien el directorio de archivos temporales
     * del sistema operativo.
     *
     * @param string $path Ruta que se desea obtener dentro del directorio.
     * @return string La ruta del directorio de archivos temporales.
     */
    public function getTmpDir(?string $path = null): string
    {
        // Si el directorio ya está asignado se entrega.
        if (!isset($this->directories['tmp'])) {
            $this->directories['tmp'] = $this->getStorageDir('/tmp');
            if (!is_writable($this->directories['tmp'])) {
                $this->directories['tmp'] = sys_get_temp_dir();
            }
        }
        // Retornar el directorio determinado o la ruta dentro del directorio.
        return $this->directories['tmp'] . ($path ?? '');
    }

    /**
     * Obtener todas las capas de la aplicación.
     *
     * @return array Arreglo asociativo con las capas de la aplicación.
     */
    private function getLayers(): array
    {
        // Si las capas no están definidas se definen.
        if (!isset($this->layers)) {
            $layers = require $this->getProjectDir('/config/layers.php');
            $this->layers = [];
            foreach ($layers as $layer) {
                $this->layers[$layer['namespace']] = [
                    'path' => str_replace(
                        ['framework:', 'project:'],
                        [$this->getFrameworkDir(), $this->getProjectDir()],
                        $layer['directory']
                    )
                ];
            }
        }
        // Entregar listado de capas.
        return $this->layers;
    }

    /**
     * Entrega la información de una capa específica de la aplicación.
     *
     * @param string $layer Capa que se requiere su información.
     * @return array Información de la capa si fue encontrada o null.
     */
    public function getLayer(string $layer): ?array
    {
        $layer = str_replace('/', '\\', $layer);
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
     * O sea, se obtiene la ruta completa del primer archivo que se encuentra
     * buscando en todas las rutas de la aplicación desde mayor a menor
     * prioridad.
     * Este método permite buscar cualquier archivo dentro de las capas de la
     * aplicación.
     *
     * @param string $filename
     * @return string|null Ruta absoluta al archivo si fue encontrado o null.
     */
    public function getFilePath(string $filename): ?string
    {
        $filename = ($filename[0] != '/' ? '/' : '') . $filename;
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
     * Método que carga los archivos del directorio App de cada capa.
     */
    public function loadFiles(array $files): void
    {
        // Cargar los paths en orden reverso para poder sobrescribir
        // con los archivos que se cargarán.
        $paths = $this->getPathsReverse();

        // Incluir los archivos que existen en cada capa.
        foreach ($files as $file) {
            foreach ($paths as $path) {
                $filepath = $path . $file;
                if (is_readable($filepath)) {
                    include $filepath;
                }
            }
        }
    }

    /**
     * Método para autocarga de clases que no están cubiertas por composer.
     * Se requiere este autocargador porque el define que una clase puede estar
     * en cualquiera de las capas, y eso no se puede lograr con PSR4. Ya que
     * este necesariamente hace la búsqueda con el namespace. Acá el objetivo
     * es poder tener una autocarga que buscará una clase en diferentes capas
     * (en orden de prioridad) y en la primera capa que encuentre la clase, la
     * utilizará como la clase solicitada a través de la autocarga. Lo anterior
     * independientemente del namespace que tenga esa clase encontrada, o sea,
     * independientemente de la capa donde se haya encontrado. Esto entrega
     * ciertas ventajas y facilidades a la hora de programar (simplificaciones).
     * Sólo se deben buscar clases donde su namespace (prefijo) sea:
     *   - No tengan prefijo. Esto es temporal mientras se migra al prefijo.
     *   - Partan con el prefijo: \sowerphp\magicload\
     *
     * @param string $class Clase que se desea cargar.
     * @return bool =true si se encontró y cargó la clase.
     */
    public function loadClass(string $class): bool
    {
        // Clases que está en un namespace que no sea el prefijo no se
        // procesarán, pues su carga se deberá hacer con composer.
        $prefix = 'sowerphp\\magicload\\';
        $prefix_len = 19; // strlen($prefix) -> 19
        $class_has_prefix = strpos($class, '\\') !== false;
        if ($class_has_prefix && substr($class, 0, $prefix_len) != $prefix) {
            return class_exists($class);
        }
        // Si no se han cargado las capas (caso raro) retornar false, pues no
        // se puede buscar aun con este método la clase.
        // Sería un caso de borde cuando se pida por error una clase antes que
        // se haya completado la inicialización del servicio de capas.
        if (empty($this->layers)) {
            return false;
        }
        // Quitar el prefijo de carga automáfica de la clase
        $remove_prefix_count = 1;
        $magic_class = str_replace($prefix, '', $class, $remove_prefix_count);
        // Armar el nombre de la clase buscada. Se considera la posibilidad que
        // la clase venga con uno o más módulos, ejemplos:
        //   - Controller
        //   - Controller_App
        //   - Dev\Controller_Config
        //   - Sistema\Usuarios\Model_Usuario
        $magic_class_has_module = strpos($magic_class, '\\') !== false;
        $magic_class_file = str_replace('_', '/', $magic_class) . '.php';
        if ($magic_class_has_module) {
            $magic_class_file_parts = explode('\\', $magic_class_file);
            $magic_class_file_parts_count = count($magic_class_file_parts);
            $magic_class_file = '/Module/'
                . implode(
                    '/Module/',
                    array_slice(
                        $magic_class_file_parts,
                        0,
                        $magic_class_file_parts_count - 1
                    )
                ) . '/'
                . $magic_class_file_parts[$magic_class_file_parts_count-1]
            ;
        } else {
            $magic_class_file = '/' . $magic_class_file;
        }
        // Buscar la clase automágica.
        $real_class = null;
        foreach ($this->layers as $namespace => $layer) {
            $real_class_file = $layer['path'] . $magic_class_file;
            if (is_readable($real_class_file)) {
                $real_class = $namespace . '\\' . $magic_class;
                break;
            }
        }
        // Cargar la clase automágica.
        if ($real_class && class_exists($real_class)) {
            class_alias($real_class, $class);
            return true;
        }
        // Si no se encontró la clase con la carga automágica retornar false.
        return false;
    }

}
