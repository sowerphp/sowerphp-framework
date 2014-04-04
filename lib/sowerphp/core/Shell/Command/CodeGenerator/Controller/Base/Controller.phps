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

// namespace del controlador
namespace {namespace};

/**
 * Clase abstracta para el controlador asociado a la tabla {table} de la base
 * de datos
 * Comentario de la tabla: {comment}
 * Esta clase permite controlar las acciones básicas entre el modelo y vista
 * para la tabla {table}, o sea implementa métodos CRUD
 * @author {author}
 * @version {version}
 */
abstract class Controller_Base_{classs} extends \Controller_App
{

    /**
     * Controlador para listar los registros de tipo {class}
     * @author {author}
     * @version {version}
     */
    public function listar ($page = 1, $orderby = null, $order = 'A')
    {
        // crear objeto
        ${classs} = new Model_{classs}();
        // si se debe buscar se agrega filtro
        $searchUrl = null;
        $search = array();
        if (!empty($_GET['search'])) {
            $searchUrl = '?search='.$_GET['search'];
            $filters = explode(',', $_GET['search']);
            $where = array();
            foreach ($filters as &$filter) {
                list($var, $val) = explode(':', $filter);
                $search[$var] = $val;
                // dependiendo del tipo de datos se ve como filtrar
                if (in_array(Model_{class}::$columnsInfo[$var]['type'], array('char', 'character varying')))
                    $where[] = ${classs}->like($var, $val);
                else
                    $where[] = ${classs}->sanitize($var)." = '".${classs}->sanitize($val)."'";
            }
            // agregar condicion a la busqueda
            ${classs}->setWhereStatement(implode(' AND ', $where));
        }
        // si se debe ordenar se agrega
        if ($orderby) {
            ${classs}->setOrderByStatement($orderby.' '.($order=='D'?'DESC':'ASC'));
        }
        // total de registros
        $registers_total = ${classs}->count();
        // paginar si es necesario
        if ((integer)$page>0) {
            $registers_per_page = \sowerphp\core\Configure::read('app.registers_per_page');
            $pages = ceil($registers_total/$registers_per_page);
            ${classs}->setLimitStatement($registers_per_page, ($page-1)*$registers_per_page);
            if ($page != 1 && $page > $pages) {
                $this->redirect(
                    $this->module_url.'{controller}/listar/1'.($orderby ? '/'.$orderby.'/'.$order : '').$searchUrl
                );
            }
        }
        // setear variables
        $this->set(array(
            'module_url' => $this->module_url,
            'controller' => $this->request->params['controller'],
            'page' => $page,
            'orderby' => $orderby,
            'order' => $order,
            'searchUrl' => $searchUrl,
            'search' => $search,
            '{classs}' => ${classs}->getObjects(),
            'columnsInfo' => Model_{class}::$columnsInfo,
            'registers_total' => $registers_total,
            'pages' => isset($pages) ? $pages : 0,
            'linkEnd' => ($orderby ? '/'.$orderby.'/'.$order : '').$searchUrl,
            'fkNamespace' => Model_{class}::$fkNamespace,
        ));
    }
    
    /**
     * Controlador para crear un registro de tipo {class}
     * @author {author}
     * @version {version}
     */
    public function crear ()
    {
        // si se envió el formulario se procesa
        if (isset($_POST['submit'])) {
            ${class} = new Model_{class}();
            ${class}->set($_POST);
            ${class}->save();
            \sowerphp\core\Model_Datasource_Session::message(
                'Registro {class} creado'
            );
            $this->redirect(
                $this->module_url.'{controller}/listar'
            );
        }
        // setear variables
        $this->set(array(
            'columnsInfo' => Model_{class}::$columnsInfo,
            'fkNamespace' => Model_{class}::$fkNamespace,
            'accion' => 'Crear',
        ));
        // renderizar
        $this->autoRender = false;
        $this->render('{classs}/crear_editar');
    }
    
    /**
     * Controlador para editar un registro de tipo {class}
     * @author {author}
     * @version {version}
     */
    public function editar ({pk_parameter})
    {
        ${class} = new Model_{class}({pk_parameter});
        // si el registro que se quiere editar no existe error
        if(!${class}->exists()) {
            \sowerphp\core\Model_Datasource_Session::message(
                'Registro {class}('.implode(', ', func_get_args()).') no existe, no se puede editar'
            );
            $this->redirect(
                $this->module_url.'{controller}/listar'
            );
        }
        // si no se ha enviado el formulario se mostrará
        if(!isset($_POST['submit'])) {
            $this->set(array(
                '{class}' => ${class},
                'columnsInfo' => Model_{class}::$columnsInfo,
                'fkNamespace' => Model_{class}::$fkNamespace,
                'accion' => 'Editar',
            ));
            // renderizar
            $this->autoRender = false;
            $this->render('{classs}/crear_editar');
        }
        // si se envió el formulario se procesa
        else {
            ${class}->set($_POST);
            ${class}->save();
            if(method_exists($this, 'u')) {
                $this->u({pk_parameter});
            }
            \sowerphp\core\Model_Datasource_Session::message(
                'Registro {class}('.implode(', ', func_get_args()).') editado'
            );
            $this->redirect(
                $this->module_url.'{controller}/listar'
            );
        }
    }

    /**
     * Controlador para eliminar un registro de tipo {class}
     * @author {author}
     * @version {version}
     */
    public function eliminar ({pk_parameter})
    {
        ${class} = new Model_{class}({pk_parameter});
        // si el registro que se quiere eliminar no existe error
        if(!${class}->exists()) {
            \sowerphp\core\Model_Datasource_Session::message(
                'Registro {class}('.implode(', ', func_get_args()).') no existe, no se puede eliminar'
            );
            $this->redirect(
                $this->module_url.'{controller}/listar'
            );
        }
        ${class}->delete();
        \sowerphp\core\Model_Datasource_Session::message(
            'Registro {class}('.implode(', ', func_get_args()).') eliminado'
        );
        $this->redirect($this->module_url.'{controller}/listar');
    }

{methods_ud}

}
