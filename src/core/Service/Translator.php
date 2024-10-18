<?php

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

namespace sowerphp\core;

use Illuminate\Support\Arr;
use Illuminate\Contracts\Translation\Translator;

class Service_Translator implements Interface_Service, Translator
{
    /**
     * Lenguaje por defecto.
     *
     * Es el lenguaje de la configuración. En el que se espera que estén los
     * textos cuando no se utilizan claves y se usa directamente el texto al
     * solicitar la traducción. Además, es el lenguaje por defecto si no está
     * definido uno en la sesión.
     *
     * @var string
     */
    protected $fallbackLocale;

    /**
     * Lenguaje en uso.
     *
     * @var string
     */
    protected $locale;

    /**
     * Traducciones cargadas para el lenguaje definido.
     *
     * @var array
     */
    protected $translations = [];

    /**
     * Espacios de nombres para las traducciones.
     *
     * @var array
     * @todo Implementar uso de namespaces para traducciones en paquetes.
     */
    protected $namespaces = [];

    /**
     * Registra el servicio de internacionalización.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio de internacionalización.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->setFallback('es');
        $this->setLocale(config('app.locale', $this->getFallback()));
        $this->loadLocale($this->getLocale());
    }

    /**
     * Método que carga un locale.
     *
     * Se determina el locale que se usará (se puede llamar al método sin
     * parámetros) y se cargarán las traducciones del locale.
     *
     * @param string|null $locale Locale que se desea cargar. Puede ser null y
     * se tratará de determinar en base a un valor por defecto o de manera
     * automática.
     * @param string|null $default Locale que se cargará como segunda opción si
     * el locale principal es un valor null.
     * @return string Locale que se cargó.
     */
    protected function loadLocale(?string $locale = null): string
    {
        if ($locale === null) {
            $locale = $this->getLocale();
        }
        if (!isset($this->translations[$locale])) {
            $this->translations[$locale] = $this->loadTranslations($locale);
        }

        return $locale;
    }

    /**
     * Carga archivos de traducción de un directorio especificado.
     *
     * Este método recorre todos los archivos PHP en el directorio dado y los
     * carga como arrays de traducciones. Cada archivo debe retornar un array
     * asociativo donde las claves son las cadenas a traducir y los valores las
     * traducciones correspondientes.
     *
     * @param string $path El camino al directorio donde se encuentran los
     * archivos de traducción.
     * @return array Devuelve un array asociativo con todas las traducciones
     * cargadas de los archivos en el directorio especificado.
     */
    protected function loadTranslations(string $locale): array
    {
        $path = resource_path("lang/{$locale}");
        $translations = [];
        foreach (glob("{$path}/*.php") as $file) {
            $translations[basename($file, '.php')] = require $file;
        }

        return $translations;
    }

    /**
     * Finaliza el servicio de internacionalización.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Determina si existe una traducción para una clave dada.
     *
     * @param string $key
     * @param string|null $locale
     * @return bool
     */
    public function has(string $key, ?string $locale = null): bool
    {
        $locale = $this->loadLocale($locale);

        return isset($this->translations[$locale][$key]);
    }

    /**
     * Alias para el método get, para obtener la traducción de una cadena.
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string|array|null
     */
    public function trans(string $key, array $replace = [], string $locale = null)
    {
        return $this->get($key, $replace, $locale);
    }

    /**
     * Obtiene la traducción de una cadena.
     *
     * Función para traducción de strings mediante le servicio de lenguages con
     * soporte de interpolación mixta. Permite utilizar tanto el formato de
     * placeholders de Python (%(name)s) como el formato clásico (%s, %d, %f)
     * y el formato simulado de parámetros SQL (:name) para uniformidad en
     * la definición de strings. Además, la función automáticamente ajusta
     * los placeholders que no incluyen el formato específico (%( )s).
     *
     * Ejemplos de uso:
     *
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
     *
     * @param string $key Texto que se desea traducir.
     * @param array $replace Arreglo asociativo para reemplazar en el string.
     * @param string|null $locale Lenguaje al que se traducirá el texto.
     * @return string|array Texto traducido con los placeholders reemplazados.
     * @todo Implementar retorno de arreglos traducidos.
     */
    public function get($key, array $replace = [], $locale = null)
    {
        $locale = $this->loadLocale($locale);

        // Buscar la traducción por llave.
        $default = null;
        $translation = Arr::get($this->translations[$locale], $key, $default);

        // Si la trauducción no se encontró, podría haberse pasado directamente
        // el string que se desea traducir.
        if ($translation === null) {
            $translation = $this->searchAndTranslate($key, $locale);
        }

        // Si no hay atributos para reemplazar se retorna lo traducido.
        if (empty($replace)) {
            return $translation;
        }

        // Se reemplazan los atributos en el string traducido.
        return $this->replaceAttributes($translation, $replace);
    }

