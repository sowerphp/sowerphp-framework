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
 * Esta clase permite leer y generar archivos json
 */
final class Utility_Spreadsheet_JSON
{

    /**
     * @todo Implementar método (debe ser el "inverso" de self::generate())
     */
    public static function read($archivo) {
    }

    /**
     * Método que genera un string JSON a partir de una tabla en un arreglo
     * bidimensional
     */
    public static function generate($data, $id)
    {
        // limpiar posible contenido envíado antes
        ob_clean();
        // cabeceras del archivo
        header('Content-type: application/json');
        header('Content-Disposition: attachment; filename='.$id.'.json');
        header('Pragma: no-cache');
        header('Expires: 0');
        // cuerpo del archivo
        $datos = [];
        $titles = array_shift($data);
        foreach ($titles as &$col) {
            $col = \sowerphp\core\Utility_String::normalize(trim(strip_tags($col)));
        }
        foreach ($data as &$row) {
            $dato = [];
            foreach($row as $key => &$col) {
                $dato[$titles[$key]] = rtrim(str_replace('<br />', ', ', strip_tags($col, '<br>')), " \t\n\r\0\x0B,");
            }
            $datos[] = $dato;
        }
        echo json_encode($datos, JSON_PRETTY_PRINT);
        // liberar memoria y terminar script
        unset($titles, $data, $datos, $id);
        exit(0);
    }

}
