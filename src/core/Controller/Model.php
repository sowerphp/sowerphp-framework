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

use \sowerphp\core\Network_Request as Request;
use \sowerphp\core\Facade_Session_Message as SessionMessage;

/**
 * Clase que implementa los métodos para interacturar con recursos de modelos
 * de la base de datos.
 */
abstract class Controller_Model extends \sowerphp\autoload\Controller
{

    /**
     * Servicio de modelos.
     *
     * @var Service_Model
     */
    protected $modelService;

    /**
     * Información del modelo asociado al controlador.
     *
     * Arreglo con índices: database, table, namespace, singular y plural.
     *
     * @var array
     */
    protected $model;

    /**
     * Inicializar el controlador.
     */
    public function boot(): void
    {
        $this->modelService = model();
        $this->model = $this->modelService->getModelInfoFromController(
            get_class($this),
            $this->model ?? []
        );
    }

    /**
     * Muestra una lista de recursos.
     */
    public function index(Request $request)
    {
        return $this->render('index');
    }

    /**
     * Muestra el formulario para crear un nuevo recurso.
     */
    public function create(Request $request)
    {
    }

    /**
     * Almacena un recurso recién creado en el almacenamiento.
     */
    public function store(Request $request)
    {
    }

    /**
     * Muestra el recurso especificado.
     */
    public function show(Request $request, ...$id)
    {
    }

    /**
     * Muestra el formulario para editar el recurso especificado.
     */
    public function edit(Request $request, ...$id)
    {
    }

    /**
     * Actualiza el recurso especificado en el almacenamiento.
     */
    public function update(Request $request, ...$id)
    {
    }

    /**
     * Elimina el recurso especificado del almacenamiento.
     */
    public function destroy(Request $request, ...$id)
    {
    }

    /**
     * Retorna una lista de recursos.
     */
    public function _api_index_GET(Request $request)
    {
        // Obtener registros.
        $parameters = $request->getModelParametersFromUrl();
        $results = $this->modelService->filter(
            $this->model,
            $parameters,
            $parameters['stdClass']
        );
        // Preparar respuesta formato estándar.
        if ($parameters['format'] == 'standard') {
            $metaTotal = $this->modelService->count($this->model, [
                'filters' => $parameters['filters'],
            ]);
            $metaCount = count($results);
            $metaPaginationTotalPages = ceil(
                $metaTotal / $parameters['pagination']['limit']
            );
            $currentUrl = $request->getFullUrlWithoutQuery()
                . $request->getRequestUriDecoded()
            ;
            $aux = ['pagination' => ['page' => 1]];
            $linksFirst = $currentUrl . '?' . $this->modelService->buildUrlParameters(
                Utility_Array::mergeRecursiveDistinct($parameters, $aux)
            );
            $aux = ['pagination' => ['page' => $parameters['pagination']['page'] - 1]];
            $linksPrev = $parameters['pagination']['page'] > 1
                ? $currentUrl . '?' . $this->modelService->buildUrlParameters(
                    Utility_Array::mergeRecursiveDistinct($parameters, $aux)
                )
                : null
            ;
            $linksSelf = $currentUrl . '?' . $this->modelService->buildUrlParameters($parameters);
            $aux = ['pagination' => ['page' => $parameters['pagination']['page'] + 1]];
            $linksNext = $parameters['pagination']['page'] < $metaPaginationTotalPages
                ? $currentUrl . '?' . $this->modelService->buildUrlParameters(
                    Utility_Array::mergeRecursiveDistinct($parameters, $aux)
                )
                : null
            ;
            $aux = ['pagination' => ['page' => $metaPaginationTotalPages]];
            $linksLast = $currentUrl . '?' . $this->modelService->buildUrlParameters(
                Utility_Array::mergeRecursiveDistinct($parameters, $aux)
            );
            $body = [
                'meta' => [
                    'total' => $metaTotal,
                    'count' => $metaCount,
                    'pagination' => [
                        'current_page' => (int)$parameters['pagination']['page'],
                        'total_pages' => $metaPaginationTotalPages,
                        'per_page' => (int)$parameters['pagination']['limit'],
                    ]
                ],
                'links' => [
                    'first' => $linksFirst,
                    'prev' => $linksPrev,
                    'self' => $linksSelf,
                    'next' => $linksNext,
                    'last' => $linksLast,
                ],
                'data' => $results,
            ];
        }
        // Preparar respuesta formato Datatables.
        else if ($parameters['format'] == 'datatables') {
            $recordsTotal = $this->modelService->count($this->model, []);
            $recordsFiltered = count($results);
            $body = [
                'draw' => (int)$request->input('draw'),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $results,
            ];
        }
        // Entregar respuesta.
        return response()->json($body, 200);
    }

