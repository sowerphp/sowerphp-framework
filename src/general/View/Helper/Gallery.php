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
 * Clase para crear una galería de imágenes
 */
class View_Helper_Gallery
{

    private $_name;

    public function __construct ($dir = null, $name = 'galeria')
    {
        $this->_name = $name;
        // si se indico un directorio se genera e imprime (like TableHelper)
        if ($dir) {
            echo $this->generate($dir);
        }
    }

    public function generate($dir)
    {
        $projectDir = app('layers')->getProjectPath();
        // buffer para ir dibujando la galería
        $buffer = '';
        // obtener cabecera
        $buffer .= $this->header();
        // inicio de la galería
        $buffer .= '<div>'."\n";
        // obtener imagenes (si existen miniaturas se usan)
        if(file_exists($projectDir . '/public' . $dir . '/miniaturas')) {
            $imagenes = scandir($projectDir . '/public' . $dir . '/miniaturas');
            $miniaturas = '/miniaturas';
        } else {
            $imagenes = scandir($projectDir . '/public' . $dir);
            $miniaturas = '';
        }
        // mostrar imagenes
        foreach($imagenes as &$imagen) {
            if (!is_dir($projectDir . '/public' . $dir . $miniaturas . '/' . $imagen)) {
                $buffer .= '<a href="'.url($dir.'/'.$imagen).'" rel="prettyPhoto['.$this->_name.']"><img src="'.url($dir.$miniaturas.'/'.$imagen).'" alt="'.$imagen.'" class="pp-thumbnail" /></a>'."\n";
            }
        }
        // fin de la galería
        $buffer .= '</div>'."\n";
        // retornar bufer
        return $buffer;
    }

    private function header ()
    {
        return '
            <link rel="stylesheet" href="'.url('/css/prettyPhoto.css').'" type="text/css" media="screen" charset="utf-8" />
            <script src="'.url('/js/jquery.browser.min.js').'" type="text/javascript" charset="utf-8"></script>
            <script src="'.url('/js/jquery.prettyPhoto.js').'" type="text/javascript" charset="utf-8"></script>
            <style type="text/css">
            img.pp-thumbnail {
                margin: 5px;
                padding: 3px;
                border: solid 1px #CCC;
                -moz-box-shadow: 1px 1px 5px #999;
                -webkit-box-shadow: 1px 1px 5px #999;
                box-shadow: 1px 1px 5px #999;
                max-width: 160px;
                max-height: 120px;
            }
            </style>
            <script type="text/javascript" charset="utf-8">
                $(document).ready(function(){
                    $("a[rel^=\'prettyPhoto\']").prettyPhoto();
                });
            </script>
        ';
    }

}
