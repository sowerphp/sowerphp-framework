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
 * Excepción personalizada para manejar errores de PHP.
 */
class Exception_Error extends Exception
{
    /**
     * Constructor para la excepción de error.
     *
     * @param string $message Mensaje de error.
     * @param int $severity Nivel del error.
     * @param string $file Archivo donde ocurrió el error.
     * @param int $line Línea donde ocurrió el error.
     * @param int $code Código de error (opcional, por defecto 500).
     */
    public function __construct($message, $severity, $file, $line, $code = 500)
    {
        $this->severity = $severity;
        $this->file = $file;
        $this->line = $line;

        // Crear un mensaje detallado
        $formattedMessage = sprintf(
            "[%s] Error: %s in %s on line %d",
            $this->getSeverityLevel($severity),
            $message,
            $file,
            $line
        );

        parent::__construct($formattedMessage, $code);
    }

    /**
     * Obtiene la representación en texto del nivel de severidad.
     *
     * @param int $severity Nivel de severidad del error.
     * @return string Representación en texto del nivel de severidad.
     */
    private function getSeverityLevel($severity)
    {
        $levels = [
            E_ERROR             => 'Error',
            E_WARNING           => 'Warning',
            E_PARSE             => 'Parsing Error',
            E_NOTICE            => 'Notice',
            E_CORE_ERROR        => 'Core Error',
            E_CORE_WARNING      => 'Core Warning',
            E_COMPILE_ERROR     => 'Compile Error',
            E_COMPILE_WARNING   => 'Compile Warning',
            E_USER_ERROR        => 'User Error',
            E_USER_WARNING      => 'User Warning',
            E_USER_NOTICE       => 'User Notice',
            E_STRICT            => 'Strict',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED        => 'Deprecated',
            E_USER_DEPRECATED   => 'User Deprecated',
        ];

        return $levels[$severity] ?? 'Unknown severity';
    }
}
