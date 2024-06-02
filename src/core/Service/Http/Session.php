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
use Illuminate\Filesystem\Filesystem;
use Illuminate\Session\Store;
use Illuminate\Session\SessionManager;
use Illuminate\Session\FileSessionHandler;

/**
 * Servicio de sesión HTTP.
 *
 * Gestiona las sesiones HTTP utilizando Illuminate Session Manager.
 */
class Service_Http_Session implements Interface_Service, Interface_Service_Session
{

    /**
     * Contenedor de servicios de la sesión.
     *
     * @var Container
     */
    protected $container;

    /**
     * Instancia de SessionManager.
     *
     * @var SessionManager
     */
    protected $sessionManager;

    /**
     * Instancia de Service_Config.
     *
     * @var Service_Config
     */
    protected $configService;

    /**
     * Instancia de Network_Request.
     *
     * @var Network_Request
     */
    protected $request;

    /**
     * Constructor de la clase.
     *
     * @param Service_Config $configService Servicio de configuración.
     */
    public function __construct(Service_Config $configService, Network_Request $request)
    {
        $this->configService = $configService;
        $this->request = $request;
    }

    /**
     * Destructor de la clase.
     *
     * Se encarga de guarda la sesión cuando la instancia de esta clase es
     * destruída.
     */
    public function __destruct()
    {
        // Guardar sesión, en el caso que no se haya guardado en flujo normal
        // de Service_Http_Kernel.
        $this->save();
    }

    /**
     * Registra el servicio de sesión HTTP.
     *
     * @return void
     */
    public function register(): void
    {
        // Crear un contenedor de Illuminate.
        $container = new Container();

        // Registrar el servicio de archivos en el contenedor.
        $container->singleton('files', function () {
            return new Filesystem();
        });

        // Obtener toda la configuración desde el servicio de configuración.
        $configRepository = $this->configService->getRepository();
        $container->singleton('config', function () use ($configRepository) {
            return $configRepository;
        });

        // Registrar el almacenamiento de la sesión.
        $container->singleton('session.store', function (Container $app) {
            $fileHandler = new FileSessionHandler(
                $app->make('files'),
                $app->make('config')->get('session.files'),
                $app->make('config')->get('session.lifetime')
            );
            return new Store('laravel_session', $fileHandler);
        });

        // Registrar el administrador de la sesión.
        $container->singleton('session', function ($app) {
            return new SessionManager($app);
        });

        // Asignar el administrador de la sesión al servicio.
        $this->sessionManager = $container->make('session');
    }

    /**
     * Inicializa el servicio de sesión HTTP.
     *
     * @return void
     */
    public function boot(): void
    {
        // Iniciar sesión
        $this->start();

        // Configurar sesión.
        $this->configure();

        // Guardar datos para URL tracking.
        $this->saveUrlTracking();
    }

    /**
     * Inicia la sesión.
     *
     * @return void
     */
    public function start(): void
    {
        // Verificar si la cookie de sesión se está enviando correctamente
        $sessionId = $_COOKIE['laravel_session'] ?? null;
        if ($sessionId) {
            $this->sessionManager->setId($sessionId);
        }

        // Iniciar sesión.
        if (!$this->sessionManager->start()) {
            throw new \Exception('No fue posible iniciar la sesión.');
        }

        // Verificar nuevamente si la cookie de sesión se está enviando correctamente
        if (!$sessionId) {
            // Si no hay cookie de sesión, establecerla
            setcookie('laravel_session', $this->sessionManager->getId(), time() + 120 * 60, '/', null, false, true);
        }
    }

    /**
     * Carga configuración del inicio de la sesión.
     */
    protected function configure(): void
    {
        // Idioma por defecto de la aplicación será el del navegador web del
        // usuario si no está configurado.
        if (!$this->get('config.language')) {
            $defaultLang = config('language');
            $userLang = $this->request->headers->get('Accept-Language');
            if ($userLang) {
                $userLang = explode(',', explode('-', $userLang)[0])[0];
                if ($userLang === explode('_', $defaultLang)[0] || I18n::localeExists($userLang)) {
                    $this->put('config.language', $userLang);
                } else {
                    $this->put('config.language', $defaultLang);
                }
            } else {
                $this->put('config.language', $defaultLang);
            }
        }
        // Layout por defecto de la aplicación.
        if (!$this->get('config.page.layout')) {
            $this->put('config.page.layout', config('page.layout'));
        }
    }

    /**
     * Método que guarda parámetros de rastreo (ej: UTM) para seguimiento de
     * campañas en la sesión. Así no se tienen que arrastrar por las URLs y se
     * puede saber estos datos para usar en otros lados (ej: formularios).
     */
    protected function saveUrlTracking(): void
    {
        $url_tracking_keys = [
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_content',
            'utm_term',
        ];
        $url_tracking = [];
        foreach ($url_tracking_keys as $key) {
            if (!empty($_GET[$key])) {
                $url_tracking[$key] = trim($_GET[$key]);
            }
        }
        if (!empty($url_tracking)) {
            $this->put('url_tracking', $url_tracking);
        }
    }

    /**
     * Guarda y finaliza la sesión.
     *
     * @return void
     */
    public function save(): void
    {
        if ($this->sessionManager->isStarted()) {
            $this->sessionManager->save();
        }
    }

    /**
     * Obtiene un valor de la sesión.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->sessionManager->get($key, $default);
    }

    /**
     * Establece un valor en la sesión.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function put(string $key, $value): void
    {
        $this->sessionManager->put($key, $value);
    }

    /**
     * Verifica si una clave existe en la sesión.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->sessionManager->has($key);
    }

    /**
     * Elimina un valor de la sesión.
     *
     * @param string $key
     * @return void
     */
    public function forget(string $key): void
    {
        $this->sessionManager->forget($key);
    }

    /**
     * Obtiene todos los datos de la sesión.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->sessionManager->all();
    }

    /**
     * Destruye la sesión.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->sessionManager->flush();
    }

}

interface Interface_Service_Session
{
    public function start(): void;
    public function get(string $key, $default = null);
    public function put(string $key, $value): void;
    public function forget(string $key): void;
    public function flush(): void;
}

/**
 * Clase para escribir y recuperar mensajes desde una sesión.
 * @deprecated
 */
class SessionMessage
{

    public static function info(string $message): void
    {
        self::write($message, 'info');
    }

    public static function success(string $message): void
    {
        self::write($message, 'success');
    }

    public static function ok(string $message): void
    {
        self::success($message);
    }

    public static function warning(string $message): void
    {
        self::write($message, 'warning');
    }

    public static function danger(string $message): void
    {
        self::write($message, 'danger');
    }

    public static function error(string $message): void
    {
        self::danger($message);
    }

    /**
     * Método para escribir un mensaje en la sesión.
     * @param string $message Mensaje que se desea escribir.
     * @param string $type Tipo de mensaje: info, success (ok), warning o
     * danger (error).
     */
    public static function write(string $message, string $type = 'info'): void
    {
        if ($type == 'ok') {
            $type = 'success';
        }
        else if ($type == 'error') {
            $type = 'danger';
        }
        $messages = self::flush();
        $messages[] =  [
            'text' => $message,
            'type' => $type,
        ];
        session(['session.messages' => $messages]);
    }

    /**
     * Método para recuperar todos los mensajes de la sesión y limpiarlos de la
     * misma.
     */
    public static function flush(): array
    {
        $messages = session('session.messages');
        session()->forget('session.messages');
        return $messages ? (array)$messages : [];
    }

}
