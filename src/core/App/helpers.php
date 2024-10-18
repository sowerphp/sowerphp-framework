<?php

declare(strict_types=1);

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

use Illuminate\Contracts\Auth\Authenticatable;
use League\Flysystem\Filesystem;
use sowerphp\core\App;
use sowerphp\core\Auth_Guard;
use sowerphp\core\Database_Connection;
use sowerphp\core\Network_Mail_Mailbox;
use sowerphp\core\Network_Request;
use sowerphp\core\Network_Response;
use sowerphp\core\Model;
use sowerphp\core\Service_Cache;
use sowerphp\core\Service_Http_Client;
use sowerphp\core\Service_Http_Redirect;
use sowerphp\core\Service_Http_Router;
use sowerphp\core\Service_Model;
use sowerphp\core\Service_View;
use Symfony\Component\Mailer\Mailer;

/**
 * Permite acceder a las instancias almacenadas en el contenedor de servicios de
 * la aplicación. Por defecto entrega la instancia de la aplicación.
 *
 * @param string|null $key La clave del servicio que se desea obtener.
 * @param array $parameters Parámetros adicionales para la creación de
 * instancias.
 * @return mixed La instancia del servicio solicitado o la instancia de
 * la clase contenedora.
 */
function app(string $key = null, array $parameters = []): object
{
    $instance = App::getInstance();

    if ($key === null) {
        return $instance;
    }

    return empty($parameters)
        ? $instance->getService($key)
        : $instance->getService($key, $parameters)
    ;
}

/**
 * Obtiene una instancia de almacenamiento.
 *
 * @param string|null $disk Nombre del disco que se desea usar.
 * @return Filesystem
 */
function storage(?string $disk = null): Filesystem
{
    return app('storage')->disk($disk);
}

/**
 * Obtiene la ruta al directorio de almacenamiento.
 *
 * Este método utiliza el servicio 'layers' registrado en la aplicación para
 * obtener el directorio de almacenamiento. Si se proporciona un camino
 * adicional, se concatenará con el directorio de almacenamiento.
 *
 * Además, si la ruta se pasa y se determina que es un directorio se verificará
 * si existe, si no existe se tratará de crear.
 *
 * @param string|null $path Ruta adicional para concatenar con el directorio de
 * almacenamiento.
 * @return string Ruta completa al directorio de almacenamiento, incluyendo la
 * ruta adicional si se proporciona.
 */
function storage_path(?string $path = null): string
{
    if ($path !== null) {
        $storage = storage();
        $isDirectory = $storage->isLikelyDirectory($path);
        if ($isDirectory && !$storage->directoryExists($path)) {
            $storage->createDirectory($path);
        }
    }

    return app('layers')->getStoragePath($path);
}

/**
 * Obtiene la ruta al directorio de recursos.
 *
 * Este método utiliza el servicio 'layers' registrado en la aplicación para
 * obtener el directorio de recursos. Si se proporciona un camino adicional, se
 * concatenará con el directorio de recursos.
 *
 * @param string|null $path Ruta adicional para concatenar con el directorio de
 * recursos.
 * @return string Ruta completa al directorio de recursos, incluyendo la ruta
 * adicional si se proporciona.
 */
function resource_path(?string $path = null): string
{
    return app('layers')->getResourcePath($path);
}

/**
 * Accede a la configuración de la aplicación.
 *
 * @param string $selector Variable / parámetro que se desea leer.
 * @param mixed $default Valor por defecto de la variable buscada.
 * @return mixed Valor determinado de la variable (real, defecto o null).
 */
function config(string $selector, $default = null)
{
    return app('config')->get($selector, $default);
}

/**
 * Permite interactuar con la sesión.
 *
 * @param string|null $key
 * @param mixed $default
 * @return mixed
 */
function session($key = null, $default = null)
{
    // Obtener el servicio de la sesión desde el contenedor de la aplicación.
    $sessionService = app('session');

    // Si no se proporciona una clave, devolver el servicio de la sesión.
    if (is_null($key)) {
        return $sessionService;
    }

    // Si se proporciona un array, tratarlo como un conjunto de pares
    // clave-valor para almacenar en la sesión.
    if (is_array($key)) {
        foreach ($key as $k => $v) {
            $sessionService->put($k, $v);
        }
        return;
    }

    // De lo contrario, devolver el valor de la sesión para la clave
    // proporcionada, con un posible valor por defecto.
    return $sessionService->get($key, $default);
}

