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
     *
     * @var App
     */
    private static $instance;

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
        try {
            $kernel = $this->container->make('kernel');
            $result = $kernel->handle();
            return $result;
        } catch (\Throwable $throwable) {
            if (isset($kernel)) {
                $kernel->handleThrowable($throwable);
            } else {
                $this->handleThrowable($throwable);
            }
            return 1;
        }
    }

    /**
     * Manejador de errores y excepciones cuando el kernel aún no está
     * disponible. Esto mostrará un error "feo", sin embargo, en producción
     * no debería ocurrir un error de este tipo ya que pruebas adecuadas hasta
     * que el kernel esté disponible asegurarán que el error se maneje por el
     * kernel de una manera más amigable con el usuario.
     *
     * @param \Throwable $throwable
     * @return void
     */
    private function handleThrowable(\Throwable $throwable): void
    {
        // Variables con los datos del error o excepción.
        $type = $throwable instanceof \Error ? 'error' : 'excepción';
        $class = get_class($throwable);
        $message = $throwable->getMessage();
        $code = $throwable->getCode();
        $severity = $throwable->severity;
        $file = $throwable->getFile();
        $line = $throwable->getLine();
        $trace = $throwable->getTraceAsString();
        // Armar mensaje con el detalle del error o excepción.
        $error = sprintf(
            'Se capturó %s (%s de nivel %s):' . "\n\n"
                .' %s.' . "\n\n"
                . 'En %s:%d.' . "\n\n"
                . 'Traza completa:' . "\n\n"
                . '%s'
            , $class, $type, $severity, $message, $file, $line, $trace
        );
        // Generar mensaje con el error o excepción.
        header('Content-Type: text/plain');
        die($error);
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
        $this->registerService('app', $this); // No es un servicio realmente.

        // Registrar e inicializar el resto de servicios de la aplicación.
        $this->registerServices();
        $this->bootServices();

        // Inicializar cada capa con su archivo bootstrap personalizado.
        $this->getService('layers')->loadFiles([
            '/App/bootstrap.php',
        ]);
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
            $request = Network_Request::capture();
            $this->registerService('request', $request);
            $this->registerService('session', Service_Http_Session::class);
            $this->registerService('kernel', Service_Http_Kernel::class);
        }

        // Ejecutar método register() en cada servicio.
        $registered = [];
        foreach ($this->container->getBindings() as $key => $binding) {
            $service = $this->container->make($key);
            $serviceClass = get_class($service);
            if ($service instanceof Interface_Service && !isset($registered[$serviceClass])) {
                $service->register();
                $registered[$serviceClass] = true;
            }
        }
    }

    /**
     * Inicializa todos los servicios necesarios después del registro.
     */
    private function bootServices(): void
    {
        $initialized = [];
        foreach ($this->container->getBindings() as $key => $binding) {
            $service = $this->container->make($key);
            $serviceClass = get_class($service);
            if ($service instanceof Interface_Service && !isset($initialized[$serviceClass])) {
                $service->boot();
                $initialized[$serviceClass] = true;
            }
        }
    }

    /**
     * Registra un servicio en el contenedor.
     *
     * @param string $key Identificador del servicio.
     * @param mixed $service Instancia del servicio o el nombre de la clase.
     */
    private function registerService($key, $service): void
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
            $this->container->instance(get_class($service), $service);
        }
        // Si $service es una clase, usar singleton para registrar.
        else if (is_string($service)) {
            // Verificar que la clase del servicio exista.
            if (!class_exists($service)) {
                throw new \InvalidArgumentException(sprintf(
                    'La clase del servicio %s no existe.',
                    $service
                ));
            }
            // Registrar.
            $this->container->singleton($key, $service);
            $this->container->singleton($service, $service);
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
