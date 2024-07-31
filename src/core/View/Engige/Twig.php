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

use \Twig\Environment;
use \Twig\Loader\FilesystemLoader;
use \Twig\TwigFunction;
use \Twig\TwigFilter;

/**
 * Motor de renderizado de plantilla HTML utilizando Twig.
 */
class View_Engine_Twig extends View_Engine
{

    /**
     * Posibles rutas para la búsqueda de vistas.
     *
     * Se configurará con las rutas de cada capa. Por lo que las vistas se
     * buscarán en cada una de las capas de la aplicación desde su base.
     *
     * @var array
     */
    protected $viewPaths;

    /**
     * Ruta, dentro del directorio de almacenamiento, para el caché de las
     * vistas ya procesadas de Twig.
     *
     * @var string
     */
    protected $cachePath = 'framework/views/twig';

    /**
     * Extensiones por defecto que serán cargadas si no están definidas en la
     * configuración de twig.
     *
     * @var array
     */
    protected $extensions = [
        View_Engine_Twig_General::class,
        View_Engine_Twig_Form::class,
    ];

    /**
     * Instancia del objeto de Twig para el renderizado.
     *
     * @var \Twig\Environment
     */
    protected $twig;

    /**
     * Inicialización de Twig.
     *
     * @return void
     */
    protected function boot(): void
    {
        // Crear el FilesystemLoader con los posibles directorios para las
        // vistas.
        $this->viewPaths = array_values($this->layersService->getPaths());
        $loader = new FilesystemLoader($this->viewPaths);
        // Definir el caché en el directorio estándar si es producción.
        $config = [];
        if (config('app.env') != 'local') {
            $config['cache'] = storage_path($this->cachePath);
        }
        // Crear el entorno de Twig con el cargador y configuración.
        $this->twig = new Environment($loader, $config);
        // Cargar las extensiones que estarán disponibles en las plantillas.
        $extensions = config('app.ui.twig.extensions', $this->extensions);
        foreach ($extensions as $extension) {
            $this->twig->addExtension(new $extension());
        }
    }

    /**
     * Renderizar una plantilla Twig y devolver el resultado como una cadena.
     *
     * @param string $filepath Ruta a la plantilla Twig que se va a renderizar.
     * @param array $data Datos que se pasarán a la plantilla Twig para su uso
     * dentro de la vista.
     * @return string El contenido HTML generado por la plantilla Twig.
     */
    public function render(string $filepath, array $data): string
    {
        // Convertir la ruta absoluta a relativa.
        $relativePath = substr(str_replace($this->viewPaths, '', $filepath), 1);
        // Renderizar la plantilla.
        return $this->twig->render($relativePath, $data);
    }

    /**
     * Registrar una función en el motor de renderizado Twig.
     *
     * Permite registrar una función mediante:
     *   - Función anónima.
     *   - Función.
     *   - Método de una clase.
     *
     * Ejemplos de uso de una función:
     *   {{ greet() }}
     *   {{ greet('Alice') }}
     *   {{ greet('Bob', 'Hi') }}
     *
     * @param string $name Nombre de la función que estará disponible en Twig.
     * @param Closure|string|array $function Función o llamada a la misma.
     * @return void
     */
    public function addFunction(string $name, $function): void
    {
        $this->twig->addFunction(
            new TwigFunction($name, $function)
        );
    }

    /**
     * Registrar un filtro en el motor de renderizado Twig.
     *
     * Permite registrar un filtro mediante:
     *   - Función anónima.
     *   - Función.
     *   - Método de una clase.
     *
     * Ejemplos de uso de un filtro:
     *   {{ 'text' | my_filter('prefix_', '_suffix') }}
     *   {{ 'text' | my_filter('prefix_') }}
     *   {{ 'text' | my_filter }}
     *
     * @param string $name Nombre del filtro que estará disponible en Twig.
     * @param Closure|string|array $filter Función o llamada a la misma.
     * @return void
     */
    public function addFilter($name, $filter): void
    {
        $this->twig->addFilter(
            new TwigFilter($name, $filter)
        );
    }

    /**
     * Cualquier método que no esté definido en el motor de renderizado será
     * llamado en la instancia de Twig.
     *
     * Ejemplos de métodos de la instancia de Twig que se usarán:
     *   - addExtension()
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->twig, $method], $parameters);
    }

}
