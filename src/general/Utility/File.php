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

namespace sowerphp\general;

/**
 * Clase para manejar archivos y directorios
 *
 * Esta clase permite realizar diversas acciones sobre archivos y directorios
 * que se encuentren en el servidor donde se ejecuta la aplicación. Tales como:
 * listar contenido de directorios, subir archivos al servidor, extraer archivos
 * comprimidos, comprimir archivos y directorios, etc.
 */
class Utility_File
{

    // constantes para errores de la subida de archivos
    // errores propios de sowerphp no compatibles con los estándares de PHP
    const UPLOAD_ERROR = 1;
    const UPLOAD_ERROR_EXTENSION = 2;
    const UPLOAD_ERROR_MIMETYPE = 3;
    const UPLOAD_ERROR_SIZE = 4;

    /**
     * Recibe y procesa un archivo enviado por POST a la apliación
     * Si se especifican las dimensiones w y/o h se asume que es una imagen.
     * Si hay algún error se retornarán las constantes:
     *   - UPLOAD_ERROR: error del servidor o cliente al subir la imagen
     *   - UPLOAD_ERROR_EXTENSION: extensión no válida
     *   - UPLOAD_ERROR_MIMETYPE: mimetype no válido
     *   - UPLOAD_ERROR_SIZE: se excede tamaño
     * @param array|string $src Arreglo de $_FILES o bien el índice de $_FILES
     * @param array $filters Filtros que se deben validar al subir la imagen (extensions, mimetypes, size, width y height)
     * @return array|int Arreglo con los datos del archivo (índices: data, name, type y size) o código de error en caso de fallo
     */
    public static function upload($src, $filters = [])
    {
        // si es string se debe recuperar el valor desde variables $_FILES
        if (is_string($src)) {
            if (!isset($_FILES[$src])) {
                return self::UPLOAD_ERROR;
            }
            $src = &$_FILES[$src];
        }
        // verificar que exista el archivo y haya sido subido
        if ($src['error']) {
            return self::UPLOAD_ERROR;
        }
        // asignar filtros por defecto
        $filters = array_merge([
            'extensions' => [],
            'mimetypes' => [],
            'size' => 0,
            'width' => 0,
            'height' => 0,
        ], $filters);
        // verificar extensión
        if (!empty($filters['extensions'])) {
            if (is_string($filters['extensions'])) {
                $filters['extensions'] = [$filters['extensions']];
            }
            if (!in_array(self::extension($src['name']), $filters['extensions'])) {
                return self::UPLOAD_ERROR_EXTENSION;
            }
        }
        // verificar mimetype
        if (!empty($filters['mimetypes'][0])) {
            if (is_string($filters['mimetypes'])) {
                $filters['mimetypes'] = [$filters['mimetypes']];
            }
            if (!in_array($src['type'], $filters['mimetypes'])) {
                return self::UPLOAD_ERROR_MIMETYPE;
            }
        }
        // verificar tamaño
        if ($filters['size'] && $src['size']>($filters['size']*1024)) {
            return self::UPLOAD_ERROR_SIZE;
        }
        // si se ha definido ancho o algo máximo entonces es una imagen y se
        // debe verificar su tamaño, si lo excede la imagen se debe escalar
        if ($filters['width'] && $filters['height']) {
            list($file['width'], $file['height']) = getimagesize($src['tmp_name']);
            if ($file['width'] > $filters['width'] || $file['height'] > $filters['height']) {
                list($file['width'], $file['height']) = Utility_Image::resizeOnFile(
                    $src['tmp_name'], $filters['width'], $filters['height']
                );
            }
        }
        // crear arreglo con los datos del archivo y entregar
        $file['data'] = fread(fopen($src['tmp_name'], 'rb'), filesize($src['tmp_name']));
        $file['name'] = $src['name'];
        $file['type'] = $src['type'];
        $file['size'] = filesize($src['tmp_name']);
        return $file;
    }

    /**
     * Entrega una glosa descriptiva a partir del código de error de subida del
     * archivo mediante POST.
     *
     * @param int $code Código del error.
     * @return string Mensaje del error.
     */
    public static function uploadErrorCodeToMessage($code): string
    {
        switch ($code) {
            case \UPLOAD_ERR_INI_SIZE: {
                return __('El archivo excede el tamaño máximo permitido por el servidor (opción: upload_max_filesize).');
            }
            case \UPLOAD_ERR_FORM_SIZE: {
                return __('El archivo excede el tamaño máximo permitido por el formulario (opción: MAX_FILE_SIZE).');
            }
            case \UPLOAD_ERR_PARTIAL: {
                return __('El archivo pudo ser subido solo parcialmente.');
            }
            case \UPLOAD_ERR_NO_FILE: {
                return __('No se subió el archivo.');
            }
            case \UPLOAD_ERR_NO_TMP_DIR: {
                return __('No fue posible encontrar una carpeta temporal para subir el archivo en el servidor.');
            }
            case \UPLOAD_ERR_CANT_WRITE: {
                return __('Ocurrió un problema al tratar de guardar el archivo en el sistema de archivos del servidor.');
            }
            case \UPLOAD_ERR_EXTENSION: {
                return __('La subida del archivo fue detenida por una extensión de PHP en uso.');
            }
            default: {
                return __('Ocurrió un error desconocido al subir el archivo.');
            }
        }
    }

