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
 * Clase para realizar operaciones sobre imagenes
 */
class Utility_Image
{

    /**
     * Obtiene el área en la que se encuentran caras en una foto
     * @param photo Ruta de la imagen
     * @return array Arreglo con las coordenadas del area y su centro
     */
    public static function face_area($photo)
    {
        // si el archivo no existe se retorna null
        if (!file_exists($photo)) {
            return null;
        }
        // variable para devolver coordenadas
        $coordinates = null;
        // si existe el modulo Face Detect se utiliza para encontrar la cara
        if (function_exists('face_detect')) {
            // detectar caras
            $faces = face_detect($photo, 'cascade.xml');
            // si encontro alguna cara se procesa
            if (isset($faces[0])) {
                // buscar area que reune a todas las caras de la foto (x1,y1 y x2,y2)
                foreach ($faces as &$face) {
                    // determinar inicio
                    if (!isset($coordinates['x1']) || $face['x']<$coordinates['x1']) {
                        $coordinates['x1'] = $face['x'];
                    }
                    if (!isset($coordinates['y1']) || $face['y']<$coordinates['y1']) {
                        $coordinates['y1'] = $face['y'];
                    }
                    // determinar fin
                    $x2 = $face['x'] + $face['w'];
                    $y2 = $face['y'] + $face['h'];
                    if (!isset($coordinates['x2']) || $x2>$coordinates['x2']) {
                        $coordinates['x2'] = $x2;
                    }
                    if (!isset($coordinates['y2']) || $y2>$coordinates['y2']) {
                        $coordinates['y2'] = $y2;
                    }
                }
            }
        }
        // si las coordenadas son nulas, ya sea porque no se encontro cara o porque no existia
        // el modulo se determinan las coordenadas segun las geometrias de la imagen
        if ($coordinates === null) {
            // obtener tamaño de la imagen (0=ancho, 1=alto)
            $size = getimagesize($photo);
            $x = $size[0];
            $y = $size[1];
            // la imagen se encuentra vertial
            if ($y > $x) {
                $coordinates['x1'] = 0;
                $coordinates['x2'] = $x;
                $coordinates['y1'] = ($y-$x)/2;
                $coordinates['y2'] = $coordinates['y1'] + $x -1;
            }
            // la imagen se encuentra vertical (o es cuadrada)
            else {
                $coordinates['x1'] = ($x-$y)/2;
                $coordinates['x2'] = $coordinates['x1'] + $y -1;
                $coordinates['y1'] = 0;
                $coordinates['y2'] = $y;
            }
        }
        // colocar punto central del poligono (x,y)
        $coordinates['x'] = $coordinates['x1'] + (($coordinates['x2'] - $coordinates['x1']) / 2);
        $coordinates['y'] = $coordinates['y1'] + (($coordinates['y2'] - $coordinates['y1']) / 2);
        // devolver coordenadas
        return $coordinates;
    }

