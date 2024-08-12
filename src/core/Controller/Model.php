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
 * Clase que implementa los métodos para interacturar con registros de modelos
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
     * Clase del modelo singular asociado al controlador.
     *
     * @var string
     */
    protected $modelClass;

    /**
     * Versión de la API actualmente vigente.
     *
     * @var string
     */
    protected $apiVersion = '2.0.0';

    /**
     * Constructor del controlador.
     */
    public function __construct(
        Service_Model $modelService,
        Network_Request $request,
        Network_Response $response
    )
    {
        $this->modelService = $modelService;
        parent::__construct($request, $response);
    }

    public function boot(): void
    {
        // TODO: eliminar al terminar pruebas (pedirá permisos).
    }

    /**
     * Obtiene el nombre de la clase del modelo singular asociada al
     * controlador.
     *
     * @return string
     */
    protected function getModelClass(): string
    {
        if (!isset($this->modelClass)) {
            $this->modelClass = $this->modelService->getModelFromController(
                get_class($this)
            );
        }
        return $this->modelClass;
    }

    /**
     * Obtiene la instancia del modelo plural asociado al controlador.
     *
     * @return Model_Plural
     */
    protected function getModelPluralInstance(): Model_Plural
    {
        $modelClass = $this->getModelClass();
        $pluralInstance = (new $modelClass())->getPluralInstance();
        return $pluralInstance;
    }

    /**
     * Muestra una lista de recursos.
     */
    public function index(Request $request)
    {
        $instance = $this->modelService->instantiate(
            $this->getModelClass()
        );
        if (!in_array('list', $instance->getMeta()['model.default_permissions'])) {
            return redirect()->withError(__(
                'No es posible listar registros de tipo %s.',
                $instance->getMeta()['model.label']
            ))->back();
        }
        $data = $instance->getListData();
        return $this->render('index', [
            'data' => $data,
        ]);
    }

    /**
     * Muestra el recurso especificado.
     */
    public function show(Request $request, ...$id)
    {
        $instance = $this->modelService->instantiate(
            $this->getModelClass(),
            ...$id
        );
        if (!$instance->exists()) {
            if (!$instance->exists()) {
                return redirect()->withError(__(
                    'Recurso solicitado %s(%s) no existe, no se puede mostrar.',
                    $instance->getMeta()['model.label'],
                    implode(', ', $id)
                ))->back();
            }
        }
        if (!in_array('view', $instance->getMeta()['model.default_permissions'])) {
            return redirect()->withError(__(
                'No es posible mostrar registros de tipo %s.',
                $instance->getMeta()['model.label']
            ))->back();
        }
        $data = $instance->getShowData();
        foreach ($data['fields'] as $field => &$config) {
            $config['value'] = $instance->getAttribute($field);
        }
        return $this->render('show', [
            'id' => $id,
            'data' => $data,
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo recurso.
     */
    public function create(Request $request)
    {
        // Crear una instancia del modelo.
        $instance = $this->modelService->instantiate(
            $this->getModelClass()
        );
        // Validar que el modelo permite la creación de registros.
        if (!in_array('add', $instance->getMeta()['model.default_permissions'])) {
            return redirect()->withError(__(
                'No es posible crear registros de tipo %s.',
                $instance->getMeta()['model.label']
            ))->back();
        }
        // Preparar los metadatos para el formulario de creación.
        $data = $instance->getSaveDataCreate();
        $routeConfig = $request->getRouteConfig();
        // Agregar metadatos del formulario en si.
        $data['form'] = [
            'data' => $request->input(),
            'file' => $request->file(),
            'attributes' => [
                'id' => $data['model']['db_table'] . 'ModelCreateForm',
                'action' => url($routeConfig['url']['controller'] . '/store'),
                'onsubmit' => 'return validateModelCreateForm(this)',
            ],
            'submit_button' => [
                'label' => 'Crear nuevo ' . strtolower($data['model']['verbose_name']),
            ],
        ];
        // Crear formulario a partir de los metadados del modelo.
        $form = View_Form_Model::create($data);
        // Renderizar la vista con el formulario de creación.
        return $this->render('create', [
            'data' => $data,
            'form' => $form,
        ]);
    }

    /**
     * Almacena un recurso recién creado en el almacenamiento.
     */
    public function store(Request $request)
    {
        // Crear una instancia del modelo.
        $instance = $this->modelService->instantiate(
            $this->getModelClass()
        );
        // Validar datos.
        try {
            $rules = $instance->getValidationRulesCreate();
            $validatedData = $request->validate($rules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back(422)
                ->withErrors($e->errors(), 'create')
                ->withInput()
            ;
        }
        // Obtener URL de la API que se deberá consumir.
        $routeConfig = $request->getRouteConfig();
        $url = sprintf(
            '%s/%s',
            $routeConfig['url']['controller'],
            $routeConfig['action']
        );
        // Consumir recurso de la API.
        $response = libredte()->post($url, ['data' => $validatedData]);
        $json = $response->json() ?? $response->body();
        if (!$response->successful()) {
            if (is_string($json)) {
                $message = $json;
                $status_code = $response->status();
            } else {
                $message = $json['message'];
                if (!empty($json['errors'])) {
                    foreach ($json['errors'] as $field => $errors) {
                        foreach ($errors as $error) {
                            $message .= ' ' . $error;
                        }
                    }
                }
                $status_code = $json['status_code'];
            }
            return redirect()->back($status_code)->withError($message)->withInput();
        } else {
            return redirect()->withSuccess($json['message'])->back(200);
        }
    }

    /**
     * Muestra el formulario para editar el recurso especificado.
     */
    public function edit(Request $request, ...$id)
    {
        // Crear una instancia del modelo.
        $instance = $this->modelService->instantiate(
            $this->getModelClass(),
            ...$id
        );
        if (!$instance->exists()) {
            return redirect()->withError(__(
                'Recurso solicitado %s(%s) no existe, no se puede editar.',
                $instance->getMeta()['model.label'],
                implode(', ', $id)
            ))->back();
        }
        // Validar que el modelo permite la edición de registros.
        if (!in_array('change', $instance->getMeta()['model.default_permissions'])) {
            return redirect()->withError(__(
                'No es posible editar registros de tipo %s.',
                $instance->getMeta()['model.label']
            ))->back();
        }
        // Preparar los metadatos para el formulario de edición.
        $data = $instance->getSaveDataEdit();
        $routeConfig = $request->getRouteConfig();
        // Agregar metadatos del formulario en si.
        $data['form'] = [
            'data' => $request->input(),
            'file' => $request->file(),
            'attributes' => [
                'id' => $data['model']['db_table'] . 'ModelEditForm',
                'action' => url(
                    $routeConfig['url']['controller']
                    . '/update/'
                    . implode('/', $id)
                ),
                'onsubmit' => 'return validateModelEditForm(this)',
            ],
            'submit_button' => [
                'label' => 'Editar ' . strtolower($data['model']['verbose_name']),
            ],
        ];
        // Crear formulario a partir de los metadados del modelo.
        $form = View_Form_Model::create($data);
        // Renderizar la vista con el formulario de edición.
        return $this->render('edit', [
            'data' => $data,
            'form' => $form,
        ]);
    }

    /**
     * Actualiza el recurso especificado en el almacenamiento.
     */
    public function update(Request $request, ...$id)
    {
        // Crear una instancia del modelo.
        $instance = $this->modelService->instantiate(
            $this->getModelClass()
        );
        // Validar datos.
        try {
            $rules = $instance->getValidationRulesEdit();
            $validatedData = $request->validate($rules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back(422)
                ->withErrors($e->errors(), 'edit')
                ->withInput()
            ;
        }
        // Obtener URL de la API que se deberá consumir.
        $routeConfig = $request->getRouteConfig();
        $url = sprintf(
            '%s/%s/%s',
            $routeConfig['url']['controller'],
            $routeConfig['action'],
            implode('/', $id)
        );
        // Consumir recurso de la API.
        $response = libredte()->put($url, ['data' => $validatedData]);
        $json = $response->json() ?? $response->body();
        if (!$response->successful()) {
            if (is_string($json)) {
                $message = $json;
                $status_code = $response->status();
            } else {
                $message = $json['message'];
                if (!empty($json['errors'])) {
                    foreach ($json['errors'] as $field => $errors) {
                        foreach ($errors as $error) {
                            $message .= ' ' . $error;
                        }
                    }
                }
                $status_code = $json['status_code'];
            }
            return redirect()->back($status_code)->withError($message)->withInput();
        } else {
            return redirect()->withSuccess($json['message'])->back($response->status());
        }
    }

    /**
     * Elimina el recurso especificado del almacenamiento.
     */
    public function destroy(Request $request, ...$id)
    {
        // Obtener URL de la API que se deberá consumir.
        $routeConfig = $request->getRouteConfig();
        $url = sprintf(
            '%s/%s/%s',
            $routeConfig['url']['controller'],
            $routeConfig['action'],
            implode('/', $id)
        );
        // Consumir recurso de la API.
        $response = libredte()->delete($url);
        $json = $response->json() ?? $response->body();
        if (!$response->successful()) {
            $message = is_string($json) ? $json : $json['message'];
            $status_code = is_string($json) ? $response->status() : $json['status_code'];
            return redirect()->back($status_code)->withError($message);
        } else {
            return redirect()->withSuccess($json['message'])->back($response->status());
        }
    }

    /**
     * Entrega los metadatos de la consulta a la API.
     *
     * @param string $action Acción de la API que se realizó.
     * @param array $meta Metadatos que se deben incluir en la respuesta.
     * @return array Arreglo con los metadatos de la respuesta de la API.
     */
    protected function getApiMeta(string $action, array $meta = []): array
    {
        $request = request();
        return array_merge([
            'action' => $action,
            'version' => $this->apiVersion,
            //'url' => $request->fullUrl(),
            'url' => $request->url(),
            'generated_by' => get_class($this),
            'generated_at' => date('c'),
            //'expires_at' => date('c', strtotime('+1 day')),
            'locale' => app('translator')->getLocale(),
            'user' => $request->user(),
        ], $meta);
    }

    /**
     * Retorna una lista de recursos.
     */
    public function _api_index_GET(Request $request)
    {
        $pluralInstance = $this->getModelPluralInstance();
        if (!in_array('list', $pluralInstance->getMeta()['model.default_permissions'])) {
            throw new \Exception(__(
                'No es posible listar registros de tipo %s.',
                $pluralInstance->getMeta()['model.label']
            ));
        }
        // Obtener registros.
        $parameters = $request->getModelParametersFromUrl();
        $results = $pluralInstance->filter(
            $parameters,
            $parameters['stdClass']
        );
        // Preparar respuesta formato estándar.
        if ($parameters['format'] == 'standard') {
            $metaTotal = $pluralInstance->count([
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
                'meta' => $this->getApiMeta('index',[
                    'total' => $metaTotal,
                    'count' => $metaCount,
                    'pagination' => [
                        'current_page' => (int)$parameters['pagination']['page'],
                        'total_pages' => $metaPaginationTotalPages,
                        'per_page' => (int)$parameters['pagination']['limit'],
                    ]
                ]),
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
            $recordsTotal = $pluralInstance->count([]);
            $recordsFiltered = $pluralInstance->count([
                'filters' => $parameters['filters'],
            ]);
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
     * Muestra la estructura para listar los recursos.
     *
     * Permite obtener los datos necesarios para listar los registros de manera
     * correcta. Por ejemplo, nombres de columnas o valores para renderizar.
     */
    public function _api_list_GET(Request $request)
    {
        try {
            $instance = $this->modelService->instantiate(
                $this->getModelClass()
            );
            if (!in_array('list', $instance->getMeta()['model.default_permissions'])) {
                throw new \Exception(__(
                    'No es posible listar registros de tipo %s.',
                    $instance->getMeta()['model.label']
                ));
            }
            $data = $instance->getListData();
            return response()->json([
                'meta' => $this->getApiMeta('list'),
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->jsonException($e);
        }
    }

    /**
     * Retorna el recurso especificado.
     */
    public function _api_show_GET(Request $request, ...$id)
    {
        $stdClass = (bool)$request->input('stdClass', false);
        try {
            $instance = $this->modelService->instantiate(
                $this->getModelClass(),
                ...$id
            );
            if (!in_array('view', $instance->getMeta()['model.default_permissions'])) {
                throw new \Exception(__(
                    'No es posible mostrar registros de tipo %s.',
                    $instance->getMeta()['model.label']
                ));
            }
            if (!$instance->exists()) {
                throw new \Exception(__(
                    'Recurso solicitado %s(%s) no existe, no se puede mostrar.',
                    $instance->getMeta()['model.label'],
                    implode(', ', $id)
                ));
            }
            $result = $stdClass ? $instance->toStdClass() : $instance->toArray();
            return response()->json([
                'meta' => $this->getApiMeta('show'),
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->jsonException($e);
        }
    }

    /**
     * Muestra la estructura para crear un recurso.
     *
     * Permite obtener los datos necesarios para la creación. Por ejemplo,
     * opciones de selección, listas de valores predefinidos, etc.
     */
    public function _api_create_GET(Request $request)
    {
        try {
            $instance = $this->modelService->instantiate(
                $this->getModelClass()
            );
            if (!in_array('add', $instance->getMeta()['model.default_permissions'])) {
                throw new \Exception(__(
                    'No es posible crear registros de tipo %s.',
                    $instance->getMeta()['model.label']
                ));
            }
            $data = $instance->getSaveDataCreate();
            return response()->json([
                'meta' => $this->getApiMeta('create'),
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->jsonException($e);
        }
    }

    /**
     * Almacena un nuevo recurso y retorna la respuesta.
     */
    public function _api_store_POST(Request $request)
    {
        try {
            $instance = $this->modelService->instantiate(
                $this->getModelClass()
            );
            if (!in_array('add', $instance->getMeta()['model.default_permissions'])) {
                throw new \Exception(__(
                    'No es posible almacenar registros de tipo %s.',
                    $instance->getMeta()['model.label']
                ));
            }
            $rules = $instance->getValidationRulesCreate('data.');
            $validatedData = $request->validate($rules);
            $instance->fill($validatedData['data']);
            if (!$instance->save()) {
                throw new \Exception(__(
                    'No fue posible crear el recurso %s.',
                    (string)$instance
                ), 422);
            }
            return response()->json([
                'meta' => $this->getApiMeta('store'),
                'message' => __(
                    'Recurso %s creado correctamente.',
                    (string)$instance
                ),
                'data' => $instance,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status_code' => 422,
                'message' => __('Los datos proporcionados no son válidos.'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->jsonException($e);
        }
    }

    /**
     * Muestra la estructura para editar un recurso.
     */
    public function _api_edit_GET(Request $request, ...$id)
    {
        try {
            $instance = $this->modelService->instantiate(
                $this->getModelClass(),
                ...$id
            );
            if (!in_array('change', $instance->getMeta()['model.default_permissions'])) {
                throw new \Exception(__(
                    'No es posible editar registros de tipo %s.',
                    $instance->getMeta()['model.label']
                ));
            }
            if (!$instance->exists()) {
                throw new \Exception(__(
                    'Recurso solicitado %s(%s) no existe, no se puede editar.',
                    $instance->getMeta()['model.label'],
                    implode(', ', $id)
                ));
            }
            $data = $instance->getSaveDataEdit();
            return response()->json([
                'meta' => $this->getApiMeta('edit'),
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->jsonException($e);
        }
    }

    /**
     * Actualiza el recurso especificado y retorna la respuesta.
     */
    public function _api_update_PUT(Request $request, ...$id)
    {
        try {
            $instance = $this->modelService->instantiate(
                $this->getModelClass(),
                ...$id
            );
            if (!in_array('change', $instance->getMeta()['model.default_permissions'])) {
                throw new \Exception(__(
                    'No es posible actualizar registros de tipo %s.',
                    $instance->getMeta()['model.label']
                ));
            }
            if (!$instance->exists()) {
                throw new \Exception(__(
                    'Recurso solicitado %s(%s) no existe, no se puede actualizar.',
                    $instance->getMeta()['model.label'],
                    implode(', ', $id)
                ));
            }
            $rules = $instance->getValidationRulesEdit('data.');
            $validatedData = $request->validate($rules);
            $instance->fill($validatedData['data']);
            if (!$instance->save()) {
                throw new \Exception(__(
                    'No fue posible editar el recurso %s.',
                    (string)$instance
                ), 422);
            }
            return response()->json([
                'meta' => $this->getApiMeta('update'),
                'message' => __(
                    'Recurso %s editado correctamente.',
                    (string)$instance
                ),
                'data' => $instance,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status_code' => 422,
                'message' => __('Los datos proporcionados no son válidos.'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->jsonException($e);
        }
    }

    /**
     * Elimina el recurso especificado y retorna la respuesta.
     */
    public function _api_destroy_DELETE(Request $request, ...$id)
    {
        try {
            $instance = $this->modelService->instantiate(
                $this->getModelClass(),
                ...$id
            );
            if (!in_array('delete', $instance->getMeta()['model.default_permissions'])) {
                throw new \Exception(__(
                    'No es posible eliminar registros de tipo %s.',
                    $instance->getMeta()['model.label']
                ));
            }
            if (!$instance->exists()) {
                throw new \Exception(__(
                    'Recurso solicitado %s(%s) no existe, no se puede eliminar.',
                    $instance->getMeta()['model.label'],
                    implode(', ', $id)
                ));
            }
            $status = $instance->delete();
            if ($status) {
                $message = __(
                    'Recurso %s eliminado correctamente.',
                    (string)$instance
                );
                $code = 200;
            } else {
                $message = __(
                    'No fue posible eliminar el recurso %s.',
                    (string)$instance
                );
                $code = 500;
            }
            return response()->json([
                'meta' => $this->getApiMeta('destroy'),
                'message' => $message,
            ], $code);
        } catch (\Exception $e) {
            return response()->jsonException($e);
        }
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

    /*
    |--------------------------------------------------------------------------
    | DESDE AQUÍ HACIA ABAJO ESTÁ OBSOLETO Y DEBE SER REFACTORIZADO Y ELIMINAR.
    |--------------------------------------------------------------------------
    */

    protected $deleteRecord = true; ///< Indica si se permite o no borrar registros
    protected $contraseniaNames = ['contrasenia', 'clave', 'password', 'pass']; ///< Posibles nombres de campo tipo contraseña
    protected $actionsColsWidth = 170; ///< Ancho de la columna de acciónes en acción listar
    protected $extraActions = []; ///< iconos extra para la columna de acciones

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
    public function listar(Request $request, $page = 1, $orderby = null, $order = 'A')
    {
        // Crear objeto plural.
        $Objs = $this->getModelPluralInstance();
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
                if (empty($this->getModelClass()::$columnsInfo[$var])) {
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
                else if (!empty($this->getModelClass()::$columnsInfo[$var]['fk'])) {
                    $where[] = $var . ' = :' . $var;
                    $vars[':' . $var] = $val;
                }
                // Si es un campo de texto se filtrará con LIKE.
                else if (in_array($this->getModelClass()::$columnsInfo[$var]['type'], ['char', 'character varying', 'varchar', 'text'])) {
                    $where[] = 'LOWER(' . $var . ') LIKE :' . $var;
                    $vars[':' . $var] = '%' . strtolower($val) . '%';
                }
                // Si es un tipo fecha con hora se usará LIKE.
                else if (in_array($this->getModelClass()::$columnsInfo[$var]['type'], ['timestamp', 'timestamp without time zone'])) {
                    $where[] = 'CAST(' . $var . ' AS TEXT) LIKE :' . $var;
                    $vars[':' . $var] = $val . ' %';
                }
                // Si es un campo número entero se castea.
                else if (in_array($this->getModelClass()::$columnsInfo[$var]['type'], ['smallint', 'integer', 'bigint', 'smallserial', 'serial', 'bigserial'])) {
                    $where[] = $var . ' = :' . $var;
                    $vars[':' . $var] = (int)$val;
                }
                // Si es un campo número decimal se castea.
                else if (in_array($this->getModelClass()::$columnsInfo[$var]['type'], ['decimal', 'numeric', 'real', 'double precision'])) {
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
        if (isset($this->getModelClass()::$columnsInfo[$orderby])) {
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
            return redirect($request->getRequestUriDecoded())
                ->withError(
                    __('%(error_message)s',
                        [
                            'error_message' => $e->getMessage()
                        ]
                    )
                );
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
            foreach ($this->getModelClass()::$columnsInfo as $col => &$info) {
                if (in_array($col, $this->columnsView['listar'])) {
                    $columns[$col] = $info;
                }
            }
        } else {
            $columns = $this->getModelClass()::$columnsInfo;
        }
        // Renderizar la vista.
        return $this->render('listar', [
            'model' => $this->getModelClass(),
            'models' => app('inflector')->pluralize($this->getModelClass()),
            'module_url' => $request->getModuleUrl() . '/',
            'controller' => $request->getRouteConfig()['controller'],
            'page' => $page,
            'orderby' => $orderby,
            'order' => $order,
            'searchUrl' => $searchUrl,
            'search' => $search,
            'Objs' => $Objs->getObjects($this->getModelClass()),
            'columns' => $columns,
            'registers_total' => $registers_total,
            'pages' => isset($pages) ? $pages : 0,
            'linkEnd' => ($orderby ? ('/' . $orderby . '/' . $order) : '') . $searchUrl,
            'fkNamespace' => $this->getModelClass()::$fkNamespace,
            'comment' => $this->getModelClass()::$tableComment,
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
    public function crear(Request $request)
    {
        $filterListar = !empty($_GET['listar']) ? base64_decode($_GET['listar']) : '';
        // si se envió el formulario se procesa
        if (isset($_POST['submit'])) {
            $modelClass = $this->getModelClass();
            $Obj = new $modelClass();
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
                        return redirect($request->getControllerUrl() . '/listar' . $filterListar)
                            ->withSuccess(
                                __('Registro creado.')
                            );
                    } else {
                        return redirect($request->getControllerUrl() . '/listar' . $filterListar)
                            ->withError(
                                __('Registro no creado.')
                            );
                    }
                } catch (\Exception $e) {
                    SessionMessage::error($e->getMessage());
                }
            } else {
                SessionMessage::error(__(
                    'Registro ya existe.'
                ));
            }
        }
        // Renderizar la vista
        return $this->render('crear_editar', [
            'columnsInfo' => $this->getModelClass()::$columnsInfo,
            'fkNamespace' => $this->getModelClass()::$fkNamespace,
            'accion' => 'Crear',
            'columns' => $this->getModelClass()::$columnsInfo,
            'contraseniaNames' => $this->contraseniaNames,
            'listarUrl' => $request->getControllerUrl()
                . '/listar' . $filterListar,
        ]);
    }

    /**
     * Acción para editar un registro de la tabla
     * @param pk Parámetro que representa la PK, pueden ser varios parámetros los pasados
     */
    public function editar(Request $request, ...$pk)
    {
        $filterListar = !empty($_GET['listar']) ? base64_decode($_GET['listar']) : '';
        $modelClass = $this->getModelClass();
        $Obj = new $modelClass(array_map('urldecode', $pk));
        // si el registro que se quiere editar no existe error
        if (!$Obj->exists()) {
            return redirect(
                $request->getControllerUrl() . '/listar'.$filterListar
            )->withError(
                __('Registro (%(args)s) no existe, no se puede editar.',
                    [
                        'args' => implode(', ', $pk)
                    ]
                )
            );
        }
        // si no se ha enviado el formulario se mostrará
        if (isset($_POST['submit'])) {
            foreach ($this->getModelClass()::$columnsInfo as $col => &$info) {
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
                    return redirect($request->getControllerUrl() . '/listar' . $filterListar)
                        ->withSuccess(
                            __('Registro (%(args)s) editado.',
                                [
                                    'args' => implode(', ', $pk)
                                ]
                            )
                        );
                } else {
                    return redirect($request->getControllerUrl() . '/listar' . $filterListar)
                        ->withError(
                            __('Registro (%(args)s) no editado.',
                                [
                                    'args' => implode(', ', $pk)
                                ]
                            )
                        );
                }
            } catch (\Exception $e) {
                SessionMessage::error($e->getMessage());
            }
        }
        // Renderizar la vista.
        return $this->render('crear_editar', [
            'Obj' => $Obj,
            'columns' => $this->getModelClass()::$columnsInfo,
            'contraseniaNames' => $this->contraseniaNames,
            'fkNamespace' => $this->getModelClass()::$fkNamespace,
            'accion' => 'Editar',
            'listarUrl' => $request->getControllerUrl()
                . '/listar' . $filterListar,
        ]);
    }

    /**
     * Acción para eliminar un registro de la tabla
     * @param pk Parámetro que representa la PK, pueden ser varios parámetros los pasados
     */
    public function eliminar(Request $request, ...$pk)
    {
        $filterListar = !empty($_GET['listar']) ? base64_decode($_GET['listar']) : '';
        if (!$this->deleteRecord) {
            return redirect(
                $request->getControllerUrl() . '/listar' . $filterListar
            )->withError(
                __('No se permite el borrado de registros.')
            );
        }
        $modelClass = $this->getModelClass();
        $Obj = new $modelClass(array_map('urldecode', $pk));
        // si el registro que se quiere eliminar no existe error
        if(!$Obj->exists()) {
            return redirect(
                $request->getControllerUrl() . '/listar'.$filterListar
            )->withError(
                __('Registro (%(args)s) no existe, no se puede eliminar.',
                    [
                        'args' => implode(', ', $pk)
                    ]
                )
            );
        }
        try {
            $Obj->delete();
            return redirect($request->getControllerUrl() . '/listar' . $filterListar)
                ->withSuccess(
                    __('Registro (%(args)s) eliminado.'.
                        [
                            'args' => implode(', ', $pk)
                        ])
                );
        } catch (\Exception $e) {
            return redirect($request->getControllerUrl() . '/listar' . $filterListar)
                ->withError(
                    __('No se pudo eliminar el registro (%(args)s): %(error_message)s',
                        [
                            'args' => implode(', ', $pk),
                            'error_message' => $e->getMessage()
                        ]
                    )
                );
        }
    }

    /**
     * Método para descargar un archivo desde la base de datos
     */
    public function d(Request $request, $campo, ...$pk)
    {
        // si el campo que se solicita no existe error
        if (!isset($this->getModelClass()::$columnsInfo[$campo . '_data'])) {
            return redirect(
                $request->getControllerUrl() . '/listar'
            )->withError(
                __('Campo %(field)s no existe.',
                    [
                        'field' => $campo
                    ]
                )
            );
        }
        $modelClass = $this->getModelClass();
        $Obj = new $modelClass(...$pk);
        // si el registro que se quiere eliminar no existe error
        if(!$Obj->exists()) {
            return redirect(
                $request->getControllerUrl() . '/listar'
            )->withError(
                __('Registro (%(pks)s) no existe. No se puede obtener %(field)s.',
                    [
                        'pks' => implode(', ', $pk),
                        'field' => $campo
                    ]
                )
            );
        }
        if ((float)$Obj->{$campo.'_size'} == 0.0) {
            return redirect(
                $request->getControllerUrl() . '/listar'
            )->withError(
                __('No hay datos para el campo %(field)s en el registro (%(pks)s).',
                    [
                        'field' => $campo,
                        'pks' => implode(', ', $pk)
                    ]
                )
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
