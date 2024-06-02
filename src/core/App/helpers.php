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

/**
 * Función global para acceder a las instancias almacenadas en el
 * contenedor de servicio de una clase, por defecto App.
 *
 * @param string|null $key La clave del servicio que se desea obtener.
 * @param array $parameters Parámetros adicionales para la creación de
 * instancias.
 * @return mixed La instancia del servicio solicitado o la instancia de
 * la clase contenedora.
 */
function app(string $key = null, array $parameters = []): object
{
    $instance = \sowerphp\core\App::getInstance();
    if ($key === null) {
        return $instance;
    }
    return empty($parameters)
        ? $instance->getService($key)
        : $instance->getService($key, $parameters)
    ;
}

/**
 * Obtener una instancia de almacenamiento.
 *
 * @param string|null $disk Nombre del disco que se desea usar.
 * @return \League\Flysystem\Filesystem
 */
function storage(?string $disk = null): \League\Flysystem\Filesystem
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
 * @param string|null $path Ruta adicional para concatenar con el directorio
 * de almacenamiento.
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
 * Función global para acceder a la configuración de la aplicación.
 * @param string $selector Variable / parámetro que se desea leer.
 * @param mixed $default Valor por defecto de la variable buscada.
 * @return mixed Valor determinado de la variable (real, defecto o null).
 */
function config(string $selector, $default = null)
{
    return app('config')->get($selector, $default);
}

/**
 * Función auxiliar para interactuar con la sesión.
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
 * Función global para acceder a la solicitud HTTP en curso.
 *
 * @return \sowerphp\core\Network_Request
 */
function request(): \sowerphp\core\Network_Request
{
    return app('kernel')->getRequest();
}

/**
 * Función global para acceder a la respuesta HTTP en curso.
 *
 * @return \sowerphp\core\Network_Response
 */
function response(): \sowerphp\core\Network_Response
{
    return app('kernel')->getResponse();
}

/**
 * Función que entrega la ruta completa (URL) de un recurso (path) de la
 * aplicación.
 * @param resource Recurso (path) que se desea resolver.
 * @return string URL completa que resuelve el recurso (path).
 */
function url(string $resource = '/', ...$args): string
{
    $resource = vsprintf($resource, $args);
    $url = (string)config('app.url');
    if (!$url) {
        try {
            $url = request()->getFullUrlWithoutQuery();
        } catch (\Exception $e) {
            $url = null;
        }
    }
    if (!$url) {
        throw new \Exception(__(
            'No fue posible determinar la URL completa del recurso %s.',
            $resource
        ));
    }
    return $resource == '/' ? $url : $url . $resource;
}

/**
 * Función para mostrar información relevante para depuración de una
 * variale.
 * @param mixed $var Variable que se desea mostrar.
 * @param string $label Etiqueta de la variable, debería ser su nombre.
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
 * Función para formatear números con soporte para idioma y dígitos
 * decimales.
 *
 * @param mixed $n Número a formatear.
 * @param mixed $d Cantidad de decimales o true para usar 2. Si no se
 * proporciona, se asume 0.
 * @param string|null $language Idioma que se debe utilizar para
 * formatear el número. Si no se pasa, se utiliza la función
 * get_language() para obtenerlo.
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
 * Función para traducción de string singulares en dominio master con
 * soporte de interpolación mixta. Permite utilizar tanto el formato de
 * placeholders de Python (%(name)s) como el de sprintf (%s, %d).
 *
 * @param string $string Texto que se desea traducir.
 * @param mixed ...$args Argumentos para reemplazar en el string, pueden
 * ser un arreglo asociativo o valores individuales.
 * @return string Texto traducido con los placeholders reemplazados.
 */
function __(string $string, ...$args): string
{
    return __d('master', $string, ...$args);
}

