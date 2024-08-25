<?php

/**
 * SowerPHP: Simple and Open Web Ecosystem Reimagined for PHP.
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
     * Servicio de módulos.
     *
     * @var Service_Module
     */
    protected $moduleService;

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
        Service_Module $moduleService,
        Service_Database $databaseService
    )
    {
        $this->configService = $configService;
        $this->moduleService = $moduleService;
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
     * @param string $model Identificador de la clase del modelo.
     * @param array ...$id Clave primaria del modelo.
     * @return Model Instancia del modelo.
     */
    public function instantiate(string $model, ...$id): Model
    {
        $modelClass = $this->getModelClass($model);
        // Si no hay PK, entonces se está pidiendo una instancia sin ir a la
        // base de datos (objeto sin datos).
        if (empty($id)) {
            return new $modelClass();
        }
        // Si hay PK, es una instancia de un modelo con datos de la base de
        // datos. En este caso se busca en caché primero.
        $cacheKey = $this->getCacheKey($model, $id);
        if (!isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = new $modelClass(...$id);
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
     * @param string $modelId Identificador de la clase.
     * @return string Clase que se encontró para el identificador.
     */
    protected function getModelClass(string $modelId): string
    {
        $key = 'models.alias.' . $modelId;
        $modelClass = $this->configService->get($key);
        return $modelClass ?? $modelId;
    }

    /**
     * Genera una clave única para el caché a partir del ID y PK del modelo.
     *
     * @param string $modelId Identificador del modelo.
     * @param array $id Clave primaria del modelo.
     * @return string Clave única para el modelo (ID y PK).
     */
    protected function getCacheKey(string $modelId, array $id): string
    {
        return $modelId . ':' . implode(':', $id);
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
     * Método que determina el nombre del modelo asociado a un controlador.
     *
     * @param string $controller Clase FQCN del controlador.
     * @return string Clase del modelo singular asociado al controlador.
     */
    public function getModelFromController(string $controller): string
    {
        $pos = strrpos($controller, '\\');
        $class = str_replace('Controller_', '', substr($controller, $pos + 1));
        $singular = \sowerphp\core\Utility_Inflector::singularize($class);
        $namespace = '\\' . substr($controller, 0, $pos);
        $model = $namespace . '\Model_' . $singular;
        return $model;
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

    /**
     * Obtiene una lista de modelos instanciados vacíos.
     *
     * Este método solo obtendrá modelos que hereden (directa o indirectamente)
     * de la clase base de modelos del framework \sowerphp\core\Model.
     *
     * @param string|null $module Nombre del módulo para buscar modelos solo en
     * uno específico. `''` para buscar modelos solo en las capas. `null` (por
     * defecto) para buscar modelos en todas las posibles rutas de búsqueda de
     * la aplicación (capas y módulos).
     * @return array Arreglo con los modelos instanciados vacíos.
     */
    public function getEmptyInstancesFromAllSingularModels(?string $module = null): array
    {
        $searchDir = 'Model';
        $classes = $this->moduleService->searchAndLoadClasses($searchDir, $module);
        $models = [];
        $parentClass = Model::class;
        foreach ($classes as $className => $classInfo) {
            $reflectionClass = new \ReflectionClass($classInfo['fqcn']);
            if (!$reflectionClass->isSubclassOf($parentClass)) {
                continue;
            }
            $instance = $reflectionClass->newInstance();
            $models[$className] = array_merge($classInfo, [
                'instance' => $instance,
            ]);
        }
        return $models;
    }

}
