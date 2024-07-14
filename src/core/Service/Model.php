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

use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Servicio para trabajar con modelos de la base de datos.
 *
 * La principal utilidad, es que permite instanciar un objeto solo una vez por
 * ejecución de la aplicación y con eso no se vuelve a ir a la base de datos.
 *
 * Se maneja una caché para los modelos instanciados.
 */
class Service_Model implements Interface_Service
{

    /**
     * Servicio de configuración.
     *
     * @var Service_Config
     */
    protected $configService;

    /**
     * Servicio de base de datos.
     *
     * @var Service_Database
     */
    protected $databaseService;

    /**
     * Caché en memoria para instancias de modelos.
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Constructor del servicio de modelos.
     *
     * @param Service_Config $configService
     */
    public function __construct(
        Service_Config $configService,
        Service_Database $databaseService
    )
    {
        $this->configService = $configService;
        $this->databaseService = $databaseService;
    }

    /**
     * Registra el servicio de modelos.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio de modelos.
     *
     * @return void
     */
    public function boot(): void
    {
    }

    /**
     * Finaliza el servicio de modelos.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Obtiene una instancia de un modelo.
     *
     * @param string $id Identificador de la clase del modelo.
     * @param mixed ...$pk Clave primaria del modelo.
     * @return mixed Instancia del modelo.
     */
    public function instantiate(string $id, $pk = null)
    {
        // Si no hay PK, entonces se está pidiendo una instancia sin ir a la
        // base de datos (objeto sin datos).
        if ($pk === null) {
            $modelClass = $this->getModelClass($id);
            return new $modelClass();
        }
        // Se normaliza la PK a un arreglo si no lo es.
        if (!is_array($pk)) {
            $pk = [$pk];
        }
        // Si hay PK, es una instancia de un modelo con datos de la base de
        // datos. En este caso se busca en caché primero.
        $cacheKey = $this->getCacheKey($id, $pk);
        if (!isset($this->cache[$cacheKey])) {
            $modelClass = $this->getModelClass($id);
            $this->cache[$cacheKey] = (new \ReflectionClass($modelClass))
                ->newInstanceArgs($pk)
            ;
        }
        // Entregar la instancia de la caché.
        return $this->cache[$cacheKey];
    }

    /**
     * Obtiene el nombre de la clase a partir de un ID.
     *
     * Permite realizar la búsqueda por un identificador en la configuración
     * de la clase o bien asumir que el $id es la misma clase que se desea
     * cargar.
     *
     * Se usa el ID para no tener que cargar estos identificadores como alias
     * de clases. Lo que permite "no ensuciar" la aplicación con alias que
     * pueden resultar confusos o generar problemas al no estar en formato FQCN.
     *
     * @param string $id Identificador de la clase.
     * @return string Clase que se encontró para el identificador.
     */
    protected function getModelClass(string $id): string
    {
        $key = 'models.alias.' . $id;
        $modelClass = $this->configService->get($key);
        return $modelClass ?? $id;
    }

    /**
     * Genera una clave única para el caché a partir del ID y PK del modelo.
     *
     * @param string $id Identificador del modelo.
     * @param array $pk Clave primaria del modelo.
     * @return string Clave única para el modelo (ID y PK).
     */
    protected function getCacheKey(string $id, array $pk): string
    {
        return $id . ':' . implode(':', $pk);
    }

    /**
     * Método mágico para manejar llamadas dinámicas a métodos.
     *
     * Se utiliza para poder proporcionar los métodos para creación de modelos
     * mágicamente usando métodos, en vez de llamar a instantiate().
     *
     * @param string $method Nombre del método llamado.
     * @param array $args Argumentos pasados al método.
     * @return mixed
     *
     * @throws \Exception Si el método no está definido en la configuración.
     */
    public function __call(string $method, $args)
    {
        // Si el método no parte con get se da un error.
        // Además, se asume que un ID no tendrá menos de 3 letras.
        if (!isset($method[5]) || strpos($method, 'get') !== 0) {
            throw new \Exception(__(
                'El método %s::%s() no existe.',
                __CLASS__,
                $method
            ));
        }
        // Instanciar el objeto a partir del ID.
        $id = substr($method, 3);
        return $this->instantiate($id, $args[0] ?? null);
    }

