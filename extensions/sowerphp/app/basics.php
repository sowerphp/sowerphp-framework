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

// función para la creación de enlaces a FAQ en las alertas entregadas por la aplicación
function message_make_links($string, $html = true) {
    // preguntas frecuentes de la aplicación
    if (strpos($string, '[faq:') !== false) {
        $faq = (array)\sowerphp\core\Configure::read('faq');
        if (!empty($faq['url']) and !empty($faq['text'])) {
            $replace = $html
                        ? '<a href="'.$faq['url'].'$2" target="_blank" class="alert-link">'.$faq['text'].'</a>'
                        : $faq['text'].': '.$faq['url'].'$2';
            $string = preg_replace(
                '/\[(faq):([\w\d]+)\]/i',
                $replace,
                $string,
            );
        }
    }
    // entregar string modificado con los enlaces correspondientes
    return $string;
}
