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
 * Controlador para módulos.
 */
class Controller_Module extends \Controller_App
{

    /**
     * Método para autorizar la carga de index en caso que hay autenticación.
     */
    public function boot(): void
    {
        if (isset($this->Auth)) {
            $this->Auth->allow('index');
        }
        parent::boot();
    }

    /**
     * Renderizará (sin autenticación) el archivo en View/index.
     */
    public function index()
    {
        return $this->render('index');
    }

    /**
     * Mostrar la página principal para el módulo (con sus opciones de menú).
     */
    public function display()
    {
        $module = $this->request->getRouteConfig()['module'];
        // Si existe una vista para el del modulo se usa.
        if (app('module')->getFilePath($module, '/View/display.php')) {
            return $this->render('display');
        }
        // Si no se incluye el archivo con el título y el menú para el módulo.
        else {
            // Menú del módulo.
            $module_nav = (array)config('modules.' . $module . '.nav');
            // Nombre del módulo para URL.
            $module_url = str_replace(
                '.',
                '/',
                \sowerphp\core\Utility_Inflector::underscore($module)
            );
            // Verificar permisos.
            foreach ($module_nav as $link => &$info) {
                // Si info no es un arreglo es solo el nombre y se arma.
                if (!is_array($info)) {
                    $info = [
                        'name' => $info,
                        'desc' => '',
                        'icon' => 'fa-solid fa-link',
                    ];
                }
                // Si es un arreglo colocar opciones por defecto.
                else {
                    $info = array_merge([
                        'name' => $link,
                        'desc' => '',
                        'icon' => 'fa-solid fa-link',
                    ], $info);
                }
                // Verificar permisos para acceder al enlace.
                if(!$this->Auth->check('/' . $module_url . $link)) {
                    unset($nav_module[$link]);
                }
            }
            // Asignar variables para la vista.
            $this->set([
                'title' => config('modules.' . $module . '.title')
                    ?? str_replace ('.', ' &raquo; ', $module)
                ,
                'nav' => $module_nav,
                'module' => $module_url,
            ]);
            // Renderizar la vista.
            return $this->render('Module/index');
        }
    }

}