    /**
     * Recupera los archivos/directorios desde una carpeta.
     *
     * @todo Selección de solo algunos archivos de la carpeta
     * @param string $dir Nombre del directorio a examinar.
     * @return array Arreglo con los nombres de los archivos y/o directorios.
     */
    public static function browseDirectory(string $dir): array
    {
        $filesAux = scandir($dir);
        $files = [];
        foreach($filesAux as &$file) {
            if ($file[0] != '.') {
                $files[] = $file;
            }
        }
        return $files;
    }

    /**
     * Obtiene el tamaño de un fichero o directorio (método basado en función encontrada en Internet con enlace roto)
     * @param string $filepath Nombre del archivo/directorio a consultar tamaño
     * @param bool $mostrarUnidad =true mostrará la unidad (B, KiB, MiB, etc), =false entregará el resultado en bytes (B)
     * @return float|string Tamaño del archivo/directorio, ya sea con o sin unidad
     */
    public static function getSize($filepath, $mostrarUnidad = true)
    {
        // verificar que el archivo exista
        if (!file_exists($filepath)) {
            throw new \Exception(__('El archivo "%s" no existe.', $filepath));
        }
        // verificar que sea un fichero o directorio
        if (!is_file($filepath) && !is_dir($filepath)) {
            throw new \Exception(
                __('El archivo "%s" no es válido, debe ser un fichero o un directorio.', $filepath)
            );
        }
        // calcular tamaño
        $size = 0;
        if (is_dir($filepath)) {
            $dir = opendir($filepath);
            if (!$dir) {
                throw new \Exception(
                    __('No fue posible abrir el directorio "%s" para calcular su tamaño.', $filepath)
                );
            }
            while ($file = readdir($dir)) {
                // si el archivo es un directorio lo recorre recursivamente
                if (is_dir($filepath . DIRECTORY_SEPARATOR . $file)) {
                    // no recorre el dir padre ni el mismo recursivamente
                    if ($file != '.' && $file != '..') {
                        // llamada recursiva sin unidad (ahora solo sumando, unidad al finalizar recursividad)
                        $size += self::getSize($filepath . DIRECTORY_SEPARATOR . $file, false);
                    }
                }
                // si no es directorio se retorna el tamaño del archivo
                else {
                    $size += filesize($filepath . DIRECTORY_SEPARATOR . $file);
                }
            }
            closedir($dir);
        }
        // si no es directorio es fichero, se obtiene el tamaño del archivo
        else {
            $size += filesize($filepath);
        }
        clearstatcache();
        // si no se requiere la inidad, se retorna el tamaño en bruto
        if (!$mostrarUnidad) {
            return $size;
        }
        // dependiendo del tamaño del archivo se le coloca la unidad
        // la cual será en potencia de 2, por lo que, por ejemplo, es KiB y no KB
        if ($size <= 1024) {
            return $size.' B';
        }
        if ($size >= pow(1024, 4)) {
            return round($size/pow(1024, 4), 2).' TiB';
        }
        if ($size >= pow(1024, 3)) {
            return round($size/pow(1024, 3), 2).' GiB';
        }
        if ($size >= pow(1024, 2)) {
            return round($size/pow(1024, 2), 2).' MiB';
        }
        return round($size/1024, 2).' KiB';
    }

    /**
     * Abrir y leer un archivo (lo devuelve en el formato de arreglo)
     * @param string $file_name Ruta hacia el archivo
     * @return array con indices: name, type, size y data
     */
    public static function get($file_name)
    {
        // si el archivo no existe se retorna null
        if (!file_exists($file_name)) {
            return null;
        }
        // leer datos estandar de un archivo
        $file['name'] = basename($file_name);
        $file['type'] = mime_content_type($file_name);
        $file['size'] = filesize($file_name);
        $file['data'] = fread(fopen($file_name, 'rb'), $file['size']);
        // si es una imagen se setean otros atributos (tamaño)
        if (in_array($file['type'], array('image/jpeg', 'image/gif', 'image/png'))) {
            list($file['w'], $file['h']) = getimagesize($file_name);
            $file['ratio'] = $file['w'] / $file['h'];
        }
        // entregar archivo leído
        return $file;
    }

