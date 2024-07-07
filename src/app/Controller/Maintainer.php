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

use \sowerphp\core\Facade_Session_Message as SessionMessage;

/**
 * Clase que implementa los métodos básicos de un mantenedor de una tabla de la
 * base de datos.
 *
 * Implementa los métodos del CRUD: create, read, update y delete.
 */
class Controller_Maintainer extends \Controller
{

    protected $model = false; ///< Atributo con el namespace y clase del modelo singular
    protected $models = false; ///< Atributo con el namespace y clase del modelo plural
    protected $module_url; ///< Atributo con la url para acceder el módulo
    protected $deleteRecord = true; ///< Indica si se permite o no borrar registros
    protected $contraseniaNames = ['contrasenia', 'clave', 'password', 'pass']; ///< Posibles nombres de campo tipo contraseña
    protected $actionsColsWidth = 170; ///< Ancho de la columna de acciónes en acción listar
    protected $extraActions = []; ///< iconos extra para la columna de acciones

    /**
     * Constructor del controlador.
     */
    public function __construct(
        \sowerphp\core\Network_Request $request,
        \sowerphp\core\Network_Response $response
    )
    {
        parent::__construct($request, $response);
        $this->setModelName();
        $this->module_url = $this->setModuleUrl(
            $this->request->getRouteConfig()['module']
        );
    }

    /**
     * Método que asigna los namespaces y nombres de los modelos tanto singular
     * como plural usados por este controlador.
     */
    private function setModelName()
    {
        if (!$this->models) {
            $this->models = \sowerphp\core\Utility_Inflector::camelize(
                $this->request->getRouteConfig()['controller']
            );
        }
        if (!$this->model) {
            $this->model = \sowerphp\core\Utility_Inflector::singularize(
                $this->models
            );
        }
        $this->set('models', $this->models);
        $this->set('model', $this->model);
        $this->model = '\\' . $this->namespace . '\Model_' . $this->model;
        $this->models = '\\' . $this->namespace . '\Model_' . $this->models;
    }

    /**
     * Método que asigna la url del módulo que se usa en el controlador.
     *
     * @param string $modulo Nombre del módulo donde se generarán los archivos.
     * @return string URL que se usa para acceder al módulo.
     */
    private function setModuleUrl(string $modulo = ''): string
    {
        $partes = explode('.', $modulo);
        $module_url = '';
        foreach ($partes as &$p) {
            $module_url .= \sowerphp\core\Utility_Inflector::underscore($p) . '/';
        }
        return $module_url != '/' ? ('/' . $module_url) : $module_url;
    }

    /**
     * Método que busca la vista que se deberá renderizar.
     *
     * @param string $view Vista que se desea renderizar.
     * @param array $data Variables que se pasarán a la vista al renderizar.
     */
    public function render(
        ?string $view = null,
        array $data = []
    ): \sowerphp\core\Network_Response
    {
        // Se debe determinar la vista automáticamente pues no fue indicada una
        // específica. Se renderizará la vista del Controller::action().
        if (!$view) {
            return parent::render($view, $data);
        }
        // Si la vista tiene '/' es porque se pidió una específica. Ya sea
        // mediante ruta absoluta o relativa y se deberá buscar esa.
        if (strpos($view, '/') !== false) {
            return parent::render($view, $data);
        }
        // Se indicó solo el nombre de la vista y no su ubicación (ni ruta
        // absoluta ni relativa). Se deberá determinar su ubicación.
        list($namespace, $ControllerName) = explode(
            '\Controller_',
            get_class($this)
        );
        $filepath = app('view')->resolveViewRelative(
            $ControllerName . '/' . $view,
            (string)$this->request->getRouteConfig()['module']
        );
        if ($filepath) {
            return parent::render($ControllerName . '/' . $view, $data);
        } else {
            return parent::render('Maintainer/' . $view, $data);
        }
    }

