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

/**
 * Motor de renderizado de plantilla HTML utilizando PHP puro.
 */
class View_Engine_Php extends View_Engine
{

    /**
     * Renderizar un archivo PHP y devolver el resultado como una cadena.
     * Además, si se ha solicitado, se entregará el contenido dentro de un
     * layout que también se renderizará con PHP.
     *
     * @param string $filepath Ruta al archivo PHP que se va a renderizar.
     * @param array $data Datos que se pasarán al archivo PHP para su uso
     * dentro de la vista.
     * @return string El contenido HTML generado por el archivo PHP.
     */
    public function render(string $filepath, array $data): string
    {
        // Renderizar contenido del archivo PHP.
        $content = $this->renderPhp($filepath, $data);
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
        return $this->renderPhp($layout, $data);
    }

    /**
     * Método que toma un archivo PHP y lo renderiza reemplazando las variables
     * que existan en dicho archivo.
     *
     * @param string $__view_filepath Ruta absoluta al archivo PHP.
     * @param array $__data Variables que se desean reemplazar.
     * @return string El contenido del archivo ya renderizado.
     */
    public function renderPhp(string $__view_filepath, array &$__data): string
    {
        ob_start(); // NOTE: obligatorio o se incluirá en la salida.
        extract($__data, EXTR_SKIP);
        include $__view_filepath;
        $vars = get_defined_vars();
        foreach ($vars as $var => $val) {
            if (substr($var, 0, 7) === '__view_') {
                $__data[$var] = $val;
            }
        }
        return ob_get_clean();
    }

}
