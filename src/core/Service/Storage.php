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
 */
class Service_Storage implements Interface_Service
{

    /**
     * Listado de conexiones a dispositivos de almacenamiento ("discos")
     * que puede utilizar este servicio.
     *
     * @var array
     */
    private $disks = [];

    // Dependencias de otros servicios.
    private $layersService;

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
        $this->disks['local'] = new Filesystem(
            new LocalFilesystemAdapter($this->layersService->getStorageDir())
        );
        // Almacenamiento para archivos estáticos.
        // Estos archivos son accesibles mediante la URL.
        $this->disks['static'] = new Filesystem(
            new LocalFilesystemAdapter($this->layersService->getStaticDir())
        );
        // Almacenamiento para archivos temporales.
        $this->disks['tmp'] = new Filesystem(
            new LocalFilesystemAdapter($this->layersService->getTmpDir())
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
        if ($name === null) {
            $name = 'local';
        }
        if (isset($this->disks[$name])) {
            return $this->disks[$name];
        }
        throw new \Exception(sprintf(
            'El almacenamiento "%s" no está configurado para ser usado.',
            $name
        ));
    }

}