    /**
     * Método que permite forzar las opciones de búsqueda para la acción listar
     * esto permite a cierto usuario mostrar solo cierto listado de registros
     * y no todos, esto evita tener que reprogramar la acción listar.
     */
    protected function forceSearch(array $data)
    {
        // Se asignan datos forzados para la búsqueda de registros.
        $search = [];
        foreach ($data as $var => $val) {
            $search[] = $var . ':' . $val;
        }
        // Se copian filtros extras, menos los forzados.
        if (!empty($_GET['search'])) {
            $vars = array_keys($data);
            $filters = explode(',', $_GET['search']);
            foreach ($filters as &$filter) {
                if (strpos($filter, ':')) {
                    list($var, $val) = explode(':', $filter);
                    if (!in_array($var, $vars)) {
                        $search[] = $var . ':' . $val;
                    }
                }
            }
        }
        // Se vuelve a armar la búsqueda.
        $_GET['search'] = implode(',', $search);
    }

    /**
     * Acción para listar los registros de la tabla.
     */
    public function listar($page = 1, $orderby = null, $order = 'A')
    {
        $model = $this->model;
        // Crear objeto plural.
        $Objs = new $this->models();
        // Si se debe buscar se agrega filtro.
        $searchUrl = null;
        $search = [];
        if (!empty($_GET['search'])) {
            $searchUrl = '?search=' . $_GET['search'];
            $filters = explode(',', $_GET['search']);
            $where = [];
            $vars = [];
            foreach ($filters as &$filter) {
                if (!strpos($filter, ':')) {
                    continue;
                }
                list($var, $val) = explode(':', $filter);
                // Solo procesar filtros donde el campo por el que se filtra
                // esté en el modelo.
                if (empty($model::$columnsInfo[$var])) {
                    continue;
                }
                $search[$var] = $val;
                // Si el valor es '!null' se compara contra IS NOT NULL.
                if ($val == '!null') {
                    $where[] = $var . ' IS NOT NULL';
                }
                // Si el valor es null o 'null' se compara contra IS NULL.
                else if ($val === null || $val == 'null') {
                    $where[] = $var . ' IS NULL';
                }
                // Si es una FK se filtra con igualdad.
                else if (!empty($model::$columnsInfo[$var]['fk'])) {
                    $where[] = $var . ' = :' . $var;
                    $vars[':' . $var] = $val;
                }
                // Si es un campo de texto se filtrará con LIKE.
                else if (in_array($model::$columnsInfo[$var]['type'], ['char', 'character varying', 'varchar', 'text'])) {
                    $where[] = 'LOWER(' . $var . ') LIKE :' . $var;
                    $vars[':' . $var] = '%' . strtolower($val) . '%';
                }
                // Si es un tipo fecha con hora se usará LIKE.
                else if (in_array($model::$columnsInfo[$var]['type'], ['timestamp', 'timestamp without time zone'])) {
                    $where[] = 'CAST(' . $var . ' AS TEXT) LIKE :' . $var;
                    $vars[':' . $var] = $val . ' %';
                }
                // Si es un campo número entero se castea.
                else if (in_array($model::$columnsInfo[$var]['type'], ['smallint', 'integer', 'bigint', 'smallserial', 'serial', 'bigserial'])) {
                    $where[] = $var . ' = :' . $var;
                    $vars[':' . $var] = (int)$val;
                }
                // Si es un campo número decimal se castea.
                else if (in_array($model::$columnsInfo[$var]['type'], ['decimal', 'numeric', 'real', 'double precision'])) {
                    $where[] = $var . ' = :' . $var;
                    $vars[':' . $var] = (float)$val;
                }
                // Si es cualquier otro caso se comparará con una igualdad.
                else {
                    $where[] = $var . ' = :' . $var;
                    $vars[':' . $var] = $val;
                }
            }
            $Objs->setWhereStatement($where, $vars);
        }
        // Si se debe ordenar se agrega.
        if (isset($model::$columnsInfo[$orderby])) {
            $Objs->setOrderByStatement([$orderby => ($order == 'D' ? 'DESC' : 'ASC')]);
        }
        // Total de registros.
        try {
            $registers_total = $Objs->count();
        } catch (\Exception $e) {
            // Si hay algún error en la base de datos es porque los filtros
            // están mal armados. Si se llegó acá con el error, para que no
            // falle la app se redirecciona al listado con el error.
            // Lo ideal es controlar esto antes con un "error más lindo".
            SessionMessage::error($e->getMessage());
            return redirect($this->request->getRequestUriDecoded());
        }
        // Paginar los resultados si es necesario.
        if ((integer)$page > 0) {
            $registers_per_page = config('app.ui.pagination.registers');
            $pages = ceil($registers_total / $registers_per_page);
            $Objs->setLimitStatement(
                $registers_per_page,
                ($page - 1) * $registers_per_page
            );
            if ($page != 1 && $page > $pages) {
                return redirect(
                    $this->module_url . $this->request->getRouteConfig()['controller'] . '/listar/1'
                    . ($orderby ? ('/' . $orderby . '/' . $order) : '') . $searchUrl
                );
            }
        }
        // Crear variable con las columnas para la vista.
        if (!empty($this->columnsView['listar'])) {
            $columns = [];
            foreach ($model::$columnsInfo as $col => &$info) {
                if (in_array($col, $this->columnsView['listar'])) {
                    $columns[$col] = $info;
                }
            }
        } else {
            $columns = $model::$columnsInfo;
        }
        // setear variables
        $this->set(array(
            'module_url' => $this->module_url,
            'controller' => $this->request->getRouteConfig()['controller'],
            'page' => $page,
            'orderby' => $orderby,
            'order' => $order,
            'searchUrl' => $searchUrl,
            'search' => $search,
            'Objs' => $Objs->getObjects($this->model),
            'columns' => $columns,
            'registers_total' => $registers_total,
            'pages' => isset($pages) ? $pages : 0,
            'linkEnd' => ($orderby ? ('/' . $orderby . '/' . $order) : '') . $searchUrl,
            'fkNamespace' => $model::$fkNamespace,
            'comment' => $model::$tableComment,
            'listarFilterUrl' => '?listar=' . base64_encode(
                '/' . $page . ($orderby ? ('/' . $orderby . '/' . $order) : '') . $searchUrl
            ),
            'deleteRecord' => $this->deleteRecord,
            'actionsColsWidth' => $this->actionsColsWidth,
            'extraActions' => $this->extraActions,
        ));
        // Renderizar.
        return $this->render('listar');
    }