    /**
     * Genera un thumbnail de una imagen utilizando la/s cara/s detectada/s como "centro"
     * @param photo Ruta de la imagen original
     * @param w Ancho de la imagen a generar
     * @param h Alto de la imagen a generar
     * @return array Arreglo con la imagen con indices: name, type, size y data
     */
    public static function face_thumbnail($photo, $w, $h)
    {
        // obtener coordenadas de la/s cara/s
        $c = self::face_area($photo);
        // obtener archivo
        $file = Utility_File::get($photo);
        // si el archivo no ha podido ser leido se retorna la funcion
        if ($file == null) {
            return;
        }
        // si no existe un punto central detectado se coloca en el centro de la imagen
        // esto se hace pensando en que si no se detectaron caras se mostrara la parte central de la imagen
        if (!isset($c['x']) || !isset($c['y'])) {
            $c['x'] = $file['w'] / 2;
            $c['y'] = $file['h'] / 2;
        }
        // crear imagen para ser usada con GD
        $src = imagecreatefromstring($file['data']);
        // calcular imagen a recortar
        $ratio = $w/$h;
        if ($file['ratio'] > $ratio) {
            // determinar tamaño del cuadro de recorte
            $y1 = 0;
            $y2 = $file['h'];
            $x1 = 0;
            $x2 = $y2 * $ratio;
            // mover horizontalmente el cuadro de recorte
            $x = $x1 + ($x2 - $x1) / 2;
            $y = $y1 + ($y2 - $y1) / 2;
            // determinar si la imagen se debe correr a la derecha o a la izquierda (desde el punto central detectado $c['x'])
            $delta = $c['x'] - $x;
            if ($delta) { // si hay diferencia horizontal se debe mover la imagen
                // delta > 0 mover a la derecha (sumar delta sin salir del margen derecho)
                if ($delta > 0) {
                    $move = (($file['w']-$x2)<$delta)?($file['w']-$x2):$delta;
                    $x1 += $move;
                    $x2 += $move;
                    $x = $x1 + ($x2 - $x1) / 2;
                }
                // delta < 0 mover a la izquierda (restar delta sin salir del margen izquierdo)
                if ($delta < 0) {
                    $delta = $delta * (-1);
                    $move = ($x1 < $delta) ? $x1 : $delta;
                    $x1 -= $move;
                    $x2 -= $move;
                    $x = $x1 + ($x2 - $x1) / 2;
                }
            }
        } else {
            // determinar tamaño del cuadro de recorte
            $x1 = 0;
            $x2 = $file['w'];
            $y1 = 0;
            $y2 = $x2 / $ratio;
            // mover verticalmente el cuadro de recorte
            $x = $x1 + ($x2 - $x1) / 2;
            $y = $y1 + ($y2 - $y1) / 2;
            // determinar si la imagen se debe correr hacia arriba o hacia abajo (desde el punto central detectado $c['y'])
            $delta = $c['y'] - $y;
            if ($delta) { // si hay diferencia vertical se debe mover la imagen
                // delta > 0 mover hacia abajo (sumar delta sin salir del margen inferior)
                if ($delta > 0) {
                    $move = (($file['h'] - $y2) < $delta) ? ($file['h'] - $y2) : $delta;
                    $y1 += $move;
                    $y2 += $move;
                    $y = $y1 + ($y2 - $y1) / 2;
                }
                // delta < 0 mover hacia arriba (restar delta sin salir del margen superior)
                if ($delta < 0) {
                    $delta = $delta * (-1);
                    $move = ($y1 < $delta) ? $y1 : $delta;
                    $y1 -= $move;
                    $y2 -= $move;
                    $y = $y1 + ($y2 - $y1) / 2;
                }
            }
        }
        // copiar imagen recortada a nueva imagen (generar thumbnail)
        $dst = imagecreatetruecolor($w, $h);
        // mantener transparencia
        if ($file['type'] == 'image/png' || $file['type'] == 'image/gif') {
            imagecolortransparent($dst, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, 0, 0, $x1, $y1, $w, $h, $x2 - $x1, $y2 - $y1);
        // armar archivo a devolver
        $thumbnail['name'] = $file['name'];
        $thumbnail['type'] = $file['type'];
        ob_clean();
        ob_start();
        if ($thumbnail['type'] == 'image/jpeg') {
            imagejpeg($dst);
        } else if ($thumbnail['type'] == 'image/gif') {
            imagegif($dst);
        } else if ($thumbnail['type'] == 'image/png') {
            imagepng($dst);
        }
        $thumbnail['size'] = ob_get_length();
        $thumbnail['data'] = ob_get_contents();
        ob_clean();
        // eliminar imagenes gd
        imagedestroy($dst);
        // guardar coordenadas utilizadas para generar el thumbnail y su tamaño final mas ratio
        $thumbnail['w'] = (integer)$w;
        $thumbnail['h'] = (integer)$h;
        $thumbnail['ratio'] = $w / $h;
        $thumbnail['x1'] = (integer)$x1;
        $thumbnail['y1'] = (integer)$y1;
        $thumbnail['x2'] = (integer)$x2;
        $thumbnail['y2'] = (integer)$y2;
        // retornar thumbnail
        return $thumbnail;
    }

    public static function rotate($file, $degrees, $w = false, $h = false)
    {
        $src = imagecreatefromstring($file['data']);
        if ($src) {
            $dst = imagerotate($src, $degrees, 0);
            // armar archivo a devolver
            ob_clean();
            ob_start();
            if ($file['type'] == 'image/jpeg') {
                imagejpeg($dst);
            } else if ($file['type'] == 'image/gif') {
                imagegif($dst);
            } else if ($file['type'] == 'image/png') {
                imagepng($dst);
            }
            $file['size'] = ob_get_length();
            $file['data'] = ob_get_contents();
            ob_clean();
            // eliminar imagenes gd
            imagedestroy($src);
            imagedestroy($dst);
        }
        // retornar imagen rotada
        return $file;
    }

    public static function generateFile(&$file)
    {
        // generar imagen desde el arreglo
        $src = imagecreatefromstring($file['data']);
        $file['tmp_name'] = DIR_TMP.'/'.md5(date('U')).'-'.$file['name'];
        // guardar imagen en un archivo
        if ($file['type'] == 'image/jpeg') {
            imagejpeg($src, $file['tmp_name']);
        } else if ($file['type'] == 'image/gif') {
            imagegif($src, $file['tmp_name']);
        } else if ($file['type'] == 'image/png') {
            imagepng($src, $file['tmp_name']);
        }
    }

    /**
     * Método para cambiar el tamaño de una imagen y entregar los datos y tamaño
     * de la nueva imagen
     * @param file Archivo de la imagen en el sistema de archivos
     * @param dst_w Ancho de la nueva imagen
     * @param dst_h Alto de la nueva imagen
     * @return array Arreglo con índices: size y data
     */
    public static function resize($file, $dst_w, $dst_h)
    {
        // obtener imagen fuente
        list($src_w, $src_h) = getimagesize($file);
        $src = imagecreatefromstring(file_get_contents($file));
        // crear imagen destino
        $dst = imagecreatetruecolor($dst_w, $dst_h);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $dst_w, $dst_h, $transparent);
        // copiar imagen
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
        // guardar imagen
        ob_clean();
        ob_start();
        $ext = Utility_File::extension($file);
        if ($ext == 'jpg' || $ext == 'jpeg') {
            imagejpeg($dst);
        } else if ($ext == 'gif') {
            imagegif($dst);
        } else if ($ext == 'png') {
            imagepng($dst);
        }
        $image = [
            'size' => ob_get_length(),
            'data' => ob_get_contents(),
        ];
        ob_clean();
        // eliminar imagenes gd
        imagedestroy($src);
        imagedestroy($dst);
        return $image;
    }

