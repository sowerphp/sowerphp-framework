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
 * Clase base para todas las excepciones
 */
class Exception extends \RuntimeException
{

    protected $_messageTemplate = '%s'; ///< Mensaje que se utilizará al renderizar el error
    protected $severity = LOG_ERR; // Error conditions (http://en.wikipedia.org/wiki/Syslog#Severity_levels)

    /**
     * Constructor para la excepción
     * @param message Un string con el error o bien un arreglo con atributos que son pasados al mensaje que se traducirá
     * @param code string Código del error (default: 400)
     */
    public function __construct($message, $code = 400)
    {
        // si es un arreglo se utilizará junto a sprintf
        if (is_array($message)) {
            $message = vsprintf($this->_messageTemplate, $message);
        }
        // llamar al constructor con el error y el mensaje
        parent::__construct($message, $code);
    }

}