/**
 * Obtiene el servicio de caché.
 *
 * @return Service_Cache
 */
function cache(): Service_Cache
{
    return app('cache');
}

/**
 * Obtiene una conexión a la base de datos.
 *
 * @param string|null $name Nombre de la conexión.
 * @return Database_Connection
 */
function database(?string $name = null): Database_Connection
{
    return app('database')->connection($name);
}

/**
 * Obtiene la guard de autenticación solicitada o del contexto de la llamada.
 *
 * @param string|null $guard La guard que se está autenticando (web o api).
 * @return Auth_Guard
 */
function auth(?string $guard = null): Auth_Guard
{
    return app('auth')->guard($guard);
}

/**
 * Obtiene al usuario autenticado en la aplicación.
 *
 * @param Authenticatable|null $guard
 */
function user(?string $guard = null): ?Authenticatable
{
    return auth($guard)->check() ? auth($guard)->user() : null;
}

/**
 * Traducción de strings mediante le servicio de lenguages con soporte de
 * interpolación mixta. Permite utilizar tanto el formato de placeholders de
 * Python (%(name)s) como el de sprintf (%s, %d).
 *
 * Esta función determina los valores que se usarán para reemplazar.
 * Es necesario hacer esto porque app('translator')->get() recibe siempre un
 * arreglo y la implementación original del framework (por ende su uso actual)
 * permite argumentos variables (obsoleto) o arreglos (lo nuevo que se debería
 * usar en el futuro).
 *
 * @param string $string Texto que se desea traducir.
 * @param mixed ...$args Argumentos para reemplazar en el string, pueden ser un
 * arreglo asociativo o valores individuales.
 * @return string Texto traducido con los placeholders reemplazados.
 */
function __(string $string, ...$args): string
{
    $replace = is_array($args[0] ?? null)
        ? $args[0]
        : array_slice(func_get_args(), 1)
    ;

    return app('translator')->get($string, $replace);
}

/**
 * Despacha un evento y llama a los listeners.
 *
 * @param string|object $event El evento que se va a despachar. Puede ser una
 * cadena que represente el nombre del evento o un objeto que encapsule los
 * datos del evento.
 * @param mixed $payload Los datos adicionales que se pasarán a los listeners
 * del evento. Este parámetro es opcional y por defecto es un arreglo vacío.
 * @param bool $halt Indica si se debe detener la ejecución tras la primera
 * respuesta válida de un listener. Este parámetro es opcional y por defecto es
 * `false`.
 * @return mixed|array|null Retorna un arreglo con las respuestas de los listeners.
 * Si el parámetro `$halt` es `true`, retorna la primera respuesta válida.
 */
function event($event, $payload = [], $halt = false)
{
    return app('events')->dispatch($event, $payload, $halt);
}

/**
 * Encripta los datos con el encriptador configurado en la aplicación.
 *
 * @param mixed $value Datos que se desean encriptar.
 * @param boolean $serialize Indica si se deben serializar los datos.
 * @return string Datos encriptados.
 */
function encrypt($value, $serialize = true): string
{
    return app('encryption')->encrypt($value, $serialize);
}

/**
 * Desencripta los datos con el encriptador configurado en la aplicación.
 *
 * @param mixed $value Datos que se desean desencriptar.
 * @param boolean $unserialize Indica si se deben deserializar los datos.
 * @return string Datos desencriptados.
 */
function decrypt($payload, $unserialize = true)
{
    return app('encryption')->decrypt($payload, $unserialize);
}

/**
 * Permite trabajar con el servicio de modelos de la aplicación.
 *
 * Si se pasa un modelo se obtendrá la instancia en vez del servicio.
 * Normalmente se pasará el modelo y la identificación del modelo (llave
 * primaria) para obtener una instancia del modelo (registro de la base de
 * datos).
 *
 * @param string|null $model Nombre del modelo que se desea obtener.
 * @param array ...$id Identificador del modelo en la base de datos.
 * @return Service_Model|Model
 */
function model(?string $model = null, ...$id)
{
    $modelService = app('model');

    if ($model === null) {
        return $modelService;
    }

    return $modelService->instantiate($model, ...$id);
}

