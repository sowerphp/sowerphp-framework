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

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

/**
 * Servicio que permite utilizar almacenamientos con League\Flysystem.
 *
 * Métodos disponibles en los discos de Filesystem obtenidos con disk():
 *
 * Almacenamiento de archivos:
 * - put($path, $contents, $options = [])
 * - putFile($path, $file, $options = [])
 * - putFileAs($path, $file, $name, $options = [])
 * - writeStream($path, $resource, $options = [])
 *
 * Recuperación de archivos:
 * - get($path)
 * - readStream($path)
 * - exists($path)
 * - missing($path)
 * - getVisibility($path)
 * - setVisibility($path, $visibility)
 *
 * Información de archivos:
 * - size($path)
 * - lastModified($path)
 * - mimeType($path)
 *
 * Directorio y navegación de archivos:
 * - directories($directory = null, $recursive = false)
 * - allDirectories($directory = null)
 * - files($directory = null, $recursive = false)
 * - allFiles($directory = null)
 *
 * Manipulación de archivos:
 * - copy($from, $to)
 * - move($from, $to)
 * - delete($paths)
 * - deleteDirectory($directory)
 * - makeDirectory($path)
 * - cleanDirectory($directory)
 *
 * Enlaces simbólicos:
 * - url($path)
 * - temporaryUrl($path, $expiration, $options = [])
 *
 * Descarga y respuesta:
 * - download($path, $name = null, $headers = [])
 * - response($path, $name = null, $headers = [], $disposition = 'inline')
 *
 * Además se tienen todos los métodos disponibles en la clase CustomFilesystem.
 */
class Service_Storage implements Interface_Service
{

    /**
     * Almacenamiento por defecto que usa el servicio de almacenamiento.
     *
     * @var string
     */
    protected $defaultStorage = 'local';

    /**
     * Listado de rutas de los dispositivos de almacenamient o ("discos") que
     * se utilizan para acceder a los datos.
     *
     * Si son almacenamientos locales serán las rutas dentro del sistema de
     * archivos.
     *
     * @var array
     */
    protected $paths = [];

    /**
     * Listado de conexiones a dispositivos de almacenamiento ("discos")
     * que puede utilizar este servicio.
     *
     * @var array
     */
    protected $disks = [];

    // Dependencias de otros servicios.
    protected $layersService;

    /**
     * Constructor del servicio con sus dependencias.
     */
    public function __construct(Service_Layers $layersService)
    {
        $this->layersService = $layersService;
    }

    public function register()
    {
    }

    /**
     * Inicializar y configurar los almacenamientos que se usarán.
     */
    public function boot()
    {
        // Almacenamiento para todos los archivos (global).
        // Este almacenamiento debería contener a static y tmp.
        $this->paths['local'] = $this->layersService->getStoragePath();
        $this->disks['local'] = new CustomFilesystem(
            new LocalFilesystemAdapter($this->paths['local'])
        );
        // Almacenamiento para archivos estáticos.
        // Estos archivos son accesibles mediante la URL.
        $this->paths['static'] = $this->layersService->getStaticPath();
        $this->disks['static'] = new CustomFilesystem(
            new LocalFilesystemAdapter($this->paths['static'])
        );
        // Almacenamiento para archivos temporales.
        $this->paths['tmp'] = $this->layersService->getTmpPath();
        $this->disks['tmp'] = new CustomFilesystem(
            new LocalFilesystemAdapter($this->paths['tmp'])
        );
    }

    /**
     * Retorna una instancia del disco solicitado.
     *
     * @param string $name Nombre del almacenamiento solicitado.
     * @return Filesystem Almacenamiento solicitado para ser usado.
     */
    public function disk(?string $name = null): Filesystem
    {
        $name = $name ?? $this->defaultStorage;
        if (isset($this->disks[$name])) {
            return $this->disks[$name];
        }
        throw new \Exception(sprintf(
            'El almacenamiento "%s" no está configurado para ser usado.',
            $name
        ));
    }

}

/**
 * Clase CustomFilesystem
 *
 * Extiende la funcionalidad del sistema de archivos Flysystem para incluir
 * métodos personalizados específicos de la aplicación.
 *
 * Esta clase permite el uso de todas las operaciones estándar de Flysystem
 * además de las personalizaciones adicionales definidas en la misma.
 */
class CustomFilesystem extends Filesystem
{

    /**
     * Determina si una ruta es probablemente un directorio.
     *
     * @param string $path La ruta a verificar.
     * @return bool True si la ruta es probablemente un directorio, de lo
     * contrario False.
     */
    public function isLikelyDirectory(string $path): bool
    {
        // Verificar si la ruta ya existe y su tipo.
        // 100% de certeza basada en que el directorio realmente existe.
        if ($this->directoryExists($path)) {
            return true;
        }

        // Verificar si la ruta termina con un slash.
        // 100% de certeza basada en que todo $path que termina en "/" será un
        // directorio.
        if (substr($path, -1) === DIRECTORY_SEPARATOR) {
            return true;
        }

        // Verificar si el nombre base tiene una extensión y es archivo.
        // 75% de certeza basada en que la probabilidad de que el directorio
        // tenga ".", en un lugar que no sea el inicio de su nombre, es baja.
        $basename = basename($path);
        if (strpos($basename, '.')) {
            return false;
        }

        // Se asume directorio si no se logró determinar antes.
        return true;
    }

    /**
     * Verifica si una ruta es un directorio.
     *
     * @param string $path La ruta a verificar.
     * @return bool True si la ruta es un directorio, de lo contrario False.
     */
    public function directoryExists(string $path): bool
    {
        return
            $this->fileExists($path)
            && $this->mimeType($path) === 'directory'
        ;
    }

}