    /**
     * Acción para crear un registro en la tabla
     */
    public function crear()
    {
        $filterListar = !empty($_GET['listar']) ? base64_decode($_GET['listar']) : '';
        // si se envió el formulario se procesa
        if (isset($_POST['submit'])) {
            $Obj = new $this->model();
            $Obj->set($_POST);
            if (!$Obj->exists()) {
                foreach($_FILES as $name => &$file) {
                    if (!$file['error']) {
                        $Obj->setFile($name, $file);
                    }
                }
                try {
                    $Obj->checkAttributes();
                    if ($Obj->save()) {
                        SessionMessage::success('Registro creado.');
                    } else {
                        SessionMessage::error('Registro no creado.');
                    }
                    return redirect(
                        $this->module_url . $this->request->getRouteConfig()['controller'] . '/listar' . $filterListar
                    );
                } catch (\Exception $e) {
                    SessionMessage::error($e->getMessage());
                }
            } else {
                SessionMessage::error('Registro ya existe.');
            }
        }
        // setear variables
        $model = $this->model;
        $this->set(array(
            'columnsInfo' => $model::$columnsInfo,
            'fkNamespace' => $model::$fkNamespace,
            'accion' => 'Crear',
            'columns' => $model::$columnsInfo,
            'contraseniaNames' => $this->contraseniaNames,
            'listarUrl' => $this->module_url . $this->request->getRouteConfig()['controller']
                . '/listar' . $filterListar,
        ));
        // renderizar
        return $this->render('crear_editar');
    }

