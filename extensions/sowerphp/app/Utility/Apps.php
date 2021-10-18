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
 * Utilidad general para trabajar con las aplicaciones que se pueden integrar a
 * la aplicación
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2019-06-12
 */
class Utility_Apps
{

    protected $config; ///< Configuración principal de las aplicaciones

    /**
     * Constructor de la clase que procesa las aplicaciones de terceros
     * disponibles en la aplicación web
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-07-01
     */
    public function __construct($config = [])
    {
        if (!$config) {
            $config = [
                'directory' => DIR_WEBSITE.'/Utility/Apps',
                'namespace' => '\website',
            ];
        }
        $this->config = (array)$config;
        if (!isset($this->config[0])) {
            $this->config = [$this->config];
        }
    }

    /**
     * Método que entrega una sóla aplicación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-06-14
     */
    public function getApp($app)
    {
        $apps = $this->getApps(['apps'=>[$app]]);
        return $apps ? $apps[$app] : false;
    }

    /**
     * Método que entrega todas los aplicaciones disponibles
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-07-01
     */
    public function getApps(array $filtros = [])
    {
        // definir filtros por defecto por si no existen
        $filtros = array_merge([
            'apps' => [],
            'activas' => true,
        ], $filtros);
        // realizar búsqueda de aplicaciones
        $apps = [];
        foreach ($this->config as $config) {
            // si el directorio no existe se entrega vacio el arreglo de aplicaciones
            // se falla silenciosamente, para no tener errores en la aplicación web
            if (empty($config['directory']) or !is_dir($config['directory'])) {
                continue;
            }
            $dir = opendir($config['directory']);
            while (($archivo = readdir($dir)) !== false) {
                if ($archivo[0] == '.' or is_dir($config['directory'].'/'.$archivo)) {
                    continue;
                }
                $class = substr($archivo,0,-4);
                $app = \sowerphp\core\Utility_Inflector::underscore($class);
                if ($filtros['apps'] and !in_array($app, $filtros['apps'])) {
                    continue;
                }
                $directory = $config['directory'].'/'.$class;
                $class = $config['namespace'].'\Utility_Apps_'.$class;
                if (!class_exists($class)) {
                    continue;
                }
                $App = new $class($directory);
                if ($filtros['activas'] and !$App->getActiva()) {
                    continue;
                }
                $apps[$app] = $App;
            }
            closedir($dir);
        }
        // ordenar apps
        if ($apps) {
            uasort($apps, function($app1, $app2) {
                return $app1->getNombre() == $app2->getNombre() ? 0 : ( $app1->getNombre() < $app2->getNombre() ? -1 : 1);
            });
        }
        // entregar apps como objetos
        return $apps;
    }

}
