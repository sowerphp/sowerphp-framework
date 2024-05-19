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

/**
 * Clase para cargar una página.
 */
class View_Helper_Page
{

    /**
     * Método que determina (según la extensión) que tipo de renderizado
     * se necesita hacer y lo ejecuta.
     * @param string $location Ubicación de la vista que se desea renderizar.
     * @param array $viewVars Variables que se deben pasar a la vista.
     * @return string Buffer de la página renderizada.
     */
    public static function render(string $location, array $viewVars=[]): string
    {

        $ext = substr($location, strrpos($location, '.') + 1);
        $class = 'View_Helper_Pages_' . ucfirst($ext);
        return $class::render($location, $viewVars);
    }

}
