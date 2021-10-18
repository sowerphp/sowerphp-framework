<?php

/**
 * SowerPHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
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

namespace sowerphp\general;

/**
 * Clase para manejar archivos y directorios
 *
 * Esta clase permite realizar diversas acciones sobre archivos y directorios
 * que se encuentren en el servidor donde se ejecuta la aplicación. Tales como:
 * listar contenido de directorios, subir archivos al servidor, extraer archivos
 * comprimidos, comprimir archivos y directorios, etc.
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2015-04-21
 */
class Utility_File
{

    // constantes para errores de la subida de archivos
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
     * @param src Arreglo de $_FILES o bien el índice de $_FILES
     * @param filters Filtros que se deben validar al subir la imagen (extensions, mimetypes, size, width y height)
     * @return Arreglo con los datos del archivo (índices: data, name, type y size) o error en caso de falló
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-04-21
     */
    public static function upload($src, $filters = [])
    {
        // si es string se debe recuperar el valor desde variables $_FILES
        if (is_string($src)) {
            if (!isset($_FILES[$src]))
                return self::UPLOAD_ERROR;
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
            if (is_string($filters['extensions']))
                $filters['extensions'] = [$filters['extensions']];
            if (!in_array(self::extension($src['name']), $filters['extensions']))
                return self::UPLOAD_ERROR_EXTENSION;
        }
        // verificar mimetype
        if (!empty($filters['mimetypes'][0])) {
            if (is_string($filters['mimetypes']))
                $filters['mimetypes'] = [$filters['mimetypes']];
            if (!in_array($src['type'], $filters['mimetypes']))
                return self::UPLOAD_ERROR_MIMETYPE;
        }
        // verificar tamaño
        if ($filters['size'] and $src['size']>($filters['size']*1024)) {
            return self::UPLOAD_ERROR_SIZE;
        }
        // si se ha definido ancho o algo máximo entonces es una imagen y se
        // debe verificar su tamaño, si lo excede la imagen se debe escalar
        if ($filters['width'] and $filters['height']) {
            list($file['width'], $file['height']) = getimagesize($src['tmp_name']);
            if ($file['width']>$filters['width'] || $file['height']>$filters['height']) {
                list($file['width'], $file['height']) = Utility_Image::resizeOnFile($src['tmp_name'], $filters['width'], $filters['height']);
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
     * Recupera los archivos/directorios desde una carpeta
     * @param dir Nombre del directorio a examinar
     * @return Arreglo con los nombres de los archivos y/o directorios
     * @todo Selección de sólo algunos archivos de la carpeta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-03-15
     */
    public static function browseDirectory($dir)
    {
        $filesAux = scandir($dir);
        $files = [];
        foreach($filesAux as &$file) {
            if($file[0]!='.') $files[] = $file;
        }
        return $files;
    }

    /**
     * Obtiene el tamaño de un fichero o directorio (método basado en función encontrada en Internet)
     * @param filepath Nombre del archivo/directorio a consultar tamaño
     * @param mostrarUnidad =true mostrara la unidad (KB, MB, etc)
     * @return Tamaño del archivo/directorio o bien descripción del error ocurrido
     * @author Desconocido, http://www.blasten.com/contenidos/?id=Tama?o_de_archivo_en_byte,_Kb,_Mb,_y_Gb
     * @version 2015-04-24
     */
    public static function getSize($filepath, $mostrarUnidad = true)
    {
        $method = array('B','KiB','MiB','GiB', 'TiB');
        $size = 0;
        if (!file_exists($filepath)) { // verificar que el archivo exista
            return 'Archivo no existe';
        } else if (!is_file($filepath) && !is_dir($filepath)) { // verificar que sea un fichero o directorio
            return '"'.$file.'" no válido';
        } else {
            if (is_dir($filepath) and $dir = opendir($filepath)) { // abrir el directorio
                while ($file = readdir($dir)) {
                    if (is_dir($filepath.DIRECTORY_SEPARATOR.$file)) { // si el archivo es un directorio lo recorre recursivamente
                        if ($file != '.' && $file != '..') { // no recorre el dir padre ni el mismo recursivamente
                            $size += self::getSize($filepath.DIRECTORY_SEPARATOR.$file, false); // llamada recursiva sin unidad
                        }
                    } else {
                        $size += filesize ($filepath.DIRECTORY_SEPARATOR.$file); // si no es directorio se retorna el tamaño del archivo
                    }
                }
                closedir($dir);
            } else { // si no es directorio se retorna el tamaño del archivo
                $size += filesize($filepath);
            }
        }
        clearstatcache();
        // dependiendo del tamaño del archivo se le coloca la unidad
        if (!$mostrarUnidad) return $size;
        if ($size <= 1024) // B
            return $size.' '.$method[0];
        else if ($size >= pow(1024, 4)) // TB
            return round($size/pow(1024, 4), 2).' '.$method[4];
        else if ($size >= pow(1024, 3)) // GB
            return round($size/pow(1024, 3), 2).' '.$method[3];
        else if ($size >= pow(1024, 2)) // MB
            return round($size/pow(1024, 2), 2).' '.$method[2];
        else // KB
            return round($size/1024, 2).' '.$method[1];
    }

    /**
     * Abre un archivo y lo devuelve en el formato de arreglo
     * @param file_name Ruta hacia el archivo
     * @return array con indices: name, type, size y data
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2011-08-11
     */
    public static function get($file_name)
    {
        // si el archivo no existe se retorna null
        if (!file_exists($file_name)) return null;
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
        return $file;
    }

    /**
     * Borra recursivamente un directorio
     * @param dir Directorio a borrar
     * @author http://en.kioskea.net/faq/793-warning-rmdir-directory-not-empty
     * @version 2015-04-21
     */
    public static function rmdir($dir)
    {
        // List the contents of the directory table
        $dir_content = scandir ($dir);
        // Is it a directory?
        if ($dir_content!==false) {
            // For each directory entry
            foreach ($dir_content as &$entry) {
                // Unix symbolic shortcuts, we go
                if (!in_array ($entry, array ('.','..'))) {
                    // We find the path from the beginning
                    $entry = $dir.DIRECTORY_SEPARATOR. $entry;
                    // This entry is not an issue: it clears
                    if (!is_dir($entry)) {
                        unlink ($entry);
                    } else { // This entry is a folder, it again on this issue
                        self::rmdir($entry);
                    }
                }
            }
        }
        // It has erased all entries in the folder, we can now erase
        rmdir ($dir);
    }

    /**
     * Determinar la extensión de un archivo a partir de su nombre
     * @param file Ruta hacia el archivo (o nombre del mismo)
     * @return Extensión si existe
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-03-11
     */
    public static function extension($file)
    {
        $dot = strrpos($file, '.');
        return $dot!==false ? strtolower(substr($file, $dot+1)) : null;
    }

    /**
     * Método que cuenta la cantidad de líneas que un archivo posee
     * @param file Ruta hacia el fichero
     * @return Cantidad de líneas del fichero
     * @author http://stackoverflow.com/a/20537130
     * @version 2013-12-12
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
     * @param file Ruta hacia el fichero
     * @return Mimetype del fichero o =false si no se pudo determinar
     * @author http://stackoverflow.com/a/23287361
     * @version 2015-11-03
     */
    public static function mimetype($file)
    {
        if (!function_exists('finfo_open'))
            return false;
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype = finfo_file($finfo, $file);
        finfo_close($finfo);
        return $mimetype;
    }

    /**
     * Método que empaqueta y comprime archivos (uno o varios, o directorios).
     * Si se pide usar formato zip entonces se usará ZipArchive de PHP para
     * comprimir
     * @param filepath Directorio (o archivo) que se desea comprimir
     * @param options Arreglo con opciones para comprmir (format, download, delete)
     * @todo Preparar datos si se pasa un arreglo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-03-15
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
        if (!is_readable($file))
            return false;
        // si es formato gz y es directorio se cambia a tgz
        if (is_dir($file)) {
            if ($options['format']=='gz') $options['format'] = 'tar.gz';
            else if ($options['format']=='bz2') $options['format'] = 'tar.bz2';
        }
        // obtener directorio que contiene al archivo/directorio y el nombre de este
        $filepath = $file;
        $dir = dirname($file);
        $file = basename($file);
        $file_compressed = $file.'.'.$options['format'];
        // empaquetar/comprimir directorio/archivo
        if ($options['format']=='zip') {
            // crear archivo zip
            $zip = new \ZipArchive();
            if (file_exists($dir.DIRECTORY_SEPARATOR.$file.'.zip'))
                unlink($dir.DIRECTORY_SEPARATOR.$file.'.zip');
            if ($zip->open($dir.DIRECTORY_SEPARATOR.$file.'.zip', \ZipArchive::CREATE)!==true)
                return false;
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
            if ($mimetype)
                header ('Content-Type: '.$mimetype);
            header ('Content-Length: '.filesize($dir.DIRECTORY_SEPARATOR.$file_compressed));
            readfile($dir.DIRECTORY_SEPARATOR.$file_compressed);
            unlink($dir.DIRECTORY_SEPARATOR.$file_compressed);
        }
        // borrar directorio o archivo que se está comprimiendo si así se ha
        // solicitado
        if ($options['delete']) {
            if (is_dir($filepath)) self::rmdir($filepath);
            else unlink($filepath);
        }
    }

    /**
     * Extrae un archivo de un fichero comprimido zip
     * @param archivoZip Nombre del archivo comprimido
     * @param archivoBuscado Nombre del archivo que se busca extraer
     * @return Arreglo con índices name, type (no definido), size y data (idem self::upload)
     * @warning Sólo extrae un archivo del primer nivel (fuera de directorios)
     * @todo Extracción de un fichero que este en subdirectorios
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-09-14
     */
    public static function ezip($archivoZip, $archivoBuscado = null)
    {
        $zip = zip_open($archivoZip);
        if (is_resource($zip)) {
            // buscar contenido
            do {
                $entry = zip_read($zip);
                if ($entry===false) {
                    continue;
                }
                $name = zip_entry_name($entry);
            } while ($entry && $name != $archivoBuscado && $archivoBuscado!==null);
            if ($entry===false) {
                return false;
            }
            // abrir contenido
            zip_entry_open($zip, $entry, 'r');
            $size = zip_entry_filesize($entry);
            $entry_content = zip_entry_read($entry, $size);
            // pasar datos del archivo
            $archivo['name'] = $name;
            $archivo['type'] = null;
            $archivo['size'] = $size;
            $archivo['data'] = $entry_content;
        } else {
            $archivo = false;
        }
        return $archivo;
    }

    /**
     * Método que sanitiza el nombre de un archivo para ser usado en el sistema de archivos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2018-12-02
     */
    public static function sanitize($filename)
    {
        $extension = \sowerphp\core\Utility_String::normalize(self::extension($filename));
        return \sowerphp\core\Utility_String::normalize(substr($filename, 0, strrpos($filename,'.'))).'.'.$extension;
    }

}
