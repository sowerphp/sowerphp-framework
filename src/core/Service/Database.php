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

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

/**
 * Servicio de base de datos.
 *
 * Gestiona las conexiones a bases de datos y proporciona métodos
 * para obtener y crear conexiones utilizando Illuminate Database.
 */
class Service_Database implements Interface_Service
{

    /**
     * Servicio de configuración.
     *
     * @var Service_Config
     */
    protected $configService;

    /**
     * Instancia de Capsule Manager.
     *
     * @var Capsule
     */
    protected $capsule;

    /**
     * Constructor de la clase.
     *
     * @param Service_Config $configService Servicio de configuración.
     */
    public function __construct(App $app, Service_Config $configService)
    {
        $this->configService = $configService;
        $this->capsule = new Capsule($app->getContainer());
    }

    /**
     * Registra el servicio de base de datos.
     *
     * @return void
     */
    public function register(): void
    {
        // Código de registro del servicio.
    }

    /**
     * Inicializa el servicio de base de datos.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->configureResolvers();
        $this->registerMacros();
    }

    /**
     * Configurar el resolver para usar la clase personalizada según cada uno
     * de los drivers.
     *
     * @return void
     */
    protected function configureResolvers(): void
    {
        $drivers = ['pgsql', 'mysql', 'sqlite'];
        foreach ($drivers as $driver) {
            $customClass = '\sowerphp\core\Database_Connection_Custom_'
                . ucfirst($driver)
            ;
            Connection::resolverFor(
                $driver,
                function ($connection, $database, $prefix, $config) use ($customClass) {
                    return new $customClass(
                        $connection,
                        $database,
                        $prefix,
                        $config
                    );
                }
            );
        }
    }

    /**
     * Método que crea y registra macros en el query builder.
     *
     * @return void
     */
    protected function registerMacros(): void
    {
        // Registrar la macro whereIlike.
        Builder::macro('whereIlike', function($column, $value) {
            $value = '%' . strtolower($value) . '%';
            return $this->whereRaw('LOWER('.$column.') LIKE ?', [$value]);
        });
        // Registrar la macro whereSmartFilter.
        Builder::macro('whereSmartFilter', function(string $column, $value, array $config = []) {
            // Si no se indicó la tabla en la columna es la misma tabla de la
            // query.
            if (strpos($column, '.') === false) {
                $column = $this->from . '.' . $column;
            }
            // Si el valor es '!null' se compara contra IS NOT NULL.
            if ($value == '!null') {
                return $this->whereNotNull($column);
            }
            // Si el valor es null o 'null' se compara contra IS NULL.
            else if ($value === null || $value == 'null') {
                return $this->whereNull($column);
            }
            // Si es un campo relacionado mediante llave foránea se hace join.
            else if (!empty($config['foreign_key'])) {
                // Si no se ha indicado cómo buscar en la llave foránea se hace
                // una búsqueda por igualdad con el campo y su valor en la
                // misma tabla (modelo).
                if (!is_string($config['searchable'])) {
                    return $this->whereSmartFilter(
                        $column,
                        $value,
                        array_merge($config, ['foreign_key' => null])
                    );
                }
                // JOIN con la tabla relacionada de la FK.
                $this->join(
                    $config['to_table'],
                    $config['to_table'] . '.' . $config['to_field'],
                    '=',
                    $column
                );
                // Aplicar el filtro en las columnas de la tabla relacionada.
                $this->where(function($query) use ($config, $value) {
                    $searchable = explode('|', $config['searchable']);
                    foreach ($searchable as $info) {
                        list($fkColumn, $cast) = explode(':', $info);
                        $fkConfig = ['cast' => $cast];
                        $query->orWhere(function($subQuery) use ($config, $fkColumn, $value, $fkConfig) {
                            $subQuery->whereSmartFilter(
                                $config['to_table'] . '.' . $fkColumn,
                                $value,
                                $fkConfig
                            );
                        });
                    }
                });
                return $this;
            }
            // Si es un campo de texto se filtrará con la macro ILIKE (que usa LIKE)
            else if (in_array($config['cast'], ['string'])) {
                return $this->whereIlike($column, $value);
            }
            // Si es un tipo fecha con hora se usará LIKE.
            else if (in_array($config['cast'], ['date', 'datetime'])) {
                $value = $value . '%';
                return $this->whereRaw('CAST('.$column.' AS TEXT) LIKE ?', [$value]);
            }
            // Si es un campo número entero se castea.
            else if (in_array($config['cast'], ['integer'])) {
                if (is_numeric($value)) {
                    return $this->where($column, '=', (int)$value);
                }
                return $this;
            }
            // Si es un campo número decimal se castea.
            else if (in_array(explode(':', $config['cast'])[0], ['decimal'])) {
                if (is_numeric($value)) {
                    return $this->where($column, '=', (float)$value);
                }
                return $this;
            }
            // Si es un campo booleano se castea.
            else if (in_array($config['cast'], ['boolean'])) {
                if ($value === false || $value === 'false') {
                    $value = 0;
                }
                if ($value === true || $value === 'true') {
                    $value = 1;
                }
                if (is_numeric($value)) {
                    return $this->where($column, '=', (bool)$value);
                }
                return $this;
            }
            // Si es cualquier otro caso se comparará con una igualdad.
            else {
                return $this->where($column, '=', $value);
            }
        });
        // Registrar la macro whereGlobalSearch.
        Builder::macro('whereGlobalSearch', function ($fields, $value) {
            // Aplicar los JOIN necesarios en el contexto principal.
            foreach ($fields as $field => $config) {
                if (!empty($config['foreign_key']) && is_string($config['searchable'])) {
                    $this->join(
                        $config['to_table'],
                        $config['to_table'] . '.' . $config['to_field'],
                        '=',
                        $this->from . '.' . $config['db_column']
                    );
                }
            }
            // Aplicar los filtros globales.
            $this->where(function($query) use ($fields, $value) {
                foreach ($fields as $field => $config) {
                    $query->orWhere(function($subQuery) use ($field, $value, $config) {
                        $subQuery->whereSmartFilter($field, $value, $config);
                    });
                }
            });
            return $this;
        });
    }

    /**
     * Finaliza el servicio de base de datos.
     *
     * @return void
     */
    public function terminate(): void
    {
        // Cerrar la conexión de todas las base de datos.
        $this->disconnect();
    }

    /**
     * Obtiene una conexión a la base de datos.
     *
     * @param string|null $name Nombre de la conexión.
     * @return \Illuminate\Database\Connection
     */
    public function connection(?string $name = null): Connection
    {
        if (!$name || $name == 'default') {
            $name = $this->configService->get('database.default');
        }
        return $this->capsule->getConnection($name);
    }

    /**
     * Cerrar una conexión a la base de datos o todas si no se especifica una.
     *
     * @param string|null $name Nombre de la conexión.
     */
    public function disconnect(?string $name = null): void
    {
        // Cerrar una conexión específica.
        if ($name) {
            $this->connection($name)->disconnect();
        }
        // Cerrar todas las conexiones.
        else {
            $connections = $this->getDatabaseManager()->getConnections();
            foreach ($connections as $connection) {
                $connection->disconnect();
            }
        }
    }

    /**
     * Entrega el administrador principal de conexiones a la base de datos.
     *
     * Es el objeto que gestiona las conexiones y proporciona métodos para
     * acceder a ellas.
     *
     * @return \Illuminate\Database\DatabaseManager
     */
    public function getDatabaseManager(): DatabaseManager
    {
        return $this->capsule->getDatabaseManager();
    }

}
