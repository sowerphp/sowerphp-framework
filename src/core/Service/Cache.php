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

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Redis\RedisManager;

/**
 * Servicio de caché.
 *
 * Gestiona el almacenamiento en caché de datos, proporcionando
 * métodos para almacenar, recuperar y eliminar datos de la caché.
 */
class Service_Cache implements Interface_Service
{

    /**
     * Aplicación.
     *
     * @var App
     */
    protected $app;

    /**
     * Instancia de CacheManager.
     *
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * Estadísticas de las llamadas a los métodos de la caché.
     *
     * Define:
     *   - set: cantidad de escrituras solicitadas a la caché.
     *   - assigned: cantidad de escrituras satisfactorias realizadas a la caché.
     *   - get: cantidad de lecturas solicitadas a la caché.
     *   - retrieved: cantidad de lecturas satisfactorias realizadas a la caché (hits).
     *
     * @var array
     */
    protected $stats = [
        'set' => 0,
        'assigned' => 0,
        'get' => 0,
        'retrieved' => 0,
    ];

    /**
     * Constructor de la clase.
     *
     * @param App $app Aplicación.
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Registra el servicio de caché.
     *
     * @return void
     */
    public function register(): void
    {
        $container = $this->app->getContainer();
        // Registrar redis en el contenedor de la aplicación si no existe.
        if (!$container->bound('redis')) {
            $this->app->getContainer()->singleton('redis', function ($app) {
                $config = $app->make('config');
                $redisClient = $config['database.redis.client'];
                $redisConnection = $config['cache.stores.redis.connection'];
                $redisConfig = $config['database.redis.' . $redisConnection];
                return new RedisManager($app, $redisClient, [
                    $redisConnection => $redisConfig,
                ]);
            });
        }
        // Instanciar administrador de caché.
        $this->cacheManager = new CacheManager(
            $this->app->getContainer()
        );
    }

    /**
     * Inicializa el servicio de caché.
     *
     * @return void
     */
    public function boot(): void
    {
    }

    /**
     * Finaliza el servicio de caché.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Obtiene un almacén de caché.
     *
     * @param string|null $name Nombre del almacén.
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function store(?string $name = null): Repository
    {
        $name = $name ?? $this->cacheManager->getDefaultDriver();
        return $this->cacheManager->store($name);
    }

    /**
     * Método que guarda en memoria un valor a ser cacheado.
     *
     * @param string $key Clave que tendrá el elemento en la caché.
     * @param mixed $value Valor del elemento en la caché.
     * @param int $expires Tiempo en segundos que se debe almacenar en memoria.
     * @return bool =true si se pudo asignar el elemento en la caché.
     */
    public function set(string $key, $value, int $expires = 600): bool
    {
        $this->stats['set']++;
        $status = $this->store()->put($key, $value, $expires);
        if ($status) {
            $this->stats['assigned']++;
        }
        return $status;
    }

    /**
     * Método para recuperar un elemento desde la caché.
     *
     * @param string $key Clave del elemento que se desea recuperar desde la caché.
     * @return mixed|null Elemento solicitado o =null si no se pudo recuperar.
     */
    public function get(string $key)
    {
        $this->stats['get']++;
        $result = $this->store()->get($key);
        if ($result !== null) {
            $this->stats['retrieved']++;
        }
        return $result;
    }

    /**
     * Entrega las estadísticas globales del uso de la caché.
     *
     * Las estadísticas incluyen el "hits ratio" que corresponde a:
     *   hits_ratio = #retrieved / #get
     *
     * @return array Arreglo con las estadísticas globales del uso de la caché.
     */
    public function getStats(): array
    {
        $this->stats['hitsRatio'] = $this->stats['get']
            ? round($this->stats['retrieved'] / $this->stats['get'], 6)
            : 0
        ;
        $this->stats['hitsPercentage'] = round($this->stats['hitsRatio'] * 100, 2);
        return $this->stats;
    }

    /**
     * Cualquier método que no esté definido en el servicio será llamado en el
     * store por defecto de caché.
     */
    public function __call($method, $parameters)
    {
        try {
            return call_user_func_array([$this->store(), $method], $parameters);
        } catch (\Exception $e) {
            throw new \Exception(__(
                'Error al ejecutar consulta al almacenamiento de la caché: %s.',
                $e->getMessage()
            ));
        }
    }

}