    /**
     * Método que determina la información del modelo a partir del nombre de la
     * clase del controlador.
     *
     * Se pueden especificar los datos ya conocidos en $model y no se
     * sobrescribirán (se mantendrán al retornar).
     *
     * @param string $controller Clase FQCN del controlador.
     * @param array $model Datos ya conocidos del modelo (no se sobrescribirán).
     * @return array Arreglo con índices: table, singular y plural.
     */
    public function getModelInfoFromController(string $controller, array $model = [])
    {
        $pos = strrpos($controller, '\\');
        $class = str_replace('Controller_', '', substr($controller, $pos + 1));
        $singular = \sowerphp\core\Utility_Inflector::singularize($class);
        if (empty($model['database'])) {
            $model['database'] = 'default';
        }
        if (empty($model['table'])) {
            $model['table'] = \Illuminate\Support\Str::snake($singular);
        }
        if (empty($model['namespace'])) {
            $model['namespace'] = '\\' . substr($controller, 0, $pos);
        }
        if (empty($model['singular'])) {
            $model['singular'] = $model['namespace'] . '\Model_' . $singular;
        }
        if (empty($model['plural'])) {
            $model['plural'] = $model['namespace'] . '\Model_' . $class;
        }
        return $model;
    }

    /**
     * Entrega la información de las columnas de un modelo.
     *
     * @param string $model Modelo para el cual se quiere obtener su información.
     * @return array Arreglo con la información de las columnas del modelo.
     */
    public function columns(string $model): array
    {
        return $model::$columnsInfo ?? [];
    }

    /**
     * Entrega las columnas que forman la PK del modelo.
     *
     * @return array Arreglo con las columnas que son la PK.
     */
    public function pk(string $model): array
    {
        $pk = [];
        foreach ($this->columns($model) as $column => $info) {
            if (!empty($info['pk'])) {
                $pk[] = $column;
            }
        }
        return $pk;
    }

    /**
     * Arma los filtros para la llave primaria de un modelo.
     *
     * @param string $model
     * @param array $id
     * @return array
     */
    public function buildPkFilters(string $model, array $id = []): array
    {
        $filters = [];
        $columns = $this->pk($model);
        foreach ($columns as $i => $column) {
            $value = $id[$column] ?? $id[$i] ?? null;
            if ($value !== null) {
                $filters[$column] = $value;
            }
        }
        return $filters;
    }

    /**
     * Arma el query builder y lo retorna según los parámetros pasados.
     *
     * @param array $model Arreglo con índices: database, table y singular.
     * @param array $parameters Parámetros de búsqueda y obtención de registros.
     * @return Illuminate\Database\Query\Builder
     */
    public function query(array $model, array $parameters): QueryBuilder
    {
        // Inicializar el query builder para el modelo.
        $query = $this->databaseService->connection($model['database'])
            ->table($model['table'])
        ;
        // Obtener la información de las columnas.
        $columnsInfo = $this->columns($model['singular']);
        // Aplicar filtros.
        $filters = array_merge(
            $parameters['filters'] ?? [],
            $model['filters'] ?? []
        );
        if (!empty($filters)) {
            foreach ($filters as $column => $value) {
                if (empty($columnsInfo[$column])) {
                    continue;
                }
                $query->whereSmartFilter(
                    $column,
                    $value,
                    $columnsInfo[$column]['type']
                );
            }
        }
        // Aplicar ordenamiento.
        if (!empty($parameters['sort'])) {
            foreach ($parameters['sort'] as $sort) {
                $query->orderBy($sort['column'], $sort['order']);
            }
        }
        // Aplicar paginación.
        if (
            isset($parameters['pagination']['page'])
            && isset($parameters['pagination']['limit'])
        ) {
            $page = $parameters['pagination']['page'];
            $limit = $parameters['pagination']['limit'];
            $query->skip(($page - 1) * $limit)->take($limit);
        }
        // Seleccionar las columnas deseadas si están especificadas.
        if (!empty($parameters['fields'])) {
            $query->select($parameters['fields']);
        }
        // Entregar query builder.
        return $query;
    }