/**
 * Obtiene un remitente de correo.
 *
 * @param string|null $name Nombre del remitente.
 * @return Mailer
 */
function mailer(?string $name = null): Mailer
{
    return app('mail')->mailer($name);
}

/**
 * Obtiene un receptor de correo.
 *
 * @param string|null $name Nombre del receptor.
 * @return Network_Mail_Mailbox
 */
function mail_receiver(?string $name = null): Network_Mail_Mailbox
{
    return app('mail')->receiver($name);
}

/**
 * Obtiene una instancia del cliente HTTP.
 *
 * @return Service_Http_Client
 */
function http_client(): Service_Http_Client
{
    return app('http_client');
}

/**
 * Registra un mensaje en el logger.
 *
 * @param mixed $level El nivel del log. Puede ser una cadena o una constante de
 * Monolog.
 * @param string $message El mensaje a registrar.
 * @param array $context Contexto adicional para el mensaje.
 * @return void
 */
function log_message($level, string $message, array $context = []): void
{
    app('log')->log($level, $message, $context);
}

/**
 * Envía una notificación a un destinatario por correo electrónico.
 *
 * @param string $subject El asunto del correo.
 * @param string|array $content Un string con el contenido del correo o un
 * arreglo con índices: `text` y `html`.
 * @param string|array $to Un string con el correo electrónico del destinatario
 * o un arreglo con índices: `address` y `name`.
 * @param array $attachments Arreglo con los archivos adjuntos del correo.
 * @param string|array $from Un string con el correo electrónico del remitente o
 * un arreglo con índices: `address` y `name`.
 * @return void
 */
function send_email(
    string $subject,
    $content,
    $to,
    array $attachments = [],
    $from = null
): void {
    app('notification')->sendEmail($subject, $content, $to, $attachments, $from);
}

/**
 * Entrega el servicio de enrutamiento.
 *
 * @return Service_Http_Router
 */
function router(): Service_Http_Router
{
    return app('router');
}

/**
 * Redirecciona a una URL específica.
 *
 * @param string|null $to La URL a la que redirigir.
 * @param int $status El código de estado HTTP de la redirección.
 * @param array $headers Encabezados adicionales para la respuesta.
 * @return Service_Http_Redirect Objeto con servicio de redirección.
 */
function redirect(
    ?string $to = null,
    int $status = 302,
    array $headers = []
): Service_Http_Redirect
{
    $redirectService = app('redirect');

    if ($to === null) {
        return $redirectService;
    }

    return $redirectService->to($to, $status, $headers);
}

/**
 * Permite acceder a la solicitud HTTP en curso.
 *
 * @return Network_Request
 */
function request(): Network_Request
{
    return app('request');
}

/**
 * Permite cceder a la respuesta HTTP en curso.
 *
 * @return Network_Response
 */
function response(): Network_Response
{
    return app('response');
}

/**
 * Renderiza una vista.
 *
 * La vista puede tener variables (opcionales) y además puede usar diferentes
 * motores para ser renderizada. También se permite que la vista sea
 * especificada de diferentes formas, parcial o como ruta absoluta.
 *
 * @param string $view Nombre de la vista que se desea renderizar.
 * @param array $data Variables que se pasarán a la vista al renderizar.
 * @return Service_View|Network_Response
 */
function view(?string $view = null, array $data = [])
{
    $viewService = app('view');

    if ($view === null) {
        return $viewService;
    }

    return $viewService->renderToResponse($view, $data);
}

/**
 * Entrega la ruta completa (URL) de un recurso (path) de la aplicación.
 *
 * @param resource Recurso (path) que se desea resolver.
 * @return string URL completa que resuelve el recurso (path).
 */
function url(string $resource = '/', ...$args): string
{
    $resource = vsprintf($resource, $args);
    $url = (string) config('app.url');

    if (!$url) {
        try {
            $url = request()->getFullUrlWithoutQuery();
        } catch (Exception $e) {
            $url = null;
        }
    }

    if (!$url) {
        throw new Exception(__(
            'No fue posible determinar la URL completa del recurso %s.',
            $resource
        ));
    }

    return $resource == '/' ? $url : $url . $resource;
}

