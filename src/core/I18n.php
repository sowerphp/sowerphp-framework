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

/**
 * Clase para manejar la internacionalización
 */
class I18n
{

    public static $locale = [
        'es' => 'es_CL.utf8',
        'en' => 'en_US.utf8',
        'it' => 'it_IT.utf8',
    ]; ///< Mapeo de idioma a locale

    /**
     * Función que realiza la traducción de un string a otro idioma.
     *
     * Plantilla para archivo master.po (para locale en_US.utf8):
     *
     *	msgid ""
     *	msgstr ""
     *	"Project-Id-Version: proyecto en_US master\n"
     *	"PO-Revision-Date: 2014-03-02 11:37-0300\n"
     *	"Last-Translator: Nombre del traductor <traductor@example.com>\n"
     *	"Language-Team: English\n"
     *	"Language: en_US\n"
     *	"MIME-Version: 1.0\n"
     *	"Content-Type: text/plain; charset=UTF-8\n"
     *	"Content-Transfer-Encoding: 8bit\n"
     *	"Plural-Forms: nplurals=2; plural=(n != 1);\n"
     *
     *	msgid "Buscar"
     *	msgstr "Search"
     *
     * Guardar la plantilla en /resources/locale/en_US.utf8/LC_MESSAGES/master.po
     * Luego ejecutar:
     *   $ msgfmt master.po -o master.mo
     *
     * En caso que se esté creando desde un archivo pot se debe crear el archivo po con:
     *   $ msginit --locale=en_US.utf8 --input=master.pot
     * Lo anterior creará el archivo en_US.po y luego se usa msgfmt con este archivo
     *
     * La locale que se esté utilizando debe existir en el sistema, verificar con:
     *   $ locale -a
     * En caso que no exista editar /etc/locale.gen para agregarla y luego ejecutar:
     *   # locale-gen
     *
     * Cuando se crean o modifican los directorios en /resources/locale se
     * debe reiniciar el servicio Apache (¿?)
     *
     * @param string Texto que se desea traducir
     * @param domain Dominio que se desea utilizar para la traducción
     * @param locale Localee (o idioma) al cual traducir el texto
     * @param encoding Tipo de codificación de las traducciones
     */
    public static function translate($string, $domain = 'master', $locale = null, $encoding = 'UTF-8')
    {
        if (!$locale) {
            try {
                $locale = app('session')->get('config.language');
            } catch (\Exception $e) {
                $locale = null;
            }
        }
        if (!$locale || !strpos($locale, '_')) {
            if (!$locale || !isset(self::$locale[$locale])) {
                $locale = config('language');
            }
            $locale = self::$locale[$locale];
        }
        putenv("LANG=".$locale);
        setlocale(LC_MESSAGES, $locale);
        bindtextdomain($domain, app('layers')->getProjectDir('/resources/locale'));
        textdomain($domain);
        bind_textdomain_codeset($domain, $encoding);
        return gettext($string);
    }

    /**
     * Método que verifica si el lenguaje solicitado existe
     * @param locale Locale que se está buscando si existe
     * @return bool =true si la traducción está disponible (existe el directorio
     */
    public static function localeExists($locale)
    {
        if (!isset($locale[0])) {
            return false;
        }
        if (!strpos($locale, '_') && isset(self::$locale[$locale])) {
            $locale = self::$locale[$locale];
        }
        return is_dir(app('layers')->getProjectDir('/resources/locale/' . $locale));
    }

}