    /**
     * Método para cambiar el tamaño de una imagen modificando el archivo de la
     * misma y entregar su nuevo ancho y alto
     * @param file Archivo de la imagen en el sistema de archivos
     * @param dst_w Ancho de la nueva imagen
     * @param dst_h Alto de la nueva imagen
     * @return array Arreglo con ancho y alto
     */
    public static function resizeOnFile($file, $dst_w, $dst_h)
    {
        // si la imagen es más pequeña que el nuevo tamaño no se modifica
        list($ancho, $alto) = getimagesize($file);
        if ($dst_w > $ancho && $dst_h > $alto) {
            return [$ancho, $alto];
        }
        // determinar el nuevo ancho y alto que tendra la imagen (manteniendo proporciones)
        $ratio = min($dst_w/$ancho, $dst_h/$alto);
        $new_w = round($ancho * $ratio);
        $new_h = round($alto * $ratio);
        // crear variable para la imagen segun el formato de la misma
        $mimetype = Utility_File::mimetype($file);
        if ($mimetype == 'image/png'){
            $src_img = imagecreatefrompng($file);
        } else if ($mimetype == 'image/jpeg') {
            $src_img = imagecreatefromjpeg($file);
        } else if ($mimetype == 'image/gif') {
            $src_img = imagecreatefromgif($file);
        }
        // generar imagen nueva
        $dst_img = imagecreatetruecolor($new_w, $new_h);
        // mantener transparencia
        if ($mimetype == 'image/png' || $mimetype == 'image/gif'){
            imagecolortransparent($dst_img, imagecolorallocatealpha($dst_img, 0, 0, 0, 127));
            imagealphablending($dst_img, false);
            imagesavealpha($dst_img, true);
        }
        // copiar imagen desde src_img a dst_img
        imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_w, $new_h, $ancho, $alto);
        // copiar imagen devuelta al archivo
        if ($mimetype == 'image/png') {
            imagepng($dst_img, $file, 9);
        } else if ($mimetype == 'image/jpeg') {
            imagejpeg($dst_img, $file, 100);
        } else if ($mimetype == 'image/gif') {
            imagegif($dst_img, $file);
        }
        // destruir imagenes usadas
        imagedestroy($src_img);
        imagedestroy($dst_img);
        // entregar nuevo ancho y alto
        return [$new_w, $new_h];
    }

    /**
     * Método que entrega un mapa de bits de la imagen
     */
    public static function bitmap($img) {
        $data = [];
        $w = imagesx($img);
        $h = imagesy($img);
        for($y = 0; $y < $h; $y++) {
            for($x = 0; $x < $w; $x++) {
                $data[] = (bool)imagecolorat($img, $x, $y);
            }
        }
        return [
            'data' => $data,
            'width' => $w,
            'height' => $h,
        ];
    }

}