    /**
     * Acción para editar un registro de la tabla
     * @param pk Parámetro que representa la PK, pueden ser varios parámetros los pasados
     */
    public function editar($pk)
    {
        $filterListar = !empty($_GET['listar']) ? base64_decode($_GET['listar']) : '';
        $Obj = new $this->model(array_map('urldecode', func_get_args()));
        // si el registro que se quiere editar no existe error
        if (!$Obj->exists()) {
            SessionMessage::error(
                'Registro (' . implode(', ', func_get_args()) . ') no existe, no se puede editar.'
            );
            return redirect(
                $this->module_url.$this->request->getRouteConfig()['controller'].'/listar'.$filterListar
            );
        }
        // si no se ha enviado el formulario se mostrará
        $model = $this->model;
        if (isset($_POST['submit'])) {
            foreach ($model::$columnsInfo as $col => &$info) {
                if (in_array($col, $this->contraseniaNames) && empty($_POST[$col])) {
                    $_POST[$col] = $Obj->$col;
                }
            }
            $Obj->set($_POST);
            foreach($_FILES as $name => &$file) {
                if (!$file['error']) {
                    $Obj->setFile($name, $file);
                }
            }
            try {
                $Obj->checkAttributes();
                if ($Obj->save()) {
                    SessionMessage::success(
                        'Registro ('.implode(', ', func_get_args()).') editado.'
                    );
                } else {
                    SessionMessage::error(
                        'Registro ('.implode(', ', func_get_args()).') no editado.'
                    );
                }
                return redirect(
                    $this->module_url . $this->request->getRouteConfig()['controller'] . '/listar' . $filterListar
                );
            } catch (\Exception $e) {
                SessionMessage::error($e->getMessage());
            }
        }
        // renderizar la vista
        $this->set(array(
            'Obj' => $Obj,
            'columns' => $model::$columnsInfo,
            'contraseniaNames' => $this->contraseniaNames,
            'fkNamespace' => $model::$fkNamespace,
            'accion' => 'Editar',
            'listarUrl' => $this->module_url . $this->request->getRouteConfig()['controller']
                . '/listar' . $filterListar,
        ));
        // renderizar
        return $this->render('crear_editar');
    }

    /**
     * Acción para eliminar un registro de la tabla
     * @param pk Parámetro que representa la PK, pueden ser varios parámetros los pasados
     */
    public function eliminar($pk)
    {
        if (!$this->deleteRecord) {
            SessionMessage::error('No se permite el borrado de registros.');
            return redirect(
                $this->module_url . $this->request->getRouteConfig()['controller'] . '/listar' . $filterListar
            );
        }
        $filterListar = !empty($_GET['listar']) ? base64_decode($_GET['listar']) : '';
        $Obj = new $this->model(array_map('urldecode', func_get_args()));
        // si el registro que se quiere eliminar no existe error
        if(!$Obj->exists()) {
            SessionMessage::error(
                'Registro (' . implode(', ', func_get_args()) . ') no existe, no se puede eliminar.'
            );
            return redirect(
                $this->module_url.$this->request->getRouteConfig()['controller'].'/listar'.$filterListar
            );
        }
        try {
            $Obj->delete();
            SessionMessage::success(
                'Registro (' . implode(', ', func_get_args()) . ') eliminado.'
            );
        } catch (\Exception $e) {
            SessionMessage::error(
                'No se pudo eliminar el registro (' . implode(', ', func_get_args()) . '): '.$e->getMessage()
            );
        }
        return redirect(
            $this->module_url . $this->request->getRouteConfig()['controller'] . '/listar' . $filterListar
        );
    }

    /**
     * Método para descargar un archivo desde la base de datos
     */
    public function d($campo, $pk)
    {
        // si el campo que se solicita no existe error
        $model = $this->model;
        if (!isset($model::$columnsInfo[$campo . '_data'])) {
            SessionMessage::error('Campo '.$campo.' no existe.');
            return redirect(
                $this->module_url . $this->request->getRouteConfig()['controller'] . '/listar'
            );
        }
        $pks = array_slice(func_get_args(), 1);
        $Obj = new $this->model($pks);
        // si el registro que se quiere eliminar no existe error
        if(!$Obj->exists()) {
            SessionMessage::error(
                'Registro (' . implode(', ', $pks) . ') no existe. No se puede obtener '.$campo.'.'
            );
            return redirect(
                $this->module_url . $this->request->getRouteConfig()['controller'] . '/listar'
            );
        }
        if ((float)$Obj->{$campo.'_size'} == 0.0) {
            SessionMessage::error(
                'No hay datos para el campo ' . $campo . ' en el registro ('.implode(', ', $pks).').'
            );
            return redirect(
                $this->module_url . $this->request->getRouteConfig()['controller'] . '/listar'
            );
        }
        // entregar archivo
        return $this->response->prepareFileResponse([
            'name' => $Obj->{$campo.'_name'},
            'type' => $Obj->{$campo.'_type'},
            'size' => $Obj->{$campo.'_size'},
            'data' => $Obj->{$campo.'_data'},
        ]);
    }

}