/**
 * Muestra información relevante para depuración de una variale.
 *
 * @param mixed $var Variable que se desea mostrar.
 * @param string $label Etiqueta de la variable, debería ser su nombre.
 * @deprecated Se recomienda utilizar dump() o dd().
 */
function debug($var, ?string $label = null)
{
    $backtrace = debug_backtrace();
    $debug_call = $backtrace[0];
    $debug_caller = $backtrace[1];

    $data = [
        'label' => $label ?? 'debug($var)',
        'type' => gettype($var),
        'length' => is_countable($var)
            ? count($var)
            : (is_string($var) ? strlen($var) : '')
        ,
        'file' => $debug_call['file'] ?? null,
        'line' => $debug_call['line'] ?? null,
        'caller' => isset($debug_caller['class'])
            ? "{$debug_caller['class']}::{$debug_caller['function']}()"
            : "{$debug_caller['function']}()"
        ,
        'timestamp' => microtime(true),
        'memory_usage' => memory_get_usage(),
        'value' => null, // Se ajustará según el tipo de la variable.
    ];

    // Ajustar el valor de la variable según su tipo.
    if (is_object($var)) {
        $data['type'] = get_class($var);
        $data['value'] = print_r($var, true);
    } else if (is_null($var) || is_bool($var)) {
        $data['value'] = json_encode($var, JSON_PRETTY_PRINT);
    } else {
        $data['value'] = print_r($var, true);
    }

    // Mostrar los datos (se podría mejorar la forma de mostrarlos).
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

/**
 * Formatea números con soporte para idioma y dígitos decimales.
 *
 * @param mixed $n Número a formatear.
 * @param mixed $d Cantidad de decimales o true para usar 2. Si no se
 * proporciona, se asume 0.
 * @param string|null $language Idioma que se debe utilizar para formatear el
 * número. Si no se pasa, se utiliza la función get_language() para obtenerlo.
 * @return string Número formateado según la configuración regional.
 */
function num($n, $d = 0, $language = null)
{
    if (empty($n)) {
        return '0';
    }

    if (!is_numeric($n)) {
        trigger_error(
            __('num(): El argumento proporcionado (%s) no es un número válido.', $n),
            E_USER_WARNING
        );
        return $n;
    }

    $n = (float)$n;

    if ($d === true) {
        $d = 2;
    }

    if (!is_int($d) || $d < 0) {
        trigger_error(
            __('num(): La cantidad de dígitos decimales proporcionada (%s) no es válida.', $d),
            E_USER_WARNING
        );
        return $n;
    }

    $n = round($n, $d);

    if ($language === null) {
        $language = 'es_CL';
    }

    $formatter = new NumberFormatter($language, NumberFormatter::DECIMAL);
    $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $d);
    $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $d);

    return $formatter->format($n);
}

/**
 * Da formato a los mensajes de la aplicación.
 *
 * @param string $string Mensaje al que se desea dar el formato, puede contener
 * marcadores especiales.
 * @param bool $html Indica si el formato debe ser HTML o texto plano.
 * @return string Mensaje formateado en HTML o texto.
 */
function message_format(string $string, bool $html = true): string
{
    // Preguntas frecuentes de la aplicación.
    if (strpos($string, '[faq:') !== false) {
        $faq = (array)config('services.faq');
        // Hay config de faqs -> se agrega enlace.
        if (!empty($faq['url']) && !empty($faq['text'])) {
            $replace = $html
                ? '<a href="' . $faq['url']
                    . '$2" target="_blank" class="alert-link">'
                    . $faq['text'] . '</a>'
                : $faq['text'] . ': ' . $faq['url'] . '$2';
            $string = preg_replace(
                '/\[(faq):([\w\d]+)\]/i',
                $replace,
                $string
            );
        }
        // No hay config de faqs -> se quita FAQ del mensaje.
        else {
            $string = preg_replace(
                '/\[(faq):([\w\d]+)\]/i',
                '',
                $string
            );
        }
    }

    // Cambios cuando es HTML (se pasa texto a HTML).
    if ($html) {
        // Enlaces en formato markdown.
        $string = preg_replace(
            '/\[([^\]]+)\]\((https?:\/\/[^\s]+)\)/',
            '<a href="$2" target="_blank" class="alert-link">$1</a>',
            $string
        );
        // Flechas para instrucciones (tipo "siguiente").
        $string = str_replace('>>', '&raquo;', $string);
    }

    // Entregar string modificado con los enlaces correspondientes.
    return $string;
}

