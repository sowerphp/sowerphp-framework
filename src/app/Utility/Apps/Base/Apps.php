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

namespace sowerphp\app;

/**
 * Clase base para las implementaciones de clases de las apps de terceros que
 * se pueden ejecutar en la aplicación
 */
abstract class Utility_Apps_Base_Apps
{

    protected $nombre = null; ///< Nombre de la aplicación
    protected $activa = false; ///< Indica si la aplicación está activa (disponible para ser usada en la aplicación web)
    protected $config; ///< Configuración de la aplicación
    protected $vars = []; ///< Variables usadas por la aplicación pero que no son configurables por el usuario
    protected $directory; ///< Directorio de archivos de la aplicación
    protected $namespace = 'apps'; ///< nombre del grupo de las aplicaciones que heredan esta clase

    /**
     * Constructor de la aplicación
     */
    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    /**
     * Método que entrega el nombre de la app si se usa como string el objeto
     */
    public function __toString()
    {
        return $this->getNombre();
    }

    /**
     * Método que entrega el ID de la aplicación en base a su namespace de PHP y su código
     */
    public function getID()
    {
        return strtolower(str_replace('\\', '_', $this->getNamespacePHP()).'_app_'.$this->getCodigo());
    }

    /**
     * Método que entrega el namespace de la aplicación (grupo de la app, no el de PHP)
     */
    protected function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Método que entrega el namespace de la aplicación que se instanció
     */
    protected function getNamespacePHP()
    {
        $class = '\sowerphp\autoload\Utility_Apps_'.\sowerphp\core\Utility_Inflector::camelize($this->getCodigo());
        return str_replace($class, '', get_class($this));
    }

    /**
     * Método que entrega el prefijo de la configuración
     */
    protected function getConfigName()
    {
        return 'config_'.$this->getNamespace().'_'.$this->getCodigo();
    }

    /**
     * Método que indica si la app está o no activa
     */
    public function getActiva()
    {
        return $this->activa;
    }

    /**
     * Método que entrega el código de la app
     */
    public function getCodigo()
    {
        if (!isset($this->codigo)) {
            $this->codigo = \sowerphp\core\Utility_Inflector::underscore(
                explode('Utility_Apps_', get_class($this))[1]
            );
        }
        return $this->codigo;
    }

    /**
     * Método que entrega el nombre de la app
     */
    public function getNombre()
    {
        if ($this->nombre===null) {
            $this->nombre = \sowerphp\core\Utility_Inflector::humanize(
                \sowerphp\core\Utility_Inflector::underscore(
                    explode('Utility_Apps_', get_class($this))[1]
                )
            );
        }
        return $this->nombre;
    }

    /**
     * Método que entrega la descripción de la app
     */
    public function getDescripcion()
    {
        return !empty($this->descripcion) ? $this->descripcion : null;
    }

    /**
     * Método que entrega la URL de la APP
     */
    public function getURL($clean = false)
    {
        return !empty($this->url) ? ($clean ? str_replace(['http://', 'https://'], '', $this->url) : $this->url) : null;
    }

    /**
     * Método que entrega el logo de la APP
     */
    public function getLogo()
    {
        return !empty($this->logo) ? $this->logo : null;
    }

    /**
     * Método que entrega el icono de la APP
     */
    public function getIcon()
    {
        return !empty($this->icon) ? $this->icon : 'fas fa-cubes';
    }

    /**
     * Método que entrega el código HTML de la página de configuración de la aplicación
     * @param form Objeto con el formulario que se está usando para construir la página de configuración
     */
    public function getConfigPageHTML(\sowerphp\general\View_Helper_Form $form): string
    {
        $prefix = 'app_' . $this->getCodigo();
        $buffer = '';
        $buffer .= $form->input([
            'type' => 'select',
            'name' => $prefix . '_disponible',
            'label' => '¿Disponible?',
            'options' => ['No', 'Si'],
            'value' => (int)(!empty($this->getConfig()->disponible)),
            'help' => '¿Está disponible esta aplicación?',
        ]);
        return $buffer;
    }

    /**
     * Método que asigna la configuración de la aplicación procesando el
     * formulario enviado por POST.
     *
     * @return array|null Arreglo con la configuración determinada.
     */
    public function setConfigPOST(): ?array
    {
        // Asignar configuración.
        $prefix = 'app_' . $this->getCodigo();
        $configName = 'config_apps_' . $this->getCodigo();

        // Asignar configuración.
        $_POST[$configName] = [
            'disponible' => (int)!empty($_POST[$prefix . '_disponible']),
        ];

        // Limpiar $_POST.
        unset($_POST[$prefix . '_disponible']);

        // Entregar la configuración.
        return $_POST[$configName];
    }

    /**
     * Método que obtiene la configuración de la aplicación
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Método que asigna la configuración de la aplicación al objeto (no guarda)
     */
    public function setConfig($config)
    {
        $this->config = (object)$config;
        if (!isset($this->config->disponible)) {
            $this->config->disponible = 0;
        }
    }

    /**
     * Método que entrega el valor de una variable de la app si existe
     */
    public function getVar($var)
    {
        if (!isset($this->vars[$var])) {
            throw new \Exception(
                'Variable "'.$var.'" de la aplicación "'.$this->getCodigo().'" no se encuentra asignada'
            );
        }
        return $this->vars[$var];
    }

    /**
     * Método que asigna las variables de la aplicación.
     */
    public function setVars(array $vars)
    {
        $this->vars = array_merge($this->vars, $vars);
    }

    /**
     * Método que entrega el código de la aplicación de alguna parte de la
     * página.
     */
    public function getPageCode(string $page, array $vars = []): string
    {
        $plantilla = $this->directory . '/templates/' . $page . '.php';
        if (!is_readable($plantilla)) {
            return '';
        }
        $vars = array_merge(['__view_layout' => false], $this->vars, $vars);
        if (!empty($this->config)) {
            $vars['config'] = $this->config;
        }
        return app('view')->render($plantilla, $vars);
    }

}
