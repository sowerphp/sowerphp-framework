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
 * Clase base para todas las excepciones
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2015-04-24
 */
class Exception extends \RuntimeException
{

    protected $_messageTemplate = '%s'; ///< Mensaje que se utilizará al renderizar el error
    protected $severity = LOG_ERR; // Error conditions (http://en.wikipedia.org/wiki/Syslog#Severity_levels)

    /**
     * Constructor para la excepción
     * @param message Un string con el error o bien un arreglo con atributos que son pasados al mensaje que se traducirá
     * @param code string Código del error (default: 500)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2012-10-27
     */
    public function __construct ($message, $code = 500)
    {
        // si es un arreglo se utilizará junto a sprintf
        if (is_array($message)) {
            $message = vsprintf($this->_messageTemplate, $message);
        }
        // llamar al constructor con el error y el mensaje
        parent::__construct($message, $code);
    }

    /**
     * Método para manejar las excepciones ocurridas en la aplicación
     * @param exception Excepción producida
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-05-06
     */
    public static function handler (\Exception $exception) {
        ob_clean();
        // Generar arreglo
        $data = array(
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'code' => $exception->getCode(),
            'severity' => isset($exception->severity) ? $exception->severity : LOG_ERR,
        );
        // renderizar dependiendo de si es una web o es una shell
        if (isset($_SERVER['REQUEST_URI'])) {
            $controller = new Controller_Error (new Network_Request(), new Network_Response());
            $controller->error_reporting = Configure::read('debug');
            $controller->display($data);
            $controller->shutdownProcess();
            $controller->response->status($data['code']);
            $controller->response->send();
        } else {
            $stdout = new Shell_Output('php://stdout');
            $stdout->write("\n".'<error>'.$data['exception'].':</error>', 2);
            $stdout->write("\t".'<error>'.str_replace("\n", "\n\t", $data['message']).'</error>', 2);
            $stdout->write("\t".'<error>'.str_replace("\n", "\n\t", $data['trace']).'</error>', 2);
        }
    }

}
