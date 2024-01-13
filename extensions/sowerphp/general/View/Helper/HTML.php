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
 * Helper para la creación de código HTML genérico a través de métodos estáticos
 */
class View_Helper_HTML
{

    /**
     * Método para cargar todos los archivos de un directorio como código y
     * "pintarlo" mediante http://google-code-prettify.googlecode.com
     * @param src Archivo o directorio (fullpath)
     * @param recursive Procesar (o no) de forma recursiva el directorio src
     * @param ext Procesar solo extensiones indicadas
     * @param header Header desde el cual se desea utilizar (ej: 2, que es <h2>)
     */
    public static function codeFrom($src, $recursive = false, $ext = array(), $header = 2)
    {
        // en caso que sea un directorio se recorre recursivamente
        if (is_dir($src)) {
            // si se limitan extensiones
            $restrictExtensions = (bool)count($ext);
            // buscar archivos
            $files = scandir($src);
            foreach ($files as &$file) {
                // si es archivo oculto se omite
                if ($file[0] == '.') {
                    continue;
                }
                // si se limitan extensiones y no esta en las permitidas saltar
                if ($restrictExtensions && !in_array(substr($file, strrpos($file, '.')+1), $ext)) {
                    continue;
                }
                // si es un directorio, verificar que se deba procesar
                // recursivamente, sino se veran solo archivos
                if (is_dir($src.'/'.$file) && !$recursive) {
                    continue;
                }
                // si es un directorio colocar el nombre del directorio
                if (is_dir($src.'/'.$file)) {
                    $permalink = \sowerphp\core\Utility_String::normalize($file);
                    echo "<h$header id=\"$permalink\">$file <a href=\"#$permalink\">&lt;&gt;</a></h$header>";
                }
                // llamar a la función por cada archivo
                self::codeFrom($src.'/'.$file, $recursive, $ext, ++$header);
            }
        }
        // si no es directorio entonces es un archivo, se muestra
        else {
            echo '<div><strong>',basename($src),'</strong></div>';
            echo '<pre class="prettyprint"><code>';
            echo htmlspecialchars(file_get_contents($src));
            echo '</code></pre>',"\n\n";
        }
    }

    /**
     * Función para crear enlaces hacia archivos que se encuentran en un
     * directorio visible desde el DocummentRoot, la ruta que se debe indicar en
     * dir debe ser la ruta completa que se vería desde dominio.com/full/path, o
     * sea (en este caso) dir = /full/path
     * @param dir Directorio donde están los archivos que se desean enlazar
     * @param recursive Procesar (o no) de forma recursiva el directorio dir
     */
    public static function linksFrom($dir, $recursive = false)
    {
        $realdir = DIR_WEBSITE.'/webroot'.$dir;
        if (!is_dir($realdir)) {
            echo '<p>No es posible leer el directorio de archivos.</p>';
            return;
        }
        $files = scandir($realdir);
        echo '<ul>',"\n";
        // procesar cada archivo
        foreach ($files as &$file) {
            // si es archivo oculto o no hay permiso de lectura => se omite
            if ($file[0] == '.' || !is_readable($realdir.'/'.$file)) {
                continue;
            }
            // si es un directorio
            if (is_dir($realdir.'/'.$file)) {
                // verificar que se deba procesar recursivamente, sino se veran
                // solo archivos
                if (!$recursive) {
                    continue;
                }
                // mostrar directorio y llamar función de forma recursiva
                echo '<li style="list-style-image: url(\'',url('/img/icons/16x16/files/directory.png'),'\')">';
                echo '<span style="display:block;margin-bottom:1em">',str_replace(array('_', '-'), ' ', $file),'</span>',"\n";
                self::linksFrom($dir.'/'.$file, $recursive);
                echo '</li>',"\n";
            }
            // si es un archivo
            else {
                // definir nombre y extensión
                if (strrchr($file, '.')!==FALSE) {
                    $ext = substr(strrchr($file, '.'), 1);
                    $name = str_replace(array('_', '-'), ' ', preg_replace("/.$ext$/", '', $file));
                } else {
                    $ext = '';
                    $name = str_replace(array('_', '-'), ' ', $file);
                }
                // buscar icono a partir de la extension
                $icon = \sowerphp\core\App::location('webroot/img/icons/16x16/files/'.$ext.'.png');
                if ($icon) {
                    $icon = 'img/icons/16x16/files/'.$ext.'.png';
                } else {
                    $icon = 'img/icons/16x16/files/generic.png';
                }
                // mostrar enlace
                echo '<li style="list-style-image: url(\'',url('/'.$icon),'\')"><a href="',url($dir.'/'.$file),'">',$name,'</a></li>',"\n";
            }
        }
        echo '</ul>',"\n";
    }

    /**
     * @link http://stackoverflow.com/questions/1960461/convert-plain-text-urls-into-html-hyperlinks-in-php
     */
    public static function makeClickableLinks($string)
    {
        return preg_replace(
            '@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@',
            '<a href="$1">$1</a>',
            $string
        );
    }

}
