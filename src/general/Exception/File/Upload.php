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
 * Excepción que se lanza con los códigos de error al subir un archivo mediante formulario
 */
class Exception_File_Upload extends \Exception {

    public function __construct($code)
    {
        $message = $this->codeToMessage($code);
        parent::__construct($message, $code);
    }

    private function codeToMessage($code)
    {
        switch ($code) {
            case \UPLOAD_ERR_INI_SIZE: {
                $message = __('El archivo excede el tamaño máximo permitido por el servidor (opción: upload_max_filesize).');
                break;
            }
            case \UPLOAD_ERR_FORM_SIZE: {
                $message = __('El archivo excede el tamaño máximo permitido por el formulario (opción: MAX_FILE_SIZE).');
                break;
            }
            case \UPLOAD_ERR_PARTIAL: {
                $message = __('El archivo pudo ser subido solo parcialmente.');
                break;
            }
            case \UPLOAD_ERR_NO_FILE: {
                $message = __('No se subió el archivo.');
                break;
            }
            case \UPLOAD_ERR_NO_TMP_DIR: {
                $message = __('No fue posible encontrar una carpeta temporal para subir el archivo en el servidor.');
                break;
            }
            case \UPLOAD_ERR_CANT_WRITE: {
                $message = __('Ocurrió un problema al tratar de guardar el archivo en el sistema de archivos del servidor.');
                break;
            }
            case \UPLOAD_ERR_EXTENSION: {
                $message = __('La subida del archivo fue detenida por una extensión de PHP en uso.');
                break;
            }
            default: {
                $message = __('Ocurrió un error desconocido al subir el archivo.');
                break;
            }
        }
        return $message;
    }

}
