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

/**
 * Motor de renderizado de plantilla HTML utilizando Blade de Laravel.
 */
class View_Engine_Blade extends View_Engine
{

    /**
     * Renderizar una plantilla blade y devolver el resultado como una cadena.
     *
     * @param string $filepath Ruta a la plantilla blade que se va a renderizar.
     * @param array $data Datos que se pasarán a la plantilla blade para su uso
     * dentro de la vista.
     * @return string El contenido HTML generado por la plantilla blade.
     */
    public function render(string $filepath, array $data): string
    {
        throw new \Exception('Plantillas blade actualmente no soportadas.');
    }

}