    /**
     * Realiza una búsqueda y cuenta los recursos de un modelo en específico.
     *
     * @param array $model Arreglo con índices: database, table y singular.
     * @param array $parameters Parámetros de búsqueda y obtención de registros.
     * @return int Cantidad de recursos encontrados.
     */
    public function count(array $model, array $parameters): int
    {
        $query = $this->query($model, $parameters);
        return $query->count();
    }

    /**
     * Realiza una búsqueda y obtiene recursos de un modelo en específico.
     *
     * @param array $model Arreglo con índices: database, table y singular.
     * @param array $parameters Parámetros de búsqueda y obtención de registros.
     * @param bool $stdClass =true se entregará un objeto stdClass.
     * @return \Illuminate\Support\Collection|array
     */
    public function filter(array $model, array $parameters, $stdClass = false)
    {
        $query = $this->query($model, $parameters);
        // Obtener los resultados.
        $results = $query->get();
        if ($stdClass) {
            return $results;
        }
        // Crear instancias del modelo para retornar.
        $instances = [];
        foreach ($results as $result) {
            $instance = new $model['singular'];
            $instance->fill((array)$result);
            $instances[] = $instance;
        }
        return $instances;
    }

    /**
     * Obtener un recurso (registro) desde el modelo (base de datos).
     *
     * @param array $model Arreglo con índices: database, table y singular.
     * @param array $id Valores de la llave primaria del recurso que se desea
     * recuperar desde la base de datos.
     * @param bool $stdClass =true se entregará un objeto stdClass.
     * @return stdClass|Model
     */
    public function retrieve(array $model, array $id, bool $stdClass = false)
    {
        // Generar filtros con la PK.
        $filters = $this->buildPkFilters($model['singular'], $id);
        $results = $this->filter($model, ['filters' => $filters], $stdClass);
        $n_results = count($results);
        // DoesNotExist
        if ($n_results === 0) {
            throw new \Exception(__(
                'No se encontró un registro para %s::retrieve(%s).',
                $model['table'],
                implode(', ', array_values($filters))
            ), 404);
        }
        // MultipleObjectsReturned
        else if ($n_results > 1) {
            throw new \Exception(__(
                'Se obtuvo más de un registro para %s::retrieve(%s).',
                $model['table'],
                implode(', ', array_values($filters))
            ), 409);
        }
        // Se encontró exactamente un resultado (como se espera para una PK).
        return $results[0];
    }

    /**
     * Convierte un arreglo de parámetros de modelo en un string de URL.
     *
     * @param array $parameters Arreglo con los parámetros asociados al modelo.
     * @return string String de URL con los parámetros para buscar en modelos.
     */
    public function buildUrlParameters(array $parameters): string
    {
        $urlParameters = [];

        // Procesar los fields.
        if (!empty($parameters['fields'])) {
            $urlParameters['fields'] = implode(',', $parameters['fields']);
        }

        // Procesar los filtros.
        if (!empty($parameters['filters'])) {
            foreach ($parameters['filters'] as $key => $value) {
                $urlParameters['filter[' . $key . ']'] = $value;
            }
        }

        // Procesar la paginación.
        if (!empty($parameters['pagination'])) {
            $urlParameters['page'] = $parameters['pagination']['page'];
            $urlParameters['limit'] = $parameters['pagination']['limit'];
        }

        // Procesar el ordenamiento.
        if (!empty($parameters['sort'])) {
            $sortBy = [];
            $order = [];
            foreach ($parameters['sort'] as $sort) {
                $sortBy[] = $sort['column'];
                $order[] = $sort['order'];
            }
            $urlParameters['sort_by'] = implode(',', $sortBy);
            $urlParameters['order'] = implode(',', $order);
        }

        // Construir y retornar el string de la URL.
        return http_build_query($urlParameters);
    }

}
