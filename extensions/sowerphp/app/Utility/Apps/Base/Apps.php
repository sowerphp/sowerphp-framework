<?php

/**
 * SowerPHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
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

// namespace del modelo
namespace sowerphp\app;

/**
 * Clase base para las implementaciones de clases de las apps de terceros que
 * se pueden ejecutar en la aplicación
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2019-06-27
 */
abstract class Utility_Apps_Base_Apps
{

    protected $nombre = null; ///< Nombre de la aplicación
    protected $activa = false; ///< Indica si la aplicación está activa (disponible para ser usada en la aplicación web)
    protected $config; ///< Configuración de la aplicación
    protected $vars = []; ///< Variables usadas por la aplicación pero que no son configurables por el usuario
    protected $directory; ///< Directorio de archivos de la aplicación

    /**
     * Constructor de la aplicación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-06-12
     */
    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    /**
     * Método que entrega el nombre de la app si se usa como string el objeto
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-06-12
     */
    public function __toString()
    {
        return $this->getNombre();
    }

    /**
     * Método que entrega el namespace de la aplicación que se instanció
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-06-27
     */
    public function getNamespace()
    {
        $class = '\Utility_Apps_'.\sowerphp\core\Utility_Inflector::camelize($this->getCodigo());
        return str_replace($class, '', get_class($this));
    }

    /**
     * Método que indica si la app está o no activa
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2020-09-03
     */
    public function getActiva()
    {
        return $this->activa;
    }

    /**
     * Método que entrega el código de la app
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-12-01
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
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-12-01
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
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-12-01
     */
    public function getDescripcion()
    {
        return !empty($this->descripcion) ? $this->descripcion : null;
    }

    /**
     * Método que entrega la URL de la APP
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-12-01
     */
    public function getURL($clean = false)
    {
        return !empty($this->url) ? ($clean ? str_replace(['http://', 'https://'], '', $this->url) : $this->url) : null;
    }

    /**
     * Método que entrega el logo de la APP
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-12-01
     */
    public function getLogo()
    {
        return !empty($this->logo) ? $this->logo : null;
    }

    /**
     * Método que entrega el código HTML de la página de configuración de la aplicación
     * @param form Objeto con el formulario que se está usando para construir la página de configuración
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-06-16
     */
    public function getConfigPageHTML(\sowerphp\general\View_Helper_Form $form)
    {
        $buffer = '';
        $buffer .= $form->input([
            'type' => 'select',
            'name' => 'app_'.$this->getCodigo().'_disponible',
            'label' => '¿Disponible?',
            'options' => ['No', 'Si'],
            'value' => (int)(!empty($this->getConfig()->disponible)),
            'help' => '¿Está disponible esta aplicación?',
        ]);
        return $buffer;
    }

    /**
     * Método que asigna la configuración de la aplicación procesando el
     * formulario enviado por POST
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-06-13
     */
    public function setConfigPOST()
    {
        $_POST['config_apps_'.$this->getCodigo()] = [
            'disponible' => (int)!empty($_POST['app_'.$this->getCodigo().'_disponible']),
        ];
        unset($_POST['app_'.$this->getCodigo().'_disponible']);
    }

    /**
     * Método que obtiene la configuración de la aplicación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-06-14
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Método que asigna la configuración de la aplicación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-06-14
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
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-06-15
     */
    public function getVar($var)
    {
        if (!isset($this->vars[$var])) {
            throw new \Exception('Variable "'.$var.'" de la aplicación "'.$this->getCodigo().'" no se encuentra asignada');
        }
        return $this->vars[$var];
    }

    /**
     * Método que asigna las variables de la aplicación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-06-15
     */
    public function setVars(array $vars)
    {
        $this->vars = array_merge($this->vars, $vars);
    }

    /**
     * Método que entrega el código de la aplicación de alguna parte de la página
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-06-12
     */
    public function getPageCode($page, array $vars = [])
    {
        $plantilla = $this->directory.'/templates/'.$page.'.php';
        if (!is_readable($plantilla)) {
            return '';
        }
        if (!empty($this->config)) {
            $vars['config'] = $this->config;
        }
        return \sowerphp\core\View_Helper_Pages_Php::render($plantilla, $vars);
    }

    /**
     * Método que redirecciona una URL en la aplicación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-07-17
     */
    protected function redirect($url) {
        header('location: '.$url);
        exit(0); // no hay otra opción, debe ser exit para que location funcione
    }

}