    /**
     * Borrar recursivamente un directorio
     * @param string $dir Directorio a borrar
     * @link http://en.kioskea.net/faq/793-warning-rmdir-directory-not-empty
     */
    public static function rmdir($dir)
    {
        // List the contents of the directory table
        $dir_content = scandir($dir);
        // Is it a directory?
        if ($dir_content !== false) {
            // For each directory entry
            foreach ($dir_content as &$entry) {
                // Unix symbolic shortcuts, we go
                if (!in_array($entry, array ('.','..'))) {
                    // We find the path from the beginning
                    $entry = $dir.DIRECTORY_SEPARATOR. $entry;
                    // This entry is not an issue: it clears
                    if (!is_dir($entry)) {
                        unlink ($entry);
                    }
                    // This entry is a folder, it again on this issue
                    else {
                        self::rmdir($entry);
                    }
                }
            }
        }
        // It has erased all entries in the folder, we can now erase
        rmdir($dir);
    }

    /**
     * Determinar la extensión de un archivo a partir de su nombre
     * @param string $file Ruta hacia el archivo (o nombre del mismo)
     * @return string Extensión si existe
     */
    public static function extension($file)
    {
        $dot = strrpos($file, '.');
        return $dot !== false ? strtolower(substr($file, $dot + 1)) : null;
    }

    /**
     * Método que cuenta la cantidad de líneas que un archivo posee
     * @param string $file Ruta hacia el fichero
     * @return int Cantidad de líneas del fichero
     * @link http://stackoverflow.com/a/20537130
     */
    public static function getLines($file)
    {
        $f = fopen($file, 'rb');
        $lines = 0;
        while (!feof($f)) {
            $lines += substr_count(fread($f, 8192), "\n");
        }
        fclose($f);
        return $lines;
    }

    /**
     * Método que entrega el mimetype de un archivo
     * @param string $file Ruta hacia el fichero
     * @return string Mimetype del fichero o =false si no se pudo determinar
     * @link http://stackoverflow.com/a/23287361
     */
    public static function mimetype($file)
    {
        if (!function_exists('finfo_open')) {
            return false;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype = finfo_file($finfo, $file);
        finfo_close($finfo);
        return $mimetype;
    }

    /**
     * Método que empaqueta y comprime archivos (uno o varios, o directorios).
     * Si se pide usar formato zip entonces se usará ZipArchive de PHP para
     * comprimir
     * @param string $filepath Directorio (o archivo) que se desea comprimir
     * @param array $options Arreglo con opciones para comprmir (format, download, delete)
     * @return bool =true si se pudo comprimir el archivo, =false si no fue posible
     * @todo Preparar datos si se pasa un arreglo
     */
    public static function compress($file, $options = [])
    {
        // definir opciones por defecto
        $options = array_merge([
            'format' => 'gz',
            'delete' => false,
            'download' => true,
            'commands' => [
                'gz' => 'gzip --keep :in',
                'tar.gz' => 'tar czf :in.tar.gz :in',
                'tgz' => 'tar czf :in.tgz :in',
                'tar' => 'tar cf :in.tar :in',
                'bz2' => 'bzip2 --keep :in',
                'tar.bz2' => 'tar cjf :in.tar.bz2 :in',
                'zip' => 'zip -r :in.zip :in',
            ],
        ], $options);
        // si la ruta del archivo es una arreglo los archivos se deben preparar
        // antes de ser empaquetados y comprimidos
        if (is_array($file)) {
            // TODO
        }
        // si el archivo no se puede leer se entrega =false
        if (!is_readable($file)) {
            return false;
        }
        // si es formato gz y es directorio se cambia a tgz
        if (is_dir($file)) {
            if ($options['format'] == 'gz') {
                $options['format'] = 'tar.gz';
            } else if ($options['format'] == 'bz2') {
                $options['format'] = 'tar.bz2';
            }
        }
        // obtener directorio que contiene al archivo/directorio y el nombre de este
        $filepath = $file;
        $dir = dirname($file);
        $file = basename($file);
        $file_compressed = $file.'.'.$options['format'];
        // empaquetar/comprimir directorio/archivo
        if ($options['format'] == 'zip') {
            // crear archivo zip
            $zip = new \ZipArchive();
            if (file_exists($dir.DIRECTORY_SEPARATOR.$file.'.zip')) {
                unlink($dir.DIRECTORY_SEPARATOR.$file.'.zip');
            }
            if ($zip->open($dir.DIRECTORY_SEPARATOR.$file.'.zip', \ZipArchive::CREATE) !== true) {
                return false;
            }
            // agregar un único archivo al zip
            if (!is_dir($filepath)) {
                $zip->addFile($filepath, $file);
            }
            // agregar directorio al zip
            else if (is_dir($filepath)) {
                $Iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($filepath));
                foreach ($Iterator as $f) {
                    if (!$f->isDir()) {
                        $path = $f->getPath().DIRECTORY_SEPARATOR.$f->getFilename();
                        $zip->addFile($path, str_replace($filepath, '', $file.DIRECTORY_SEPARATOR.$path));
                    }
                }
            }
            // escribir en el sistema de archivos y cerrar archivo
            file_put_contents($dir.DIRECTORY_SEPARATOR.$file_compressed, $zip->getStream(md5($filepath)));
            $zip->close();
        } else {
            exec('cd '.$dir.' && '.str_replace(':in', $file, $options['commands'][$options['format']]));
        }
        // enviar archivo
        if ($options['download']) {
            ob_clean();
            header ('Content-Disposition: attachment; filename='.$file_compressed);
            $mimetype = self::mimetype($dir.DIRECTORY_SEPARATOR.$file_compressed);
            if ($mimetype) {
                header ('Content-Type: '.$mimetype);
            }
            header ('Content-Length: '.filesize($dir.DIRECTORY_SEPARATOR.$file_compressed));
            readfile($dir.DIRECTORY_SEPARATOR.$file_compressed);
            unlink($dir.DIRECTORY_SEPARATOR.$file_compressed);
        }
        // borrar directorio o archivo que se está comprimiendo si así se ha
        // solicitado
        if ($options['delete']) {
            if (is_dir($filepath)) {
                self::rmdir($filepath);
            } else {
                unlink($filepath);
            }
        }
        // todo ok
        return true;
    }

