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

namespace sowerphp\general;

/**
 * Esta clase permite leer y generar archivos xml
 */
class Utility_Spreadsheet_XML
{

    /**
     * Clase que lee un archivo XML
     * @todo Implementar método
     */
    public static function read($archivo)
    {
    }

    /**
     * Función para generar un archivo XML a partir de una "tabla"
     */
    public static function generate($data, $id)
    {
        // limpiar posible contenido envíado antes
        ob_clean();
        // cabeceras del archivo
        header('Content-type: application/xml');
        header('Content-Disposition: attachment; filename='.$id.'.xml');
        header('Pragma: no-cache');
        header('Expires: 0');
        // cuerpo del archivo
        $root = \sowerphp\core\Utility_String::normalize(\sowerphp\core\Utility_Inflector::underscore($id));
        $item = \sowerphp\core\Utility_Inflector::singularize($root);
        echo '<?xml version="1.0" encoding="utf-8" ?>',"\n";
        echo '<',$root,'>',"\n";
        $titles = array_shift($data);
        foreach ($titles as &$col) {
            $col = \sowerphp\core\Utility_String::normalize(trim(strip_tags($col)));
        }
        foreach($data as &$row) {
            echo "\t",'<',$item,'>',"\n";
            foreach($row as $key => &$col) {
                $key = $titles[$key];
                echo "\t\t",'<',$key,'>',rtrim(str_replace('<br />', ', ', strip_tags($col, '<br>')), " \t\n\r\0\x0B,"),'</',$key,'>',"\n";
            }
            echo "\t",'</',$item,'>',"\n";
        }
        echo '</',$root,'>',"\n";
        // terminar script
        exit(0);
    }

}
