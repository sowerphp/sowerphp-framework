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

/**
 * Servicio para autocarga de clases que no están cubiertas por composer.
 *
 * Se requiere este autocargador porque se define que una clase puede estar
 * en cualquiera de las capas, y eso no se puede lograr con PSR4.
 *
 * Este servicio realiza la búsqueda de la clase en las capas y crea un alias
 * hacia la clase encontrada. De esta forma se pueden cargar dinámicamente
 * clases que se encuentran en diferentes capas.
 *
 * Este servicio permite tener una autocarga que buscará una clase en
 * diferentes capas (en orden de prioridad) y en la primera capa que encuentre
 * la clase, la utilizará como la clase solicitada a través de la autocarga.
 * Lo anterior independientemente del namespace que tenga esa clase encontrada,
 * o sea, independientemente de la capa donde se haya encontrado. Esto entrega
 * ciertas ventajas y facilidades a la hora de programar (simplificaciones).
 *
 * El prefijo de la clase, o sea, la parte inicial del namespace, para que esta
 * autocarga funcione debe ser necesariamente "\sowerphp\autoload\".
 *
 * Si se quiere usar el servicio de autocarga con clases asignadas
 * dinámicamente con alias que no usen el prefijo antes descrito o que se
 * quiera definir de manera dura (hardcodeada) el alias, se puede hacer en el
 * archivo de configuración del servicio (config/autoload.php).
 */
class Service_Autoload implements Interface_Service
{
    /**
     * Servicio de capas.
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
     * Constructor del servicio de autocarga.
     *
     * @param Service_Layers $layersService
     * @param Service_Config $configService
     */
    public function __construct(
        Service_Layers $layersService,
        Service_Config $configService
    )
    {
        $this->layersService = $layersService;
        $this->configService = $configService;
    }

    /**
     * Registra el servicio de notificaciones.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio de notificaciones.
     *
     * @return void
     */
    public function boot(): void
    {
        // Asignar el autocargador de clases.
        spl_autoload_register([$this, 'autoloader']);
    }

    /**
     * Finaliza el servicio de notificaciones.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Método que obtiene el alias de la clase desde la configuración.
     *
     * @return string|null Alias de la clase si se encontró.
     */
    protected function getClassAlias(string $class): ?string
    {
        $key = 'autoload.alias.' . $class;

        return $this->configService->get($key);
    }

    /**
     * Método que obtiene el nombre la clase que se tratará de autocargar.
     *
     * Es necesario determinar el nombre de la clase que se autocargará porque
     * solo se deben buscar clases donde su prefijo (namespace sin módulo):
     *
     *   - Partan con el prefijo: \sowerphp\autoload\
     *   - No tengan prefijo. Esto es temporal mientras se migra al prefijo.
     *
     * @param string $class Clase que se quiere cargar.
     * @return string|null Clase que se buscará (sin el prefijo) o null si no
     * se debe tratar de cargar con este autoload y se debe delegar la carga a
     * composer.
     */
    protected function getClassName(string $class): ?string
    {
        $prefix = 'sowerphp\\autoload\\';
        if (strpos($class, $prefix) !== 0) {
            return null;
        }

        return str_replace($prefix, '', $class);
    }

    /**
     * Método que construye el nombre del archivo donde se buscará la clase.
     *
     * Se consideran la posibilidad de que la clase venga con uno o más
     * módulos; o, con uno o más subdirectorios dentro de la clase. Ejemplos:
     *
     *   - Controller
     *   - Controller_App
     *   - Dev\Controller_Config
     *   - Sistema\Usuarios\Model_Usuario
     *
     * @param string $class Nombre de la clase.
     * @return string Nombre del archivo que contiene la clase.
     */
    protected function getClassFilename(string $class): string
    {
        $filename = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
        $has_module = strpos($class, '\\') !== false;

        // Si no tiene módulo solo se antepone el separador de directorio para
        // indicar que se buscará desde la "raíz" de la capa.
        if (!$has_module) {
            return DIRECTORY_SEPARATOR . $filename;
        }

        // Si tiene módulo la clase se arma el nombre completo considerando la
        // ruta hacia el módulo y, eventuales, submódulos.
        $module_dir_for_path =
            DIRECTORY_SEPARATOR . 'Module' . DIRECTORY_SEPARATOR
        ;
        $filename_parts = explode('\\', $filename);
        $filename_parts_count = count($filename_parts);

        return $module_dir_for_path
            . implode(
                $module_dir_for_path,
                array_slice(
                    $filename_parts,
                    0,
                    $filename_parts_count - 1
                )
            )
            . DIRECTORY_SEPARATOR
            . $filename_parts[$filename_parts_count-1]
        ;
    }

    /**
     * Método que realiza la búsqueda de la clase en las diferentes capas de la
     * aplicación. Si encuentra un archivo que corresponda a la clase buscada
     * asumirá que dentro estará la clase por lo que retornará el nombre
     * completo de la clase encontrada, su FQCN con la clase real.
     *
     * @param string $class Clase buscada a partir de una clase relativa.
     * @return string|null FQCN de la clase buscada si fue encontrada.
     */
    protected function loadClass(string $class): ?string
    {
        $filename = $this->getClassFilename($class);
        foreach ($this->layersService->getLayers() as $namespace => $layer) {
            $real_class_file = $layer['path'] . $filename;
            if (is_readable($real_class_file)) {
                require_once $real_class_file;
                return '\\' . $namespace . '\\' . $class;
            }
        }

        return null;
    }

    /**
     * Método que realiza la autocarga de clases.
     *
     * @param string $class Clase que se desea cargar.
     * @return bool `true` si se encontró y cargó la clase.
     */
    public function autoloader(string $class): bool
    {
        // Buscar si la clase tiene un alias por configuración. Si lo tiene se
        // busca si la clase del alias existe y se define el alias en el
        // entorno de ejecución.
        $alias_class = $this->getClassAlias($class);
        if ($alias_class) {
            if (!class_exists($alias_class)) {
                throw new \Exception(__(
                    'Alias %s de la clase %s está definido pero no existe.',
                    $alias_class,
                    $class
                ));
            }
            return class_alias($class, $alias_class);
        }

        // Buscar nombre de la clase, si no se encuentra solo se verifica si
        // existe. Si no está definida, al buscar si existe, se usará la
        // autocarga de composer para determinar si la clase existe.
        $autoload_class = $this->getClassName($class);
        if (!$autoload_class) {
            return class_exists($class);
        }

        // Buscar la clase con carga automática.
        $real_class = $this->loadClass($autoload_class);

        // Cargar la clase encontrada y crear alias.
        if ($real_class && class_exists($real_class)) {
            return class_alias($real_class, $class);
        }

        // Si no se encontró la clase con la carga automática retornar false.
        return false;
    }
}