    /**
     * Busca la traducción de un texto entre dos locales y la devuelve.
     *
     * Este método busca la clave correspondiente a un texto dado en el locale
     * de origen, y luego utiliza esta clave para obtener la traducción en el
     * locale de destino. Si el locale de destino es el mismo que el de origen,
     * devuelve el texto original sin realizar la búsqueda.
     *
     * @param string $string El texto que se desea traducir.
     * @param string|null $toLocale El locale al que se traducirá el texto. Si
     * no se especifica, se usa el valor de $this->loadLocale().
     * @param string|null $fromLocale El locale desde el que se traducirá el
     * texto. Si no se especifica, se usa el valor de $this->locale.
     * @param string|null $default El valor predeterminado que se devolverá si
     * no se encuentra la traducción. Si no se proporciona, se devolverá null.
     * @return string|null La traducción del texto, el texto original si los
     * locales son iguales, o el valor predeterminado si no se encuentra la
     * traducción.
     */
    protected function searchAndTranslate(string $string, string $toLocale): string
    {
        $fallbackLocale = $this->getFallback();
        // Si el lenguage al que se desea traducir es el mismo de origen se
        // entrega el texto directamente (no es necesario traducir).
        if ($toLocale == $fallbackLocale) {
            return $string;
        }

        // Si los lenguajes son diferentes se debe buscar la llave del texto en
        // el lenguaje de origen y luego buscar el texto con esa llave en el
        // lenguaje de destino.
        $translation = null;
        $this->loadLocale($fallbackLocale);
        $keyFrom = $this->findKeyByValue(
            $this->translations[$fallbackLocale],
            $string
        );
        if ($keyFrom !== null) {
            $translation = Arr::get($this->translations[$toLocale], $keyFrom);
        }
        if ($translation === null) {
            $translation = Arr::get($this->translations[$fallbackLocale], $string);
        }

        return $translation ?? $string;
    }

    /**
     * Función que busca un valor en un arreglo. Permite hacer la búsqueda de
     * manera recursiva con múltiples niveles de anidamiento.
     *
     * Dado que esta función realiza una búsqueda exhaustiva, puede ser costosa
     * en términos de rendimiento con arreglos muy grandes o muy anidados.
     *
     * @param array $array
     * @param string $searchValue
     * @param string $currentPath
     * @return string|null
     */
    protected function findKeyByValue(
        array $array,
        string $searchValue,
        string $currentPath = ''
    ): ?string
    {
        foreach ($array as $key => $value) {
            // Construir la ruta de la clave actual en formato de puntos
            $path = $currentPath === '' ? $key : $currentPath . '.' . $key;

            // Verificar si el valor actual es el buscado
            if (is_string($value) && $value === $searchValue) {
                return $path;
            }

            // Si el valor es un arreglo, hacer una llamada recursiva
            if (is_array($value)) {
                $foundPath = $this->findKeyByValue($value, $searchValue, $path);
                if ($foundPath !== null) {
                    return $foundPath;
                }
            }
        }

        // Devolver null si no se encuentra el valor
        return null;
    }

    /**
     * Reemplaza los placeholders en una cadena con los valores proporcionados.
     *
     * Este método soporta dos formatos de placeholders: nombrados y anónimos.
     * Si el arreglo de reemplazo es asociativo, busca y reemplaza placeholders
     * nombrados que comienzan con '%(' o ':'; si no, añade '%(' y ')s' a cada
     * clave para formar el placeholder. Si el arreglo no es asociativo, asume
     * que los placeholders son secuenciales del tipo "%s" y los reemplaza en
     * orden usando vsprintf.
     *
     * @param string $string La cadena con placeholders para reemplazar.
     * @param array $replace Arreglo de valores para reemplazar en la cadena.
     * Puede ser asociativo para placeholders nombrados o secuencial para
     * placeholders anónimos como "%s".
     * @return string La cadena con todos los placeholders reemplazados.
     */
    protected function replaceAttributes($string, array $replace): string
    {
        // Si se usó un arreglo asociativo se reemplaza con los posibles
        // placeholders que se pueden utilizar.
        if (Arr::isAssoc($replace)) {
            $placeholders = [];
            foreach ($replace as $key => $value) {
                if (in_array($key[0], ['%(', ':'])) {
                    $placeholders[$key] = $value;
                } else {
                    $placeholders['%(' . $key . ')s'] = $value;
                }
            }
            return strtr($string, $placeholders);
        }

        // Si se usó un arreglo con índices numéricos (no asociativo) se usaron
        // placeholders anónimos, como "%s", y se utiliza vsprintf para su
        // reemplazo.
        else {
            return vsprintf($string, $replace);
        }
    }

    /**
     * Establece el idioma actual.
     *
     * @param string $locale
     * @return void
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Obtiene el idioma actual.
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Establece el idioma de respaldo.
     *
     * @param string $fallback
     * @return void
     */
    public function setFallback(string $fallback)
    {
        $this->fallbackLocale = $fallback;
    }

    /**
     * Obtiene el idioma de respaldo.
     *
     * @return string
     */
    public function getFallback(): string
    {
        return $this->fallbackLocale;
    }

    /**
     * Obtiene la traducción para una clave dada basada en una cantidad.
     *
     * @param string $key
     * @param \Countable|int|array $number
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    public function choice($key, $number, array $replace = [], $locale = null): string
    {
        $locale = $this->loadLocale($locale);
        $translation = $this->getPluralTranslation($key, $number, $locale);

        return $this->replaceAttributes($translation, $replace);
    }

    /**
     * Obtiene la traducción plural para la clave dada y la cantidad.
     *
     * @param string $key
     * @param \Countable|int|array $number
     * @param string $locale
     * @return string
     */
    protected function getPluralTranslation($key, $number, $locale)
    {
        $translations = $this->translations[$locale][$key] ?? null;
        if (!$translations) {
            return $key;
        }

        if (is_array($translations)) {
            return $translations[$number > 1 ? 'plural' : 'singular'] ?? $key;
        }

        return $translations;
    }
}
