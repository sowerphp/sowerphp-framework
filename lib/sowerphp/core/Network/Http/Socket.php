<?php

/**
 * SowerPHP: Minimalist Framework for PHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

namespace sowerphp\core;

/**
 * Clase para manejar conexiones HTTP
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-26
 */
class Network_Http_Socket
{

    /**
     * Método para enviar datos mediante POST a una URL
     * @param url URL donde se enviarán los datos
     * @param data Datos que se enviarán
     * @param header Cabecera que se enviará
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-11-30
     */
    public static function post ($url, $data = array(),
                $header = "Content-Type: application/x-www-form-urlencoded\n")
    {
        // Generar contenido (variables) a enviar
        $content = array();
        foreach ($data as $key=>&$value) {
            $content[] = $key.'='.$value;
        }
        $content = implode('&', $content);
        // Crear parametros para la conexion
        $params = array(
            'http' => array(
                'method' => 'POST',
                'header' => $header,
                'content' => $content
            )
        );
        // Enviar datos por post
        $body = @file_get_contents (
            $url,
            false,
            stream_context_create($params)
        );
        if ($body===false) return false;
        return ['body'=>$body];
    }

}