    /**
     * Muestra la estructura para crear un recurso.
     */
    public function _api_create_GET(Request $request)
    {
        // Obtener los datos necesarios para la creación.
        // Por ejemplo, opciones de selección, listas de valores predefinidos, etc.
        $data = $this->modelService->getCreationData($this->model);
        return response()->json(['data' => $data], 200);
    }

    /**
     * Almacena un nuevo recurso y retorna la respuesta.
     */
    public function _api_store_POST(Request $request)
    {
        // Validar los datos de entrada.
        $validatedData = $request->validate($this->model['validation_rules']);
        // Crear el nuevo recurso.
        $newResource = $this->modelService->create($this->model, $validatedData);
        return response()->json(['data' => $newResource], 201);
    }

    /**
     * Retorna el recurso especificado.
     */
    public function _api_show_GET(Request $request, ...$id)
    {
        $stdClass = (bool)$request->input('stdClass', false);
        try {
            $result = $this->modelService->retrieve($this->model, $id, $stdClass);
            return response()->json(['data' => $result], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => $e->getCode() ?: 500,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Muestra la estructura para editar un recurso.
     */
    public function _api_edit_GET(Request $request, ...$id)
    {
        // Obtener el recurso especificado.
        $resource = $this->modelService->retrieve($this->model, $id);
        // Obtener los datos necesarios para la edición, similares a la creación.
        $data = $this->modelService->getEditData($this->model, $resource);
        return response()->json(['data' => $data], 200);
    }

    /**
     * Actualiza el recurso especificado y retorna la respuesta.
     */
    public function _api_update_PUT(Request $request, ...$id)
    {
        // Validar los datos de entrada.
        $validatedData = $request->validate($this->model['validation_rules']);
        // Actualizar el recurso.
        $updatedResource = $this->modelService->update($this->model, $id, $validatedData);
        return response()->json(['data' => $updatedResource], 200);
    }

    /**
     * Elimina el recurso especificado y retorna la respuesta.
     */
    public function _api_destroy_DELETE(Request $request, ...$id)
    {
        // Eliminar el recurso.
        $this->modelService->delete($this->model, $id);
        return response()->json(['message' => 'Resource deleted successfully.'], 200);
    }









    protected $deleteRecord = true; ///< Indica si se permite o no borrar registros
    protected $contraseniaNames = ['contrasenia', 'clave', 'password', 'pass']; ///< Posibles nombres de campo tipo contraseña
    protected $actionsColsWidth = 170; ///< Ancho de la columna de acciónes en acción listar
    protected $extraActions = []; ///< iconos extra para la columna de acciones

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
        $request = request();
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
            (string)$request->getRouteConfig()['module']
        );
        if ($filepath) {
            return parent::render($ControllerName . '/' . $view, $data);
        } else {
            return parent::render('Model/' . $view, $data);
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
        $request = request();
        // Crear objeto plural.
        $Objs = new $this->model['plural']();
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
                if (empty($this->model['singular']::$columnsInfo[$var])) {
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
                else if (!empty($this->model['singular']::$columnsInfo[$var]['fk'])) {
                    $where[] = $var . ' = :' . $var;
                    $vars[':' . $var] = $val;
                }
                // Si es un campo de texto se filtrará con LIKE.
                else if (in_array($this->model['singular']::$columnsInfo[$var]['type'], ['char', 'character varying', 'varchar', 'text'])) {
                    $where[] = 'LOWER(' . $var . ') LIKE :' . $var;
                    $vars[':' . $var] = '%' . strtolower($val) . '%';
                }
                // Si es un tipo fecha con hora se usará LIKE.
                else if (in_array($this->model['singular']::$columnsInfo[$var]['type'], ['timestamp', 'timestamp without time zone'])) {
                    $where[] = 'CAST(' . $var . ' AS TEXT) LIKE :' . $var;
                    $vars[':' . $var] = $val . ' %';
                }
                // Si es un campo número entero se castea.
                else if (in_array($this->model['singular']::$columnsInfo[$var]['type'], ['smallint', 'integer', 'bigint', 'smallserial', 'serial', 'bigserial'])) {
                    $where[] = $var . ' = :' . $var;
                    $vars[':' . $var] = (int)$val;
                }
                // Si es un campo número decimal se castea.
                else if (in_array($this->model['singular']::$columnsInfo[$var]['type'], ['decimal', 'numeric', 'real', 'double precision'])) {
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
        if (isset($this->model['singular']::$columnsInfo[$orderby])) {
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
            return redirect($request->getRequestUriDecoded());
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
                    $request->getControllerUrl() . '/listar/1'
                    . ($orderby ? ('/' . $orderby . '/' . $order) : '') . $searchUrl
                );
            }
        }
        // Crear variable con las columnas para la vista.
        if (!empty($this->columnsView['listar'])) {
            $columns = [];
            foreach ($this->model['singular']::$columnsInfo as $col => &$info) {
                if (in_array($col, $this->columnsView['listar'])) {
                    $columns[$col] = $info;
                }
            }
        } else {
            $columns = $this->model['singular']::$columnsInfo;
        }
        // Renderizar la vista.
        return $this->render('listar', [
            'model' => $this->model['singular'],
            'models' => $this->model['plural'],
            'module_url' => $request->getModuleUrl() . '/',
            'controller' => $request->getRouteConfig()['controller'],
            'page' => $page,
            'orderby' => $orderby,
            'order' => $order,
            'searchUrl' => $searchUrl,
            'search' => $search,
            'Objs' => $Objs->getObjects($this->model['singular']),
            'columns' => $columns,
            'registers_total' => $registers_total,
            'pages' => isset($pages) ? $pages : 0,
            'linkEnd' => ($orderby ? ('/' . $orderby . '/' . $order) : '') . $searchUrl,
            'fkNamespace' => $this->model['singular']::$fkNamespace,
            'comment' => $this->model['singular']::$tableComment,
            'listarFilterUrl' => '?listar=' . base64_encode(
                '/' . $page . ($orderby ? ('/' . $orderby . '/' . $order) : '') . $searchUrl
            ),
            'deleteRecord' => $this->deleteRecord,
            'actionsColsWidth' => $this->actionsColsWidth,
            'extraActions' => $this->extraActions,
        ]);
    }

    /**
     * Acción para crear un registro en la tabla
     */
    public function crear()
    {
        $request = request();
        $filterListar = !empty($_GET['listar']) ? base64_decode($_GET['listar']) : '';
        // si se envió el formulario se procesa
        if (isset($_POST['submit'])) {
            $Obj = new $this->model['singular']();
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
                        $request->getControllerUrl() . '/listar' . $filterListar
                    );
                } catch (\Exception $e) {
                    SessionMessage::error($e->getMessage());
                }
            } else {
                SessionMessage::error('Registro ya existe.');
            }
        }
        // Renderizar la vista
        return $this->render('crear_editar', [
            'columnsInfo' => $this->model['singular']::$columnsInfo,
            'fkNamespace' => $this->model['singular']::$fkNamespace,
            'accion' => 'Crear',
            'columns' => $this->model['singular']::$columnsInfo,
            'contraseniaNames' => $this->contraseniaNames,
            'listarUrl' => $request->getControllerUrl()
                . '/listar' . $filterListar,
        ]);
    }

    /**
     * Acción para editar un registro de la tabla
     * @param pk Parámetro que representa la PK, pueden ser varios parámetros los pasados
     */
    public function editar($pk)
    {
        $request = request();
        $filterListar = !empty($_GET['listar']) ? base64_decode($_GET['listar']) : '';
        $Obj = new $this->model['singular'](array_map('urldecode', func_get_args()));
        // si el registro que se quiere editar no existe error
        if (!$Obj->exists()) {
            SessionMessage::error(
                'Registro (' . implode(', ', func_get_args()) . ') no existe, no se puede editar.'
            );
            return redirect(
                $request->getControllerUrl() . '/listar'.$filterListar
            );
        }
        // si no se ha enviado el formulario se mostrará
        if (isset($_POST['submit'])) {
            foreach ($this->model['singular']::$columnsInfo as $col => &$info) {
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
                    $request->getControllerUrl() . '/listar' . $filterListar
                );
            } catch (\Exception $e) {
                SessionMessage::error($e->getMessage());
            }
        }
        // Renderizar la vista.
        return $this->render('crear_editar', [
            'Obj' => $Obj,
            'columns' => $this->model['singular']::$columnsInfo,
            'contraseniaNames' => $this->contraseniaNames,
            'fkNamespace' => $this->model['singular']::$fkNamespace,
            'accion' => 'Editar',
            'listarUrl' => $request->getControllerUrl()
                . '/listar' . $filterListar,
        ]);
    }

    /**
     * Acción para eliminar un registro de la tabla
     * @param pk Parámetro que representa la PK, pueden ser varios parámetros los pasados
     */
    public function eliminar($pk)
    {
        $request = request();
        $filterListar = !empty($_GET['listar']) ? base64_decode($_GET['listar']) : '';
        if (!$this->deleteRecord) {
            SessionMessage::error('No se permite el borrado de registros.');
            return redirect(
                $request->getControllerUrl() . '/listar' . $filterListar
            );
        }
        $Obj = new $this->model['singular'](array_map('urldecode', func_get_args()));
        // si el registro que se quiere eliminar no existe error
        if(!$Obj->exists()) {
            SessionMessage::error(
                'Registro (' . implode(', ', func_get_args()) . ') no existe, no se puede eliminar.'
            );
            return redirect(
                $request->getControllerUrl() . '/listar'.$filterListar
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
            $request->getControllerUrl() . '/listar' . $filterListar
        );
    }

    /**
     * Método para descargar un archivo desde la base de datos
     */
    public function d($campo, $pk)
    {
        $request = request();
        // si el campo que se solicita no existe error
        if (!isset($this->model['singular']::$columnsInfo[$campo . '_data'])) {
            SessionMessage::error('Campo '.$campo.' no existe.');
            return redirect(
                $request->getControllerUrl() . '/listar'
            );
        }
        $pks = array_slice(func_get_args(), 1);
        $Obj = new $this->model['singular']($pks);
        // si el registro que se quiere eliminar no existe error
        if(!$Obj->exists()) {
            SessionMessage::error(
                'Registro (' . implode(', ', $pks) . ') no existe. No se puede obtener '.$campo.'.'
            );
            return redirect(
                $request->getControllerUrl() . '/listar'
            );
        }
        if ((float)$Obj->{$campo.'_size'} == 0.0) {
            SessionMessage::error(
                'No hay datos para el campo ' . $campo . ' en el registro ('.implode(', ', $pks).').'
            );
            return redirect(
                $request->getControllerUrl() . '/listar'
            );
        }
        // entregar archivo
        return response()->file([
            'name' => $Obj->{$campo.'_name'},
            'type' => $Obj->{$campo.'_type'},
            'size' => $Obj->{$campo.'_size'},
            'data' => $Obj->{$campo.'_data'},
        ]);
    }

}