/**
 * Determina si un valor puede ser serializado.
 *
 * @param mixed $value Valor que se desea saber si puede ser serializado.
 * @return bool `true` si el valor puede ser serializado.
 */
function is_serializable($value): bool
{
    // Tipos que necesitan ser serializados.
    if (is_array($value) || is_object($value)) {
        return true;
    }

    // Los tipos escalares no necesitan ser serializados.
    if (is_scalar($value) || is_null($value)) {
        return false;
    }

    // Por defecto, cualquier otro tipo necesitaría ser serializado.
    return true;
}

/**
 * Determina si los datos están serializados.
 *
 * @param mixed $data Datos que se desea saber si están serializados.
 * @return bool `true` si los datos están serialiados.
 */
function is_serialized($data): bool
{
    // Si no es una cadena, no está serializado.
    if (!is_string($data)) {
        return false;
    }

    // Si es "N;", está serializado (valor NULL).
    if ($data === 'N;') {
        return true;
    }

    // Verificar si es un valor serializado más complejo.
    if (preg_match('/^([adObis]):/', $data, $matches)) {
        switch ($matches[1]) {
            case 'a':
            case 'O':
            case 's':
                return (bool)preg_match("/^{$matches[1]}:[0-9]+:/s", $data);
            case 'b':
            case 'i':
            case 'd':
                return (bool)preg_match("/^{$matches[1]}:[0-9.E-]+;$/", $data);
        }
    }

    // Otros casos no se consideran serializados.
    return false;
}

/**
 * Convierte un string de parámetros en un arreglo, manejando caracteres de
 * split escapados.
 *
 * Esta función permite convertir una cadena de parámetros separados por un
 * delimitador especificado en un arreglo. El delimitador puede ser cualquier
 * carácter, y las instancias de dicho delimitador que estén escapadas serán
 * tratadas como parte del valor del parámetro en lugar de como un separador.
 *
 * @param string $parametersString El string de parámetros, donde los parámetros
 * están separados por un carácter especificado.
 * @param string $delimiter El carácter utilizado para separar los parámetros.
 * Por defecto es una coma.
 * @return array Arreglo de parámetros.
 *
 * @example
 * // Con la coma como delimitador.
 * parseParameters('param\,1,2');
 * Resultado: ['param,1', '2']
 *
 * @example
 * // Con el punto y coma como delimitador.
 * parseParameters('param\;1;2', ';');
 * Resultado: ['param;1', '2']
 */
function split_parameters(string $parametersString, string $delimiter = ','): array
{
    // Escapa el delimitador para su uso en expresiones regulares.
    $escapedDelimiter = preg_quote($delimiter, '/');

    // Reemplaza las instancias del delimitador escapado con un marcador
    // temporal.
    $tempMarker = '{{[[DELIMITER]]}}';
    $tempString = preg_replace(
        '/\\\\' . $escapedDelimiter . '/',
        $tempMarker,
        $parametersString
    );

    // Divide la cadena en partes usando el delimitador como separador.
    $parts = explode($delimiter, $tempString);

    // Restaura los delimitadores escapados en las partes.
    $parameters = array_map(function ($part) use ($delimiter, $tempMarker) {
        return str_replace($tempMarker, $delimiter, trim($part));
    }, $parts);

    // Entregar los parámetros encontrados.
    return $parameters;
}

/**
 * Construye una cadena de atributos HTML a partir de un arreglo.
 *
 * @param array $attributes Arreglo de atributos.
 * @return string Cadena de atributos HTML.
 */
function html_attributes(?array $attributes): string
{
    if (empty($attributes)) {
        return '';
    }

    $attributes = array_filter($attributes, function($value) {
        return $value !== null && $value !== false && $value !== '';
    });

    return implode(' ', array_map(
        function($key, $value) {
            if ($value === true) {
                return sprintf(
                    '%s',
                    e($key)
                );
            } else {
                return sprintf(
                    '%s="%s"',
                    e($key),
                    str_replace('&#039;', '\'', e($value))
                );
            }
        },
        array_keys($attributes),
        $attributes
    ));
}