    /**
     * Extrae un archivo de un fichero comprimido ZIP.
     *
     * Busca y extrae un archivo específico dentro de un archivo ZIP,
     * incluyendo subdirectorios, o una ruta exacta si el nombre del
     * archivo comienza con "/".
     *
     * @param string $zipFile Nombre del archivo comprimido ZIP.
     * @param string|null $searchedFile Nombre del archivo que se busca
     * extraer. Si se proporciona una ruta que comienza con "/", se
     * buscará una coincidencia exacta.
     * @return array|false Retorna un arreglo asociativo con las claves
     * 'name', 'type', 'size', y 'data' correspondientes al archivo
     * extraído, o false si el archivo no se encuentra o si ocurre un error.
     * @example ezip('path/to/myzip.zip', 'myfile.txt')
     * Extrae "myfile.txt" del ZIP.
     * @example ezip('path/to/myzip.zip', '/path/to/myfile.txt')
     * Extrae "myfile.txt" usando la ruta completa.
     */
    public static function ezip(string $zipFile, ?string $searchedFile = null)
    {
        $zip = new \ZipArchive();
        $res = $zip->open($zipFile);
        if ($res === true) {
            $fileData = false;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (
                    $searchedFile !== null
                    && (
                        basename($name) === $searchedFile
                        || $name === ltrim($searchedFile, '/')
                    )
                ) {
                    $entry = $zip->getFromIndex($i);
                    if ($entry !== false) {
                        $stat = $zip->statIndex($i);
                        $size = $stat['size'];
                        // Temporalmente guarda datos en un archivo para
                        // determinar el tipo MIME.
                        $tmpFile = tempnam(sys_get_temp_dir(), 'ZIP');
                        file_put_contents($tmpFile, $entry);
                        $type = self::mimetype($tmpFile);
                        unlink($tmpFile); // Elimina el archivo temporal
                        // Armar arreglo con los datos del archivo.
                        $fileData = [
                            'name' => $name,
                            'type' => $type,
                            'size' => $size,
                            'data' => $entry,
                        ];
                        break;
                    }
                }
            }
            $zip->close();
        } else {
            $fileData = false;
        }
        return $fileData;
    }

    /**
     * Método que sanitiza el nombre de un archivo para ser usado en el sistema de archivos
     * @param string $filename nombre del archivo que se desea limpiar el nombre
     * @return string Nombre del archivo ya normalizado y limpiado para ser usado en el sistema de archivos
     */
    public static function sanitize($filename)
    {
        $extension = \sowerphp\core\Utility_String::normalize(self::extension($filename));
        return \sowerphp\core\Utility_String::normalize(
            substr($filename, 0, strrpos($filename,'.'))
        ) . '.' . $extension;
    }

}