/**
 * Función para traducción de string singulares, eligiendo dominio con
 * soporte de interpolación mixta. Permite utilizar tanto el formato de
 * placeholders de Python (%(name)s) como el formato clásico (%s, %d, %f)
 * y el formato simulado de parámetros SQL (:name) para uniformidad en
 * la definición de strings. Además, la función automáticamente ajusta
 * los placeholders que no incluyen el formato específico (%( )s).
 *
 * @param string $domain Dominio del lenguaje al que se traducirá el texto.
 * @param string $string Texto que se desea traducir.
 * @param mixed ...$args Argumentos para reemplazar en el string, pueden
 * ser un arreglo asociativo o valores individuales.
 * @return string Texto traducido con los placeholders reemplazados.
 *
 * Ejemplos de uso:
 * 1. Interpolación al estilo Python con formato completo:
 *    echo __(
 *        'Hello %(name)s, your balance is %(balance).2f',
 *        ['%(name)s' => 'John', '%(balance).2f' => 1234.56]
 *    );
 *
 * 2. Interpolación al estilo Python sin formato específico en los placeholders:
 *    echo __(
 *        'Hello %(name)s, your balance is %(balance).2f',
 *        ['name' => 'John', 'balance' => 1234.56]
 *    );
 *
 * 3. Uso con formato clásico de sprintf:
 *    echo __('Hello %s, you have %d new messages', 'Alice', 5);
 *
 * 4. Uso del formato simulado de parámetros SQL (no para consultas SQL):
 *    echo __(
 *        'Your username is :name and your ID is :id',
 *        [':name' => 'Alice', ':id' => '123']
 *    );
 */
function __d(string $domain, string $string, ...$args): string
{
    $translated = \sowerphp\core\I18n::translate($string, $domain);
    if (empty($args)) {
        return $translated;
    }

    // Verificar si se usó un array asociativo o valores individuales
    $firstArg = $args[0];
    if (is_array($firstArg) && Utility_Array::isAssoc($firstArg)) {
        $placeholders = [];
        foreach ($firstArg as $key => $value) {
            if (in_array($key[0], ['%(', ':'])) {
                $placeholders[$key] = $value;
            } else {
                $placeholders['%(' . $key . ')s'] = $value;
            }
        }
        return strtr($translated, $placeholders);
    } else {
        return vsprintf($translated, $args);
    }
}

/**
 * Función que permite ejecutar un comando en la terminal.
 */
function shell_exec_async($cmd, $log = false, &$output = []): int
{
    $cmd = trim($cmd);
    if (empty($cmd)) {
        return 255;
    }
    if ($cmd[0] != '/') {
        $cmd = app('layers')->getProjectPath() . '/console/shell.php ' . $cmd;
    }
    $screen_cmd = 'screen -dm';
    if ($log) {
        if (!is_string($log)) {
            $log = DIR_TMP . '/screen_' . microtime(true) . '.log';
        } else {
            $log = trim($log);
        }
        exec('screen --version', $screen_version);
        $version = explode(' ', $screen_version[0])[2];
        if ($version >= '4.06.00') {
            $screen_cmd .= ' -L -Logfile '.escapeshellarg($log);
        } else {
            $screen_cmd .= ' -L '.escapeshellarg($log);
        }
    }
    $screen_cmd .= ' ' . $cmd;
    $rc = 0;
    exec($screen_cmd, $output, $rc);
    $output = implode("\n", $output);
    return $rc;
}

/**
 * Función para dar formato a los mensajes de la aplicación.
 * @param string $string Mensaje al que se desea dar el formato
 * (contiene marcadores especiales).
 * @param bool $html Indica si el formato debe ser HTML o texto plano.
 * @return string Mensaje formateado en HTML o texto.
 */
function message_format(string $string, bool $html = true): string
{
    // preguntas frecuentes de la aplicación
    if (strpos($string, '[faq:') !== false) {
        $faq = (array)config('faq');
        // hay config de faqs -> se agrega enlace
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
        // no hay config de faqs -> se quita FAQ del mensaje
        else {
            $string = preg_replace(
                '/\[(faq):([\w\d]+)\]/i',
                '',
                $string
            );
        }
    }
    // cambios cuando es HTML (se pasa texto a HTML)
    if ($html) {
        // enlaces en formato markdown
        $string = preg_replace(
            '/\[([^\]]+)\]\((https?:\/\/[^\s]+)\)/',
            '<a href="$2" target="_blank" class="alert-link">$1</a>',
            $string
        );
        // flechas para instrucciones (tipo "siguiente")
        $string = str_replace('>>', '&raquo;', $string);
    }
    // entregar string modificado con los enlaces correspondientes
    return $string;
}
