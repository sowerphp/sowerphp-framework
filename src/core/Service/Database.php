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

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;

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
        // Configurar el resolver para usar la clase personalizada según cada
        // uno de los drivers.
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
            $connections = $this->capsule->getDatabaseManager()->getConnections();
            foreach ($connections as $connection) {
                $connection->disconnect();
            }
        }
    }

}
