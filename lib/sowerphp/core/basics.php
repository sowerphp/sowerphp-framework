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
 * Función que carga una variable de entorno o su valor por defecto
 * @param varname Variable que se desea consultar
 * @param default Valor por defecto de la variable
 */
function env($varname, $default = null)
{
    if (isset($_ENV[$varname])) {
        return $_ENV[$varname];
    }
    $value = getenv($varname);
    if ($value !== false) {
        return $value;
    }
    return $default;
}

/**
 * Función que entrega la ruta completa (URL) de un recurso (path) de la aplicación
 * @param resource Recurso (path) que se desea resolver
 * @return string URL completa que resuelve el recurso (path)
 */
function url($resource = '/')
{
    $url = (string)\sowerphp\core\Configure::read('app.url');
    if (!$url) {
        $url = (new \sowerphp\core\Network_Request())->url;
    }
    if (!$url) {
        throw new \Exception(__('No fue posible determinar la URL completa del recurso %s', $resource));
    }
    if (strpos($resource, '/static/') === 0) {
        $url_static = \sowerphp\core\Configure::read('app.url_static');
        if ($url_static !== null) {
            $url = $url_static;
            $resource = substr($resource, 7);
        }
    }
    return $resource == '/' ? $url : $url.$resource;
}

/**
 * Función para mostrar el valor de una variable (y su tipo) o un objeto (y su
 * clase)
 * @param var Variable que se desea mostrar
 * @param withtype Si es verdadero se usará "var_dump" sino "print_r"
 */
function debug($var, $withtype = false)
{
    if (isset($_SERVER['REQUEST_URI'])) {
        echo '<pre>';
        if ($withtype) {
            var_dump($var);
        } else {
            print_r($var);
        }
        echo '</pre>',"\n";
    } else {
        if ($withtype) {
            var_dump($var);
        } else {
            print_r($var);
        }
        echo "\n";
    }
}

/**
 * Función para formatear números con soporte para idioma y dígitos decimales.
 *
 * @param mixed $n Número a formatear.
 * @param mixed $d Cantidad de decimales o true para usar 2. Si no se proporciona, se asume 0.
 * @param string|null $language Idioma que se debe utilizar para formatear el número.
 *                              Si no se pasa, se utiliza la función get_language() para obtenerlo.
 * @return string Número formateado según la configuración regional.
 */
function num($n, $d = 0, $language = null)
{
    if (empty($n)) {
        return '0';
    }

    if (!is_numeric($n)) {
        trigger_error("num: El argumento proporcionado no es un número válido.", E_USER_WARNING);
        return $n;
    }

    $n = (float)$n;

    if ($d === true) {
        $d = 2;
    }

    if (!is_int($d) || $d < 0) {
        trigger_error("num: La cantidad de dígitos decimales proporcionada no es válida.", E_USER_WARNING);
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
 * Función para traducción de string singulares, en dominio master.
 * @param string Texto que se desea traducir
 * @param args Argumentos para reemplazar en el string, puede ser un arreglo o bien n argumentos a la función
 * @return string Texto traducido
 */
function __($string, $args = null)
{
    if (!is_array($args)) {
        $args = array_slice(func_get_args(), 1);
    }
    return __d('master', $string, $args);
}

/**
 * Función para traducción de string singulares, eligiendo dominio.
 * @param string Texto que se desea traducir
 * @param args Argumentos para reemplazar en el string, puede ser un arreglo o bien n argumentos a la función
 * @return string Texto traducido
 */
function __d($dominio, $string, $args = null)
{
    // si no hay argumentos solo se retorna el texto traducido
    if (!$args) {
        return \sowerphp\core\I18n::translate($string, $dominio);
    }
    // si los argumentos no son un arreglo se obtiene arreglo a partir
    // de los argumentos pasados a la función
    if (!is_array($args)) {
        $args = array_slice(func_get_args(), 2);
    }
    return vsprintf(\sowerphp\core\I18n::translate($string, $dominio), $args);
}

/**
 * Función que permite ejecutar un comando en la terminal
 */
function shell_exec_async($cmd, $log = false, &$output = [])
{
    $cmd = trim($cmd);
    if (empty($cmd)) {
        return 255;
    }
    if ($cmd[0]!='/') {
        $cmd = DIR_PROJECT.'/website/Shell/shell.php '.$cmd;
    }
    $screen_cmd = 'screen -dm';
    if ($log) {
        if (!is_string($log)) {
            $log = TMP.'/screen_'.microtime(true).'.log';
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
    $screen_cmd .= ' '.$cmd;
    $rc = 0;
    exec($screen_cmd, $output, $rc);
    $output = implode("\n", $output);
    return $rc;
}

/**
 * Función para dar formato a los mensajes de la aplicación
 * @param string Mensaje al que se desea dar el formato (contiene marcadores especiales)
 * @param html Indica si el formato debe ser HTML o texto plano
 * @return string Mensaje formateado en HTML o texto plano según se solicitó
 */
function message_format($string, $html = true)
{
    // preguntas frecuentes de la aplicación
    if (strpos($string, '[faq:') !== false) {
        $faq = (array)\sowerphp\core\Configure::read('faq');
        // hay config de faqs -> se agrega enlace
        if (!empty($faq['url']) && !empty($faq['text'])) {
            $replace = $html
                        ? '<a href="'.$faq['url'].'$2" target="_blank" class="alert-link">'.$faq['text'].'</a>'
                        : $faq['text'].': '.$faq['url'].'$2';
            $string = preg_replace(
                '/\[(faq):([\w\d]+)\]/i',
                $replace,
                $string
            );
        }
        // no hay config de faqs -> se quita FAQ del mensaje
        else {
            $string = preg_replace('/\[(faq):([\w\d]+)\]/i', '', $string);
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
