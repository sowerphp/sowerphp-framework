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

namespace sowerphp\general;

/**
 * Helper para la creación de tablas en HTML para explorar un servidor FTP
 */
class View_Helper_FTP
{

    private $ftp; ///< Conexión al servidor FTP
    private $rootDir; ///< Directorio raíz del explorador

    /**
     * Constructor del helper para acceder al servidor FTP
     * @param config Configuración para el servidor FTP (mismos \sowerphp\core\Network_Ftp)
     */
    public function __construct($config, $rootDir = '/')
    {
        $this->ftp = new \sowerphp\core\Network_Ftp($config);
        $this->rootDir = $rootDir;
        if (isset($_GET['download'])) {
            $this->download($_GET['download']);
        } else {
            $this->browse(isset($_GET['dir'])?$_GET['dir']:'/');
        }
    }

    /**
     * Método que descarga un archivo desde el servidor FTP
     * @param file Archivo que se desea descargar
     */
    private function download($file)
    {
        ob_clean();
        readfile($this->ftp->getUri().$this->rootDir.str_replace('../', '', substr($file, 1)));
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=".substr($file, 1));
        exit;
    }

    /**
     * Método que genera la tabla con el contenido de un directorio del servidor FTP
     * @param dir Directorio que se desea explorar
     */
    private function browse($dir)
    {
        $dir = str_replace('../', '', substr($dir, 1));
        $aux = $this->ftp->scandir($this->rootDir.$dir);
        $files = array_merge(isset($aux['d'])?$aux['d']:[], isset($aux['-'])?$aux['-']:[]);
        foreach ($files as &$f) {
            $link = $f['type']=='d' ? 'dir=/'.$dir.$f['name'].'/' : 'download=/'.$dir.$f['name'];
            $f['name'] = '<a href="?'.$link.'">'.($f['type']=='d'?'<span class="fa fa-folder"></span> ':'').$f['name'].'</a>';
            $f['size'] = $f['type']=='d' ? null : $this->sizeFormat($f['size']);
            unset($f['type'], $f['perm'], $link);
        }
        if ($dir != '') {
            $link = substr($dir, 0, strrpos($dir, '/', -2));
            array_unshift(
                $files,
                [
                    '<a href="?dir=/'.($link?$link.'/':$link).'"><span class="fa fa-folder"></span> ..</a>',
                    '',
                    ''
                ]
            );
        }
        echo $this->breadcrumb($dir),"\n";
        array_unshift($files, ['Archivo', 'Tamaño', 'Modificado']);
        new \sowerphp\general\View_Helper_Table($files);
    }

    /**
     * Método que genera el breadcrumb a partir de un ruta
     * @param path Ruta de los directorios
     * @return string Breadcrumb con la ruta de los directorios
     */
    private function breadcrumb($path)
    {
        $dirs = $path ? explode('/', substr($path, 0, -1)) : [];
        $n_dirs = count($dirs);
        $buffer = '<nav aria-label="breadcrumb">';
        $buffer .= '<ol class="breadcrumb">';
        $buffer .= $dirs ? '<li class="breadcrumb-item"><a href="?dir=/">Raíz</a></li>' : '<li class=" breadcrumb-item active">Raíz</li>';
        for ($i=0; $i<$n_dirs; $i++) {
            if ($i+1<$n_dirs) {
                $buffer .= '<li class="breadcrumb-item"><a href="?dir=/'.implode('/', array_slice($dirs, 0, $i+1)).'/">'.$dirs[$i].'</a></li>';
            } else {
                $buffer .= '<li class="breadcrumb-item active">'.$dirs[$i].'</li>';
            }
        }
        $buffer .= '</ol>';
        $buffer .= '</nav>';
        return $buffer;
    }

    /**
     * Método que da formato al tamaño del archivo del servidor FTP
     * @param size Tamaño en bytes
     * @return string Tamaño en XiB donde X será la unidad de acuerdo al tamaño
     */
    private function sizeFormat($size)
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];
        $n_units = count($units);
        $unit = 0;
        for ($i=0; $i<$n_units; $i++) {
            if ($size>=1024) {
                $size /= 1024;
                $unit = $i+1;
            }
        }
        return round($size, 1).' '.$units[$unit];
    }

}
