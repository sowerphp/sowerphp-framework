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

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

/**
 * Servicio que permite utilizar almacenamientos con League\Flysystem.
 *
 * Métodos disponibles en los discos de Filesystem obtenidos con disk():
 *
 * Almacenamiento de archivos:
 * - write(string $location, string $contents, array $config = [])
 *   Escribe contenido en un archivo.
 * - writeStream(string $location, $contents, array $config = [])
 *   Escribe un flujo de datos en un archivo.
 * - setVisibility(string $path, string $visibility)
 *   Establece la visibilidad (pública o privada) de un archivo.
 *
 * Recuperación de archivos:
 * - read(string $location): string
 *   Lee el contenido de un archivo como una cadena.
 * - readStream(string $location)
 *   Lee el contenido de un archivo como un flujo de datos.
 * - fileExists(string $location): bool
 *   Verifica si un archivo existe en la ruta especificada.
 * - lastModified(string $path): int
 *   Obtiene la fecha de la última modificación del archivo.
 * - fileSize(string $path): int
 *   Obtiene el tamaño del archivo.
 * - mimeType(string $path): string
 *   Obtiene el tipo MIME del archivo.
 * - visibility(string $path): string
 *   Obtiene la visibilidad de un archivo.
 *
 * Directorio y navegación de archivos:
 * - listContents(string $location, bool $deep = self::LIST_SHALLOW): DirectoryListing
 *   Lista los contenidos de un directorio, opcionalmente de forma recursiva.
 *
 * Manipulación de archivos:
 * - move(string $source, string $destination, array $config = [])
 *   Mueve un archivo de una ubicación a otra.
 * - copy(string $source, string $destination, array $config = [])
 *   Copia un archivo a una nueva ubicación.
 * - delete(string $location): void
 *   Elimina un archivo.
 * - deleteDirectory(string $location): void
 *   Elimina un directorio y su contenido.
 * - createDirectory(string $location, array $config = []): void
 *   Crea un directorio en la ruta especificada.
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

    /**
     * Servicio de capas de la aplicación.
     *
     * @var Service_Layers
     */
    protected $layersService;

    /**
     * Constructor del servicio con sus dependencias.
     */
    public function __construct(Service_Layers $layersService)
    {
        $this->layersService = $layersService;
    }

    public function register(): void
    {
    }

    /**
     * Inicializar y configurar los almacenamientos que se usarán.
     */
    public function boot(): void
    {
        // Almacenamiento para todos los archivos (global).
        // Este almacenamiento debería contener a static y tmp.
        $this->paths['local'] = $this->layersService->getStoragePath();
        $this->disks['local'] = new CustomFilesystem(
            new LocalFilesystemAdapter($this->paths['local'])
        );

        // Almacenamiento para archivos estáticos públicos.
        // Estos archivos son accesibles mediante la URL (/static).
        $this->paths['static'] = $this->layersService->getStaticPath();
        $this->disks['static'] = new CustomFilesystem(
            new LocalFilesystemAdapter($this->paths['static'])
        );

        // Almacenamiento para archivos estáticos privados.
        // Estos archivos son accesibles mediante la URL (/private).
        $this->paths['private'] = $this->getFullPath('/private', 'local');
        $this->disks['private'] = new CustomFilesystem(
            new LocalFilesystemAdapter($this->paths['private'])
        );

        // Almacenamiento para archivos temporales.
        $this->paths['tmp'] = $this->layersService->getTmpPath();
        $this->disks['tmp'] = new CustomFilesystem(
            new LocalFilesystemAdapter($this->paths['tmp'])
        );
    }

    /**
     * Finaliza el servicio de almacenamiento.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Obtiene una instancia del disco solicitado.
     *
     * @param string $name Nombre del almacenamiento solicitado.
     * @return Filesystem Almacenamiento solicitado para ser usado.
     */
    public function disk(?string $name = null): Filesystem
    {
        $name = $name ?? $this->defaultStorage;
        if (!isset($this->disks[$name])) {
            throw new \Exception(sprintf(
                'El almacenamiento "%s" no está configurado para ser usado.',
                $name
            ));
        }

        return $this->disks[$name];
    }

    /**
     * Crea una instancia de un disco dentro de otro almacenamiento (subdisco).
     *
     * @param string $name Nombre del almacenamiento solicitado.
     * @return Filesystem Almacenamiento solicitado para ser usado.
     */
    public function subdisk(string $path, ?string $name = null): Filesystem
    {
        $name = $name ?? $this->defaultStorage;
        $key = $name . ':' . $path;
        if (!isset($this->disks[$key])) {
            $this->paths[$key] = $this->getFullPath($path, $name);
            $this->disks[$key] = new CustomFilesystem(
                new LocalFilesystemAdapter($this->paths[$key])
            );
        }

        return $this->disks[$key];
    }

    /**
     * Obtiene la ruta completa dentro de un almacenamiento.
     *
     * @param string $path Ruta que se desea obtener completa dentro del
     * almacenamiento.
     * @param string $name Nombre del almacenamiento solicitado.
     * @return string Ruta completa dentro del almacenamiento.
     */
    public function getFullPath(?string $path = null, ?string $name = null): string
    {
        $basePath = $this->path($name) ?? '';
        $path = $this->normalizePath($path ?? '');

        return $basePath . $path;
    }

    /**
     * Obtiene la ruta base del almacenamiento solicitado.
     *
     * @param string $name Nombre del almacenamiento solicitado.
     * @return string Ruta base del almacenamiento.
     */
    protected function path(?string $name = null): string
    {
        $name = $name ?? $this->defaultStorage;
        if (!isset($this->paths[$name])) {
            throw new \Exception(sprintf(
                'El almacenamiento "%s" no está configurado para ser usado.',
                $name
            ));
        }

        return $this->paths[$name] ?? null;
    }

    /**
     * Método que normaliza un path. Esto lo hace incorporando el "slash"
     * inicial. Con esto el path quedará desde la "raíz". Y esa "raíz" podrá
     * ser la raíz real del sistema de archivos o la raíz de uno de los
     * directorios de los discos de almacenamiento.
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
