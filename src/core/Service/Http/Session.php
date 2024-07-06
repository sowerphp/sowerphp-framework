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
use Illuminate\Config\Repository;
use Illuminate\Session\SessionManager;
use Illuminate\Filesystem\Filesystem;

/**
 * Servicio de sesión HTTP.
 *
 * Gestiona las sesiones HTTP utilizando Illuminate Session Manager.
 */
class Service_Http_Session implements Interface_Service
{

    /**
     * Configuración por defecto de la sesión del usuario.
     *
     * @var array
     */
    protected $defaultConfig = [
        'driver' => 'file',
        'lifetime' => 10080, // en minutos. Por defecto: 1 semana.
        'expire_on_close' => false,
        'encrypt' => false,
        'cookie' => 'sec_session_id',
        'http_only' => true,
        'same_site' => 'lax',
        // NOTE: Estas opciones se asigan en el método por requerir alguna acción.
        'files' => null,
        'path' => null,
        'domain' => null,
        'secure' => null,
    ];

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
    public function __construct(
        Service_Config $configService,
        Network_Request $request
    )
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
    }

    /**
     * Método que obtiene el administrador de la sesión.
     *
     * @return void
     */
    protected function getSessionManager()
    {
        if (!isset($this->sessionManager)) {
            // Crear un contenedor de servicios para el administrador de la
            // sesión.
            $container = new Container();

            // Obtener toda la configuración desde el servicio de configuración
            // y registrarla en el contenedor.
            $configRepository = $this->configService->getRepository();
            $container->instance('config', $configRepository);
            $this->setDefaultConfig($configRepository);

            // Registrar el servicio de archivos (driver) en el contenedor.
            $container->singleton('files', Filesystem::class);

            // Asignar el administrador de la sesión.
            $this->sessionManager = new SessionManager($container);
        }
        // Entregar el administrador de la sesión.
        return $this->sessionManager;
    }

    /**
     * Asignar configuración por defecto para la sesión.
     */
    protected function setDefaultConfig(Repository $config): void
    {
        // Asignar configuración estándar (atributo de la clase).
        foreach ($this->defaultConfig as $key => $value) {
            $key = 'session.' . $key;
            if (!$config->has($key)) {
                $config->set($key, $value);
            }
        }

        // Asignar ubicación de archivos si no existe.
        if (!$config->has('session.files') || is_null($config->get('session.files'))) {
            $config->set('session.files', storage_path('framework/sessions'));
        }

        // Asignar ruta por defecto.
        if (!$config->has('session.path') || is_null($config->get('session.path'))) {
            $path = request()->getBaseUrlWithoutSlash();
            $config->set('session.path', $path != '' ? $path : '/');
        }

        // Asignar dominio por defecto.
        if (!$config->has('session.domain') || is_null($config->get('session.domain'))) {
            $domain = request()->headers->get('X-Forwarded-Host');
            if (!$domain) {
                $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            }
            if (strpos($domain, ':')) {
                list($domain, $port) = explode(':', $domain);
            }
            $config->set('session.domain', $domain);
        }

        // Asignar cómo se provee la cookie de la sesión por defecto.
        if (!$config->has('session.secure') || is_null($config->get('session.secure'))) {
            $config->set('session.secure', isset($_SERVER['HTTPS']));
        }
    }

    /**
     * Inicializa el servicio de sesión HTTP.
     *
     * @return void
     */
    public function boot(): void
    {
        // Crear administrador de la sesión.
        $this->getSessionManager();

        // Iniciar sesión
        $this->start();

        // Configurar sesión.
        $this->configure();

        // Guardar datos para URL tracking.
        $this->saveUrlTracking();
    }

    /**
     * Finaliza el servicio de Console kernel.
     *
     * @return void
     */
    public function terminate(): void
    {
        // Guardar y cerrar la sesión.
        // NOTE: el ideal sería acá y no en el destructor, pero mientras
        // existan "exit;" en la aplicación debe ir en el destructor.
        //$this->save();
    }

    /**
     * Inicia la sesión.
     *
     * @return void
     */
    public function start(): void
    {
        // Configuración de la sesión.
        $config = $this->sessionManager->getSessionConfig();

        // Verificar si la cookie de sesión se está enviando correctamente.
        $cookieName = $config['cookie'];
        $sessionId = $_COOKIE[$cookieName] ?? null;
        if ($sessionId) {
            $this->sessionManager->setId($sessionId);
        }

        // Iniciar sesión.
        if (!$this->sessionManager->start()) {
            throw new \Exception('No fue posible iniciar la sesión.');
        }

        // Verificar nuevamente si la cookie de sesión se está enviando
        // correctamente. Si no hay cookie de sesión, establecerla.
        if (!$sessionId) {
            $cookieLifetime = $config['lifetime'] * 60;
            $cookieExpires = time() + $cookieLifetime;
            $result = setcookie(
                $cookieName,
                $this->sessionManager->getId(),
                [
                    'expires' => $cookieExpires,
                    'path' => $config['path'],
                    'domain' => $config['domain'],
                    'secure' => $config['secure'],
                    'httponly' => $config['http_only'],
                    'samesite' => ucfirst($config['same_site'] ?? 'None'),
                ]
            );
            if ($result === false) {
                throw new \Exception(
                    'No fue posible asignar la cookie de la sesión.'
                );
            }
        }
    }

    /**
     * Carga configuración del inicio de la sesión.
     */
    protected function configure(): void
    {
        // Idioma por defecto de la aplicación será el del navegador web del
        // usuario si no está configurado.
        if (!$this->get('config.app.locale')) {
            $defaultLang = config('app.locale');
            $userLang = $this->request->headers->get('Accept-Language');
            if ($userLang) {
                $userLang = explode(',', explode('-', $userLang)[0])[0];
                if (
                    $userLang === explode('_', $defaultLang)[0]
                    || I18n::localeExists($userLang)
                ) {
                    $this->put('config.app.locale', $userLang);
                } else {
                    $this->put('config.app.locale', $defaultLang);
                }
            } else {
                $this->put('config.app.locale', $defaultLang);
            }
        }
        // Layout por defecto de la aplicación.
        if (!$this->get('config.app.ui.layout')) {
            $this->put('config.app.ui.layout', config('app.ui.layout'));
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
     * Obtener los parámetros de configuración de la cookie de la sesión.
     *
     * @return array
     */
    public function getCookieParams(): array
    {
        $config = $this->sessionManager->getSessionConfig();
        return [
            'lifetime' => $config['lifetime'],
            'path' => $config['path'],
            'domain' => $config['domain'],
            'secure' => $config['secure'],
            'httponly' => $config['http_only'],
            'samesite' => $config['same_site'],
        ];
    }

    /**
     * Método que guarda la sesión.
     *
     * Se utiliza un método propio para controlar que se guarde sólo si existe
     * el sesión manager.
     *
     * @return void
     */
    protected function save(): void
    {
        if (isset($this->sessionManager)) {
            $this->sessionManager->save();
        }
    }

    /**
     * Cualquier método que no esté definido en el servicio será llamado en el
     * administrador de la sesión.
     *
     * Ejemplos de métodos del administrador de la sesión que se usarán:
     *   - put()
     *   - get()
     *   - forget()
     *   - flush()
     *   - flash()
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(
            [
                $this->getSessionManager(),
                $method
            ],
            $parameters
        );
    }

}
