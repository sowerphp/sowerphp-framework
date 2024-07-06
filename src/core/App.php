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
     *
     * @var App
     */
    protected static $instance;

    /**
     * Servicios del núcleo (comúnes) que son cargados por defecto.
     *
     * @var array
     */
    protected $defaultCoreServices = [
        'invoker' => Service_Invoker::class,
        'layers' => Service_Layers::class,
        'module' => Service_Module::class,
        'storage' => Service_Storage::class,
        'config' => Service_Config::class,
        'cache' => Service_Cache::class,
        'database' => Service_Database::class,
        'lang' => Service_Lang::class,
        //'mail' => Service_Mail::class,
        //'http_client' => Service_Http_Client::class,
        'view' => Service_View::class,
    ];

    /**
     * Servicios cuando la aplicación se ejecuta en modo consola que son
     * cargados por defecto.
     *
     * @var array
     */
    protected $defaultConsoleServices = [
        'kernel' => Service_Console_Kernel::class,
    ];

    /**
     * Servicios cuando la aplicación se ejecuta en modo HTTP que son
     * cargados por defecto.
     *
     * @var array
     */
    protected $defaultHttpServices = [
        'kernel' => Service_Http_Kernel::class,
        'router' => Service_Http_Router::class,
        'redirect' => Service_Http_Redirect::class,
        'session' => Service_Http_Session::class,
        //'auth' => Service_Http_Auth::class,
    ];

    /**
     * Contenedor de servicios.
     *
     * @var Container
     */
    protected $container;

    /**
     * Tipo de aplicación que se está ejecutando.
     */
    protected $type;

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
    public static function getInstance(): ?self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
            try {
                self::$instance->bootstrap();
            } catch (\Throwable $throwable) {
                self::$instance->handleThrowable($throwable);
                return null;
            }
        }
        return self::$instance;
    }

    /**
     * Método que ejecuta la aplicación web.
     * Se encarga de despachar la aplicación HTTP o lanzar la CLI.
     *
     * @return int Código de ejecución de la aplicación.
     */
    public function run(): int
    {
        try {
            $kernel = $this->container->make('kernel');
        } catch (\Throwable $throwable) {
            $result = $this->handleThrowable($throwable);
        }
        try {
            $result = $kernel->handle();
        } catch (\Throwable $throwable) {
            $result = $kernel->handleThrowable($throwable);
        }
        try {
            $this->terminateServices();
        } catch (\Throwable $throwable) {
            $result = $kernel->handleThrowable($throwable);
        }
        return $result;
    }

    /**
     * Obtiene el contenedor de servicios de la aplicación.
     *
     * Permite pasar el contenedor a otros servicios o clases que lo requieran,
     * evitando tener que crear un nuevo contenedor en cada clase. Esto asegura
     * que todas las dependencias estén centralizadas y gestionadas desde un
     * solo lugar. Garantizando que todas las partes de la aplicación usen las
     * mismas instancias y configuraciones. Facilita la configuración, la
     * inyección y la resolución de dependencias, haciendo que el sistema sea
     * más fácil de mantener y escalar.
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Obtiene un servicio del contenedor.
     *
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
     * Manejador de errores y excepciones cuando el kernel aún no está
     * disponible. Esto mostrará un error "feo", sin embargo, en producción
     * no debería ocurrir un error de este tipo ya que pruebas adecuadas hasta
     * que el kernel esté disponible asegurarán que el error se maneje por el
     * kernel de una manera más amigable con el usuario.
     *
     * @param \Throwable $throwable
     * @return int
     */
    protected function handleThrowable(\Throwable $throwable): int
    {
        // Variables con los datos del error o excepción.
        $type = $throwable instanceof \Error ? 'error' : 'excepción';
        $class = get_class($throwable);
        $message = $throwable->getMessage();
        $code = $throwable->getCode();
        $severity = $throwable->severity ?? LOG_ERR;
        $file = $throwable->getFile();
        $line = $throwable->getLine();
        $trace = $throwable->getTraceAsString();
        // Armar mensaje con el detalle del error o excepción.
        $error = sprintf(
            'Se capturó %s (%s de nivel %s):' . "\n\n"
                . ' %s' . "\n\n"
                . 'En %s:%d.' . "\n\n"
                . 'Traza completa:' . "\n\n"
                . '%s'
            , $class, $type, $severity, $message, $file, $line, $trace
        );
        // Generar mensaje con el error o excepción.
        header('Content-Type: text/plain; charset=UTF-8');
        echo $error;
        return $code ? 1 : 0;
    }

    /**
     * Inicializa la aplicación.
     */
    protected function bootstrap(): void
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
        $this->registerService('app', $this); // No es un servicio realmente.

        // Registrar e inicializar el resto de servicios de la aplicación.
        $this->registerServices();
        $this->bootServices();

        // Inicializar cada capa con su archivo bootstrap personalizado.
        $this->getService('layers')->loadFilesReverse([
            '/App/bootstrap.php',
        ]);
    }

    /**
     * Registra todos los servicios obligatorios de la aplicación en
     * el contenedor de servicios.
     */
    protected function registerServices(): void
    {
        $this->registerCoreServices();
        if ($this->type == 'console') {
            $this->registerConsoleServices();
        } else {
            $this->registerHttpServices();
        }
        $this->executeRegisterMethodOnAllServices();
    }

    /**
     * Registrar los servicios a partir de una configuración.
     *
     * @param array $services
     */
    protected function registerServicesFromConfig(array $services): void
    {
        foreach ($services as $key => $service) {
            $key = is_string($key)
                ? $key
                : (is_object($service) ? get_class($service) : $service)
            ;
            $this->registerService($key, $service);
        }
    }

    /**
     * Registrar servicios del núcleo (compartidos).
     */
    protected function registerCoreServices(): void
    {
        $config = $this->defaultCoreServices;
        $this->registerServicesFromConfig($config);
    }

    /**
     * Registrar servicios para ejecución en consola.
     */
    protected function registerConsoleServices(): void
    {
        $config = $this->defaultConsoleServices;
        $this->registerServicesFromConfig($config);
    }

    /**
     * Registrar servicios para solicitud HTTP.
     */
    protected function registerHttpServices(): void
    {
        $config = $this->defaultHttpServices;
        $this->registerServicesFromConfig($config);
    }

    /**
     * Ejecutar método register() en cada servicio.
     */
    protected function executeRegisterMethodOnAllServices(): void
    {
        $registered = [];
        foreach ($this->container->getBindings() as $key => $binding) {
            $service = $this->container->make($key);
            $serviceClass = get_class($service);
            if (
                $service instanceof Interface_Service
                && !isset($registered[$serviceClass])
            ) {
                $service->register();
                $registered[$serviceClass] = true;
            }
        }
    }

    /**
     * Inicializa todos los servicios necesarios después del registro.
     */
    protected function bootServices(): void
    {
        $initialized = [];
        foreach ($this->container->getBindings() as $key => $binding) {
            $service = $this->container->make($key);
            $serviceClass = get_class($service);
            if (
                $service instanceof Interface_Service
                && !isset($initialized[$serviceClass])
            ) {
                $service->boot();
                $initialized[$serviceClass] = true;
            }
        }
    }

    /**
     * Finaliza todos los servicios registrados al terminar la ejecución.
     */
    protected function terminateServices(): void
    {
        $terminated = [];
        foreach ($this->container->getBindings() as $key => $binding) {
            $service = $this->container->make($key);
            $serviceClass = get_class($service);
            if (
                $service instanceof Interface_Service
                && !isset($terminated[$serviceClass])
            ) {
                $service->terminate();
                $terminated[$serviceClass] = true;
            }
        }
    }

    /**
     * Registra un servicio en el contenedor.
     *
     * @param string $key Identificador del servicio.
     * @param mixed $service Instancia del servicio o el nombre de la clase.
     */
    public function registerService($key, $service): void
    {
        // Si el servicio ya está registrado no volver a registrarlo.
        if ($this->container->bound($key)) {
            return;
        }

        // Si $service es una instancia, usar instance para registrar.
        if (is_object($service)) {
            if ($service instanceof Interface_Service) {
                $service->register();
            }
            $this->container->instance($key, $service);
            $serviceClass = get_class($service);
            if ($serviceClass != $key) {
                $this->container->instance(get_class($service), $service);
            }
        }
        // Si $service es una clase, usar singleton para registrar.
        else if (is_string($service)) {
            // Verificar que la clase del servicio exista.
            if (!class_exists($service)) {
                throw new \InvalidArgumentException(sprintf(
                    'La clase "%s" del servicio "%s" no existe.',
                    $service,
                    $key
                ));
            }
            // Registrar.
            $this->container->singleton($key, $service);
            if ($service != $key) {
                $this->container->singleton($service, $service);
            }
        }
        // Si $service es otra cosa, lanzar una excepción.
        else {
            throw new \InvalidArgumentException(sprintf(
                'El servicio %s no es válido para ser registrado.',
                $key
            ));
        }
    }

}
