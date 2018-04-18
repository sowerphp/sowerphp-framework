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

namespace sowerphp\core;

/**
 * Clase para cargar una página en formato Markdown
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-29
 */
class View_Helper_Pages_Md
{

    /**
     * Método que renderiza una página en formato Markdown
     * @param file Archivo que se desea renderizar
     * @param vars Arreglo con variables que se desean pasar
     * @return Buffer de la página renderizada
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2018-04-18
     */
    public static function render($file, array $variables = [])
    {
        $data = file_get_contents($file);
        foreach ($variables as $key => $valor) {
            if (!is_object($valor)) {
                $data = str_replace('{'.$key.'}', $valor, $data);
            }
        }
        return str_replace(
            ['<h1>', '</h1>'],
            ['<div class="page-header"><h1>', '</h1></div>'],
            \Michelf\Markdown::defaultTransform($data)
        );
    }

}
