<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
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

use \Michelf\Markdown;

/**
 * Motor de renderizado de plantilla HTML utilizando Markdown para el contenido
 * principal de la vista y PHP puro para el Layout de la página con markdown.
 */
class View_Engine_Markdown extends View_Engine
{

    /**
     * Renderizar una plantilla markdown y devolver el resultado como una
     * cadena. Además, si se ha solicitado, se entregará el contenido dentro de
     * un layout que se renderizará con PHP.
     *
     * @param string $filepath Ruta a la plantilla markdown que se va a
     * renderizar.
     * @param array $data Datos que se pasarán a la plantilla markdown para su
     * uso dentro de la vista.
     * @return string El contenido HTML generado por la plantilla markdown.
     */
    public function render(string $filepath, array $data): string
    {
        // Renderizar contenido del archivo Markdown.
        $content = file_get_contents($filepath);
        foreach ($data as $key => $value) {
            if (
                is_scalar($value)
                || (is_object($value) && method_exists($value, '__toString'))
            ) {
                $content = preg_replace(
                    '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/',
                    $value,
                    $content
                );
            }
        }
        $content = str_replace(
            ['<h1>', '</h1>'],
            ['<div class="page-header"><h1>', '</h1></div>'],
            Markdown::defaultTransform($content)
        );
        if (empty($data['__view_layout'])) {
            return $content;
        }
        $data['_content'] = $content;
        // Renderizar el layout solicitado con el contenido previamente
        // determinado ya incluído en los datos del layout.
        $extension = substr(
            $data['__view_layout'],
            strrpos($data['__view_layout'], '.')
        );
        if ($extension != '.php') {
            $data['__view_layout'] .= '.php';
        }
        $layout = $this->viewService->resolveLayout($data['__view_layout']);
        return app('view_engine_php')->renderPhp($layout, $data);
    }

}
