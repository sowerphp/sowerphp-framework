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

class Service_Http_Session implements Interface_Service, Interface_Service_Session
{

    //protected $sessionManager;
    //protected $store;
    protected $request;

    private $configService;

    public function __construct(Service_Config $configService)
    {
        $this->configService = $configService;
    }

    public function register()
    {
        //$this->sessionManager = new \Illuminate\Session\SessionManager($this->app);
        //$this->store = $this->sessionManager->driver();
    }

    public function boot()
    {
        $this->request = new Network_Request(); // TODO: Se debe inyectar como dependencia.
        $this->start();
        $this->configure();
        $this->saveUrlTracking();
    }

    /**
     * Método que inicia la sesión.
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $expires = $this->configService->get('session.expires', 30);
            $lifetime = $expires * 60;
            $session_name = 'sec_session_id';
            $path = $this->request->getBaseUrlWithoutSlash();
            $path = $path != '' ? $path : '/';
            $domain = $this->request->getSingleHeader('X-Forwarded-Host');
            if (!$domain) {
                $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            }
            if (strpos($domain, ':')) {
                list($domain, $port) = explode(':', $domain);
            }
            $secure = isset($_SERVER['HTTPS']) ? true : false;
            $httponly = true;
            ini_set('session.use_only_cookies', true);
            ini_set('session.gc_maxlifetime', $lifetime <= 65535 ? $lifetime : 65535);
            session_name($session_name);
            if (@session_start() === false) {
                die('Service_Session::start() No fue posible iniciar la sesión de PHP "'.$session_name.'" usando '.ini_get('session.save_handler').'.');
            }
            setcookie(session_name(), session_id(), time()+$lifetime, $path, $domain, $secure, $httponly);
        }
    }

    /**
     * Carga configuración del inicio de la sesión.
     */
    private function configure(): void
    {
        // Idioma.
        if (!$this->get('config.language')) {
            $defaultLang = config('language');
            $userLang = $this->request->getSingleHeader('Accept-Language');
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
        // layout
        if (!$this->get('config.page.layout')) {
            $this->put('config.page.layout', config('page.layout'));
        }
    }

    /**
     * Método que guarda parámetros de rastreo (ej: UTM) para seguimiento de
     * campañas en la sesión. Así no se tienen que arrastrar por las URLs y se
     * puede saber estos datos para usar en otros lados (ej: formularios).
     */
    private function saveUrlTracking(): void
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
     * Entrega true si la variable esta creada en la sesión.
     * @param string $name Nombre de la variable que se quiere buscar.
     * @return bool Verdadero si la variable existe en la sesión.
     */
    public function has(string $key): bool
    {
        if (!isset($_SESSION)) {
            return false;
        }
        $result = Utility_Set::classicExtract($_SESSION, $key);
        return isset($result);
    }

    /**
     * Recuperar el valor de una variable de sesión.
     * @param string $key Nombre de la variable que se desea leer o null para
     * leer todas las variables de la sesión.
     * @param mixed $default Valor por defecto de la variable.
     * @return mixed Valor de la variable o falso en caso que no exista o la sesión no este iniciada.
     */
    public function get(?string $key = null, $default = null)
    {
        if (!isset($_SESSION)) {
            return false;
        }
        // Si no se indico un nombre, se entrega todo el arreglo de la sesión.
        if ($key === null) {
            return $_SESSION;
        }
        // Extraer los datos que se están solicitando.
        $value = Utility_Set::classicExtract($_SESSION, $key);
        // Verificar que lo solicitado existe.
        if (!isset($value)) {
            return $default !== null ? $default : false;
        }
        // Retornar el valor encontrado.
        return $value;
    }

    /**
     * Escribir un valor de una variable de sesión.
     * @param string $key Nombre de la variable.
     * @param mixed $value Valor que se desea asignar a la variable.
     */
    public function put(string $key, $value): void
    {
        if (!isset($_SESSION)) {
            return;
        }
        // Armar el arreglo necesario para realizar la escritura.
        $write = $key;
        if (!is_array($key)) {
            $write = array($key => $value);
        }
        // Por cada elemento del arreglo escribir los datos de la sesión.
        foreach ($write as $key => $val) {
            $this->overwrite($_SESSION, Utility_Set::insert($_SESSION, $key, $val));
            if (Utility_Set::classicExtract($_SESSION, $key) !== $val) {
                return;
            }
        }
    }

    /**
     * Used to write new data to _SESSION, since PHP doesn't like us setting
     * the _SESSION var itself.
     * @param array $old Antiguo conjunto de datos de la sesión.
     * @param array $new Nuevo conjunto de datos de la sesión.
     */
    private function overwrite(array &$old, array $new): void
    {
        if (!empty($old)) {
            foreach ($old as $key => $var) {
                if (!isset($new[$key])) {
                    unset($old[$key]);
                }
            }
        }
        foreach ($new as $key => $var) {
            $old[$key] = $var;
        }
    }

    /**
     * Quitar una variable de la sesión.
     * @param string $key Nombre de la variable que se desea eliminar.
     */
    public function forget(string $key): void
    {
        if ($this->has($key)) {
            $this->overwrite($_SESSION, Utility_Set::remove($_SESSION, $key));
        }
    }

    public function flush(): void
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $this->overwrite($_SESSION, []);
    }

    public function close(): void
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_write_close();
        }
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

class Model_Datasource_Session
{

    public static function read(?string $key = null)
    {
        return app('session')->get($key);
    }

    public static function write(string $key, $value = null): bool
    {
        app('session')->put($key, $value);
        return app('session')->has($key);
    }

    public static function delete(string $key): bool
    {
        app('session')->forget($key);
        return app('session')->has($key);
    }

    public static function destroy(): void
    {
        app('session')->flush();
    }

    public static function check(string $key): bool
    {
        return app('session')->has($key);
    }

    public static function message(?string $message = null, string $type = 'info')
    {
        // si se indicó un mensaje se asigna
        if ($message) {
            Model_Datasource_Session_Message::write($message, $type);
        }
        // si no se indicó un mensaje se recupera y limpia
        else {
            return Model_Datasource_Session_Message::flush();
        }
    }

}

/**
 * Clase para escribir y recuperar mensajes desde una sesión.
 */
class Model_Datasource_Session_Message
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
        app('session')->put('session.messages', $messages);
    }

    /**
     * Método para recuperar todos los mensajes de la sesión y limpiarlos de la
     * misma.
     */
    public static function flush(): array
    {
        $messages = app('session')->get('session.messages');
        app('session')->forget('session.messages');
        return $messages ? (array)$messages : [];
    }

}
