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

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\ValidationException;

/**
 * Clase con la solicitud del cliente.
 */
class Network_Request extends Request
{

    /**
     * URL completa, partiendo desde HTTP o HTTPS según corresponda.
     *
     * @var string
     */
    protected $fullUrlWithoutQuery;

    /**
     * Ruta base de la URL (base + uri arma el total del request).
     *
     * @var string
     */
    protected $baseUrlWithoutSlash;

    /**
     * URI usada para la consulta desde la aplicacion, o sea, sin base,
     * iniciando con "/".
     *
     * @var string
     */
    protected $requestUriDecoded;

    /**
     * Recurso al que se está accediendo a través de la API.
     *
     * @var string|null
     */
    protected $apiResource = null;

    /**
     * Configuración de la ruta de la solicitud HTTP.
     *
     * @var array
     */
    protected $routeConfig;

    /**
     * Servicio de la sesión del usuario en la solicitud.
     *
     * @var Service_Http_Session
     */
    protected $sessionService;

    /**
     * Factory para construir el objeto de validación.
     *
     * @var Illuminate\Validation\Factory
     */
    protected $validatorFactory;

    /**
     * Capturar el estado HTTP actual y entregar una instancia del objeto.
     *
     * @return Network_Request
     */
    public static function capture(): Network_Request
    {
        $request = parent::capture();
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $request->headers->set(
                'Authorization',
                $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            );
        }
        $request->setUserResolver(function() {
            return user();
        });
        return $request;
    }

    /**
     * Obtiene la instancia del servicio de sesión.
     *
     * @return Service_Http_Session
     */
    public function session()
    {
        if (!isset($this->sessionService)) {
            $this->sessionService = session();
        }
        return $this->sessionService;
    }

    /**
     * Método que determina la URL utiliza para acceder a la aplicación, esto
     * es: protocolo/esquema, dominio y ruta base dentro del dominio).
     *
     * @return string URL completa para acceder a la aplicación web.
     */
    public function getFullUrlWithoutQuery(): string
    {
        if (!isset($this->fullUrlWithoutQuery)) {
            if (empty($_SERVER['HTTP_HOST'])) {
                $url = (string)config('app.url');
            } else {
                if ($this->headers->get('X-Forwarded-Proto') == 'https') {
                    $scheme = 'https';
                } else {
                    $scheme = 'http' . (isset($_SERVER['HTTPS']) ? 's' : null);
                }
                $url = $scheme . '://' . $_SERVER['HTTP_HOST']
                    . $this->getBaseUrlWithoutSlash()
                ;
            }
            $this->fullUrlWithoutQuery = $url;
        }
        return $this->fullUrlWithoutQuery;
    }

    /**
     * Método que determina la ruta base dentro del dominio.
     *
     * @return string Base de la URL.
     */
    public function getBaseUrlWithoutSlash(): string
    {
        if (!isset($this->baseUrlWithoutSlash)) {
            if (!isset($_SERVER['REQUEST_URI'])) {
                $base = '';
            } else {
                $parts = explode('?', urldecode($_SERVER['REQUEST_URI']));
                $last = strrpos($parts[0], $this->getRequestUriDecoded());
                $base = $last !== false
                    ? substr($parts[0], 0, $last)
                    : $parts[0]
                ;
                $position = strlen($base) - 1;
                if ($position >= 0 && $base[$position] == '/') {
                    $base = substr($base, 0, -1);
                }
            }
            $this->baseUrlWithoutSlash = $base;
        }
        return $this->baseUrlWithoutSlash;
    }

    /**
     * Método que determina la solicitud utilizada para acceder a la página.
     *
     * @return string Solicitud completa para la página consultada.
     */
    public function getRequestUriDecoded(): string
    {
        if (!isset($this->requestUriDecoded)) {
            if (!isset($_SERVER['QUERY_STRING'])) {
                $request = '';
            } else {
                // Obtener ruta que se uso sin "/" (base) inicial.
                $uri = (
                    isset($_SERVER['QUERY_STRING'][0])
                    && $_SERVER['QUERY_STRING'][0] == '/'
                )
                    ? substr($_SERVER['QUERY_STRING'], 1)
                    : $_SERVER['QUERY_STRING']
                ;
                if (strpos($_SERVER['REQUEST_URI'], '/?' . $uri) !== false) {
                    $uri = '';
                }
                // Verificar si se pasaron variables GET.
                $inicio_variables_get = strpos($uri, '&');
                // Asignar URI.
                $request = $inicio_variables_get === false
                    ? $uri
                    : substr($uri, 0, $inicio_variables_get)
                ;
                // Agregar slash inicial de la URI.
                if (
                    !isset($request)
                    || (isset($request[0]) && $request[0] != '/')
                ) {
                    $request = '/' . $request;
                }
                // Decodificar URL.
                $request = urldecode($request);
            }
            $this->requestUriDecoded = $request;
            unset($_GET[$this->requestUriDecoded]);
        }
        return $this->requestUriDecoded;
    }

    /**
     * Método que asigna o entrega los parámetros de la solicitud.
     *
     * @param array|null $params
     * @return array
     */
    public function getRouteConfig(): array
    {
        // Si la configuración de la ruta no está asignada se asigna.
        if (!isset($this->routeConfig)) {
            // Obtener configuración básica de la ruta, a partir de la
            // información del router.
            $router = router();
            $this->routeConfig = $router->parse($this);
            $router->checkRouteConfig($this->routeConfig); // Lanza excepción.
            // Agregar información de las URL, que se deriva de los datos de la
            // ruta parseada previamente.
            $this->routeConfig['url'] = $this->getRouteConfigUrl(
                $this->routeConfig
            );
        }
        return $this->routeConfig;
    }

    protected function getRouteConfigUrl(array $config): array
    {
        $url = [];
        // Determinar parte de la URL que corresponde al módulo.
        $modules = explode('.', (string)$config['module']);
        $url['module'] = [];
        foreach ($modules as &$p) {
            $url['module'][] = \sowerphp\core\Utility_Inflector::underscore($p);
        }
        $url['module'] = $url['module']
            ? ('/' . implode('/', $url['module']))
            : ''
        ;
        // Determinar parte de la URL que correspone al controlador.
        $url['controller'] = $url['module'] . '/' . $config['controller'];
        $url['action'] = $url['controller'] . '/' . $config['action'];
        // Entregar las partes de la URL determinadas.
        return $url;
    }

    /**
     * Método que entrega la parte de la URL del módulo que está relacionado
     * con la solicitud.
     *
     * @return string
     */
    public function getModuleUrl(): string
    {
        return $this->getRouteConfig()['url']['module'];
    }

    /**
     * Método que entrega la parte de la URL del controlador que está
     * relacionado con la solicitud.
     *
     * @return string
     */
    public function getControllerUrl(): string
    {
        return $this->getRouteConfig()['url']['controller'];
    }

    /**
     * Método que entrega la parte de la URL de la acción que está relacionada
     * con la solicitud.
     *
     * @return string
     */
    public function getActionUrl(): string
    {
        return $this->getRouteConfig()['url']['action'];
    }

    /**
     * Establece y estandariza los parámetros de la solicitud asociados a un
     * modelo (filtros, paginación y ordenamiento), para ser usado en las
     * acciones del controlador asociada a modelos.
     *
     * Este método permite procesar los datos en un formato estándar sencillo y
     * en el formato de Datatables (un poco más complejo).
     *
     * @return array Arreglo con los parámetros asociados a la solicitud.
     */
    public function getModelParametersFromUrl(): array
    {
        $format = $this->input('format');
        if (!$format) {
            $format = $this->has('draw') ? 'datatables' : 'standard';
        }
        $method = 'getModelParametersFromUrl' . ucfirst($format);
        if (!method_exists($this, $method)) {
            $method = 'getModelParametersFromUrlStandard';
        }
        return array_merge([
            'format' => $format,
            'stdClass' => (bool)$this->input('stdClass', false),
        ], $this->$method());
    }

    /**
     * Obtener parámetros para buscar en recursos (modelos) usando el formato
     * de parámetros de la URL estándar.
     *
     * @return array
     */
    protected function getModelParametersFromUrlStandard(): array
    {
        $minLimit = config('app.ui.pagination.registers_min', 10);
        $maxLimit = config('app.ui.pagination.registers_max', 100);
        $defaultLimit = config('app.ui.pagination.registers', 20);
        // Determinar parámetros.
        $fields = $this->input('fields');
        $fields = $fields ? explode(',', $fields) : [];
        $filters = (array)$this->input('filter');
        $sort = [];
        $sort_by = $this->input('sort_by');
        if ($sort_by) {
            $sortColumns = explode(',', $sort_by);
            $order = $this->input('order');
            $sortOrders = $order ? explode(',', $order) : [];
            foreach ($sortColumns as $index => $column) {
                $sort[] = [
                    'column' => $column,
                    'order' => $sortOrders[$index] ?? 'asc',
                ];
            }
        }
        $pagination = [
            'page' => (int)$this->input('page', 1),
            'limit' => max($minLimit, min($maxLimit, $this->input('limit', $defaultLimit))),
        ];
        // Entregar parámetros.
        return compact('fields', 'filters', 'pagination', 'sort');
    }

    /**
     * Obtener parámetros para buscar en recursos (modelos) usando el formato
     * de parámetros de la URL de Datatables.
     *
     * @return array
     */
    protected function getModelParametersFromUrlDatatables(): array
    {
        $minLimit = config('app.ui.pagination.registers_min', 10);
        $maxLimit = config('app.ui.pagination.registers_max', 100);
        $defaultLimit = config('app.ui.pagination.registers', 20);
        // Determinar parámetros.
        $fields = [];
        $filters = [];
        foreach ((array)$this->input('columns') as $column) {
            $fields[] = $column['data'];
            if (!empty($column['search']['value'])) {
                $filters[$column['data']] = $column['search']['value'];
            }
        }
        $sort = [];
        foreach ($this->input('order', []) as $order) {
            $sort[] = [
                'column' => $this->input('columns.' . $order['column'] . '.data'),
                'order' => $order['dir'] ?? null,
            ];
        }
        $limit = max($minLimit, min($maxLimit, $this->input('length', $defaultLimit)));
        $pagination = [
            'page' => ($this->input('start', 0) / $limit) + 1,
            'limit' => $limit,
        ];
        // Entregar parámetros.
        return compact('fields', 'filters', 'pagination', 'sort');
    }

    /**
     * Determina si el Request está inicializado con datos HTTP.
     *
     * Esto se determina verificando si hay un método HTTP y headers, lo cual
     * es indicativo de una solicitud HTTP.
     *
     * @return bool
     */
    public function isHttpRequest(): bool
    {
        return $this->method() !== null && $this->headers->count() > 0;
    }

    /**
     * Determina si la solicitud HTTP es o no a la API (web o api).
     *
     * Esto se determina de 2 formas:
     *   - La solicitud parte por /api/
     *   - La solicitud acepta como respuesta un JSON
     *
     * @return boolean
     */
    public function isApiRequest(): bool
    {
        $api_prefix = strpos($this->getRequestUriDecoded(), '/api/') === 0;
        $accept_header = $this->headers->get('Accept');
        $accept_json = Str::contains($accept_header, 'application/json');
        return $api_prefix || $accept_json;
    }

    /**
     * Entrega el recurso al que se está accediendo a través de la API.
     */
    public function getApiResource(): string
    {
        if (!isset($this->apiResource)) {
            $routeConfig = $this->getRouteConfig();
            $find =
                '/' . $routeConfig['controller'] . '/'
                . (
                    !empty($routeConfig['parameters'][0])
                        ? $routeConfig['parameters'][0]
                        : ''
                )
            ;
            $pos = strrpos($this->getRequestUriDecoded(), $find) + strlen($find);
            $this->apiResource = substr($this->getRequestUriDecoded(), 0, $pos);
        }
        return $this->apiResource;
    }

    /**
     * Valida los datos de la solicitud según las reglas definidas.
     *
     * Crea una instancia del `ValidatorFactory` utilizando los servicios de
     * traducción y eventos. Valida los datos de la solicitud y lanza una
     * `ValidationException` si la validación falla.
     *
     * @param array $rules Reglas de validación a aplicar.
     * @param array $messages Mensajes personalizados de validación.
     * @param array $customAttributes Atributos personalizados de los campos.
     * @return array Datos validados.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): array
    {
        if (!isset($this->validatorFactory)) {
            $this->validatorFactory = new ValidatorFactory(
                app('translator'),
                app()->getContainer()
            );
        }
        $validator = $this->validatorFactory->make(
            $this->all(),
            $rules,
            $messages,
            $customAttributes
        );
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        return $validator->validated();
    }

    /**
     * Obtiene los datos validados según los parámetros y reglas definidas.
     *
     * Este método extiende la funcionalidad del método `validate()` para
     * manejar configuraciones adicionales como valores por defecto y
     * callbacks. Itera sobre los parámetros solicitados, aplica las reglas
     * de validación y maneja los mensajes de error personalizados. Si la
     * validación falla, captura la excepción, convierte los errores en
     * un string y relanza la excepción.
     *
     * @param array $params Arreglo de parámetros con sus configuraciones.
     * @param string|null $errorMessage Variable para almacenar el mensaje de
     * error completo (la suma de mensajes) si la validación falla.
     * @return array Datos validados con sus valores por defecto si corresponden.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function getValidatedData(array $params, &$errorMessage = null): array
    {
        // Argumentos para el método Request::validate().
        $validateArgs = [
            'rules' => [],
            'messages' => [],
            'customAttributes' => [],
        ];
        // Iterar cada uno de los parámetros solicitados.
        foreach ($params as $param => &$config) {
            // Si se pasó el nombre del parámetro solamente se corrige y se
            // arma como corresponde.
            if (is_int($param)) {
                $param = $config;
                $config = null;
            }
            // Si la configuración no es arreglo, entonces se pasó solo el
            // valor por defecto. Entonces se armga el arreglo config con dicho
            // valor por defecto.
            if (!is_array($config)) {
                $config = [
                    'default' => $config,
                ];
            }
            // Se asegura que la configuración del parámetro tenga todos los
            // índices con su configuración por defecto.
            $config = array_merge([
                'name' => $param,
                'default' => null,
                'rules' => 'nullable',
                'callback' => null,
                'messages' => [],
            ], $config);
            // Armar argumentos para el método Request::validate() que validará
            // lo solicitado.
            $validateArgs['rules'][$param] = $config['rules'];
            foreach ($config['messages'] as $key => $value) {
                $validateArgs['messages'][$param . '.'. $key] = $value;
            }
            $validateArgs['customAttributes'][$param] = $config['name'];
            // Armar datos por defecto.
            $default[$param] = $config['default'];
        }
        // Obtener los datos validados.
        try {
            $data = array_merge($default, $this->validate(
                $validateArgs['rules'],
                $validateArgs['messages'],
                $validateArgs['customAttributes']
            ));
        } catch (ValidationException $e) {
            $errorMessage = [];
            foreach ($e->errors() as $field => $messages) {
                $errorMessage[] = implode(' ', $messages);
            }
            $errorMessage = implode(' ', $errorMessage);
            throw $e;
        }
        // Aplicar callback a los datos (solo si no son el valor por defecto).
        foreach ($data as $param => &$value) {
            if ($value !== $params[$param]['default']) {
                if (
                    isset($params[$param]['callback'])
                    && is_callable($params[$param]['callback'])
                ) {
                    $value = call_user_func($params[$param]['callback'], $value);
                }
            }
        }
        // Entregar los datos encontrados.
        return $data;
    }

    /**
     * Determina desde qué IP se realiza la solicitud.
     *
     * @param bool $get_from_proxy Indica si se debe obtener el resultado desde
     * un proxy.
     * @return string IP del visitante.
     */
    public function fromIp(bool $get_from_proxy = false): string
    {
        if ($get_from_proxy && getenv('HTTP_X_FORWARDED_FOR')) {
            $ips = explode(', ', getenv('HTTP_X_FORWARDED_FOR'));
            return $ips[count($ips) - 1];
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Determina el host desde dónde se realiza la solicitud.
     *
     * @param bool $get_from_proxy Indica si se debe obtener el resultado desde
     * un proxy.
     * @return string Host del visitante.
     */
    public function fromHostname(bool $get_from_proxy = false): ?string
    {
        $host = gethostbyaddr($this->fromIp($get_from_proxy));
        return $host ?: null;
    }

    /**
     * Obtiene la ubicación asociada a la dirección IP desde dónde se realizó
     * la solicitud a la aplicación.
     *
     * Si está disponible GeoIP localmente en el servidor que ejecuta la
     * aplicación se utilizará, en caso contrario se usará el servicio
     * https://freegeoip.net (máximo de 10.000 consultas por hora).
     *
     * @return array Arreglo con los datos de la geolocalización de la solicitud.
     */
    public function fromGeolocation(): ?array
    {
        $ip = $this->fromIp();
        if (function_exists('geoip_record_by_name')) {
            $location = @geoip_record_by_name($ip);
        }
        if (!isset($location)) {
            $response = \sowerphp\core\Network_Http_Socket::get(
                'https://freegeoip.net/json/' . $ip
            );
            $location = $response['status']['code'] == 200
                ? (array)json_decode($response['body'])
                : null
            ;
        }
        return $location ?: null;
    }

}
