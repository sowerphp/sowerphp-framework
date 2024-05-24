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

namespace sowerphp\general;

/**
 * Controlador para módulos
 */
class Controller_Module extends \Controller_App
{

    /**
     * Método para autorizar la carga de index en caso que hay autenticación
     */
    public function beforeFilter()
    {
        if (isset($this->Auth)) {
            $this->Auth->allow('index');
        }
        parent::beforeFilter();
    }

    /**
     * Renderizará (sin autenticación) el archivo en View/index
     */
    public function index()
    {
        if ($this->autoRender) {
            $this->autoRender = false;
            $this->render('index');
        }
    }

    /**
     * Mostrar la página principal para el módulo (con sus opciones de menú)
     */
    public function display()
    {
        if (!$this->autoRender) {
            return;
        }
        // desactivar renderizado automático
        $this->autoRender = false;
        // Si existe una vista para el del modulo se usa
        if (app('module')->getFilePath($this->request->params['module'], '/View/display.php')) {
            $this->render('display');
        }
        // Si no se incluye el archivo con el título y el menú para el módulo
        else {
            // menú del módulo
            $nav_module = (array)config('nav.module');
            // nombre del módulo para url
            $module = str_replace(
                '.',
                '/',
                \sowerphp\core\Utility_Inflector::underscore(
                    $this->request->params['module']
                )
            );
            // verificar permisos
            foreach ($nav_module as $link => &$info) {
                // si info no es un arreglo es solo el nombre y se arma
                if (!is_array($info)) {
                    $info = array(
                        'name' => $info,
                        'desc' => '',
                        'icon' => 'fa-solid fa-link',
                    );
                }
                // si es un arreglo colocar opciones por defecto
                else {
                    $info = array_merge(array(
                        'name' => $link,
                        'desc' => '',
                        'icon' => 'fa-solid fa-link',
                    ), $info);
                }
                // Verificar permisos para acceder al enlace
                if(!$this->Auth->check('/'.$module.$link)) {
                    unset($nav_module[$link]);
                }
            }
            // setear variables para la vista
            $module = str_replace(
                '.',
                '/',
                \sowerphp\core\Utility_Inflector::underscore(
                    $this->request->params['module']
                )
            );
            $title = config('module.title');
            if (!$title) {
                $title = str_replace (
                    '.',
                    ' &raquo; ',
                    $this->request->params['module']
                );
            }
            $this->set(array(
                'title' => $title,
                'nav' => $nav_module,
                'module' => $module,
            ));
            unset($title, $nav_module, $module);
            // renderizar
            $this->render('Module/index');
        }
    }

}
