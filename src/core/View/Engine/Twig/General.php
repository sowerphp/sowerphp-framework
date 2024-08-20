<?php

/**
 * SowerPHP: Simple and Open Web Ecosystem Reimagined for PHP.
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

use \Twig\Extension\AbstractExtension;
use \Twig\TwigFilter;
use \Twig\TwigFunction;
use \Twig\Markup;
use \sowerphp\core\Facade_Session_Message as SessionMessage;

/**
 * Extensión general con funciones y filtros para Twig.
 */
class View_Engine_Twig_General extends AbstractExtension
{

    /**
     * Entrega los filtros de la extensión.
     *
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('num', 'num'),
        ];
    }

    /**
     * Entrega las funciones de la extensión.
     *
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('dd', 'dd'),
            new TwigFunction('project_path', [$this, 'function_project_path']),
            new TwigFunction('session_messages', [$this, 'function_session_messages']),
            new TwigFunction('explode', [$this, 'function_explode']),
            new TwigFunction('app_stats', [app(), 'getStats']),
        ];
    }

    /**
     * Entrega una ruta absoluta dentro del proyecto.
     *
     * @param string|null $path
     * @return string
     */
    public function function_project_path(?string $path = null): string
    {
        return app('layers')->getProjectPath($path);
    }

    /**
     * Entrega todos los mensajes de la sesión como un único string.
     *
     * @return Markup
     */
    public function function_session_messages(): Markup
    {
        return new Markup(SessionMessage::getMessagesAsString(), 'UTF-8');
    }

    /**
     * Emula la función explode de PHP en Twig.
     *
     * @param string $delimiter El delimitador.
     * @param string $string La cadena de texto a dividir.
     * @param int|null $index (Opcional) El índice del elemento a retornar.
     * @return array|string El array resultante de la división o un elemento
     * específico si se proporciona el índice.
     */
    public function function_explode(string $delimiter, string $string, int $index = null): ?string
    {
        $parts = explode($delimiter, $string);
        if ($index !== null) {
            return $parts[$index] ?? null;
        }
        return $parts;
    }

}
