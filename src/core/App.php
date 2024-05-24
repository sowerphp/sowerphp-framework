<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
 * Copyright (C) SowerPHP <https://www.sowerphp.org>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace sowerphp\core;

use Illuminate\Container\Container;

/**
 * Clase principal de la aplicación.
 * Se encarga de coordinar toda la ejecución de la aplicación:
 *   - Inicializar y gestionar el contenedor de servicios.
 *   - Inicializar la aplicación.
 *   - Registrar y configurar los servicios.
 *   - Despachar la ejecución de la aplicación.
 */
class App
{

    /**
     * Instancia única de la clase App.
     * @var App
     */
    private static App $instance;

    /**
     * Contenedor de servicios.
     * @var Container
     */
    protected Container $container;

    /**
     * Tipo de aplicación que se está ejecutando.
     */
    protected string $type;

    /**
     * Constructor protegido para evitar la instanciación directa.
     */
    protected function __construct()
    {
        // NOTE: ¡este método debe estar vacío siempre!
        // Cualquier lógica de inicialización deber ir en bootstrap()
    }

    /**
     * Método que se asegura de entregar una única instancia de la clase.
     */
    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
            self::$instance->bootstrap();
        }
        return self::$instance;
    }

    /**
     * Método que ejecuta la aplicación web.
     * Se encarga de despachar la aplicación HTTP o lanzar la CLI.
     */
    public function run(): int
    {
        $kernel = $this->container->make('kernel');
        $result = $kernel->handle();
        return $result;
    }

    /**
     * Obtiene un servicio del contenedor.
     * @param string $key Identificador del servicio.
     * @param array $parameters Parámetros para la creación de instancias.
     * @return mixed Retorna el servicio registrado bajo la clave especificada.
     * @throws \Exception Si el servicio solicitado no existe.
     */
    public function getService(string $key, array $parameters = [])
    {
        if (!$this->container->bound($key)) {
            throw new \Exception(sprintf(
                'El servicio %s no está registrado en el contenedor.',
                $key
            ));
        }
        return $this->container->make($key, $parameters);
    }

    /**
     * Inicializa la aplicación.
     */
    private function bootstrap(): void
    {
        // Definir el tiempo de inicio del script.
        define('TIME_START', microtime(true));

        // Definir tipo de aplicación que se está ejecutando.
        $this->type = php_sapi_name() === 'cli' ? 'console' : 'http';

        // Iniciar buffer.
        ob_start();

        // Asignar nivel de error máximo (para reportes previo a que se
        // asigne el valor real con la configuración).
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        // Crear el contenedor de servicios y registrar la aplicación.
        $this->container = new Container();
        $this->container->instance('app', $this);

        // Registrar e inicializar el resto de servicios de la aplicación.
        $this->registerServices();
        $this->bootServices();
    }

    /**
     * Registra todos los servicios obligatorios de la aplicación en
     * el contenedor de servicios.
     * NOTE: el orden de registro de los servicios es MUY importante.
     */
    private function registerServices(): void
    {
        // Registrar servicios del núcleo (compartidos).
        $this->registerService('layers', Service_Layers::class);
        $this->registerService('module', Service_Module::class);
        $this->registerService('config', Service_Config::class);
        $this->registerService('storage', Service_Storage::class);

        // Registrar servicios para ejecución en consola.
        if ($this->type == 'console') {
            $this->registerService('kernel', Service_Console_Kernel::class);
        }

        // Registrar servicios para solicitud HTTP
        else {
            $this->registerService('session', Service_Http_Session::class);
            $this->registerService('kernel', Service_Http_Kernel::class);
        }
    }

    /**
     * Inicializa todos los servicios necesarios después del registro.
     */
    private function bootServices(): void
    {
        foreach ($this->container->getBindings() as $key => $binding) {
            $service = $this->container->make($key);
            if ($service instanceof Interface_Service) {
                $service->boot();
            }
        }
    }

    /**
     * Registra un servicio en el contenedor.
     * @param string $key Identificador del servicio.
     * @param mixed $service Instancia del servicio o el nombre de la clase.
     * @param array $parameters Parámetros adicionales para el constructor del servicio.
     */
    private function registerService($key, $service, array $parameters = []): void
    {
        // Si el servicio ya está registrado no volver a registrarlo.
        if ($this->container->bound($key)) {
            return;
        }
        // Verificar que la clase del servicio exista.
        if (is_string($service) && !class_exists($service)) {
            throw new \InvalidArgumentException(sprintf(
                'La clase del servicio %s no existe.',
                $service
            ));
        }
        // Si $service es una instancia, usar instance para registrar.
        if (is_object($service)) {
            if ($service instanceof Interface_Service) {
                $service->register();
            }
            $this->container->instance($key, $service);
        }
        // Si $service es una clase, usar singleton para registrar.
        else {
            $this->container->singleton($key, function($app) use ($service, $parameters) {
                $instance = new $service($app, ...$parameters);
                if ($instance instanceof Interface_Service) {
                    $instance->register();
                }
                return $instance;
            });
        }
    }

}
