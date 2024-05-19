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
 * Clase que provee el servicio de configuración para otras clases.
 * Principalmente usada para la configuración de toda la aplicación.
 */
class Configure
{

    // Valores de la configuración.
    protected $config = [];

    /**
     * Asignar un valor en la configuración.
     * Se puede pasar un arreglo con la configuración como un solo
     * parámetro.
     * @param string|array $config Ubicación de la configuración o
     * arreglo con la config.
     * @param mixed $value Valor que se quiere guardar.
     */
    public function set($config, $value = null): void
    {
        // Si config no es arreglo se crea como arreglo
        if (!is_array($config)) {
            $config = [$config => $value];
            unset($value);
        }
        // Guardar cada una de las configuraciones pasadas.
        foreach ($config as $selector => $value) {
            // Si el selector no tiene punto, entonces se crea directamente la variable
            if (strpos($selector, '.') === false) {
                $this->config[$selector] = $value;
            }
            // En caso que tuviese punto se asume que se debe dividir en niveles (hasta 4)
            else {
                $names = explode('.', $selector, 4);
                switch (count($names)) {
                    case 2: {
                        $this->config[$names[0]][$names[1]] = $value;
                        break;
                    }
                    case 3: {
                        $this->config[$names[0]][$names[1]][$names[2]] = $value;
                        break;
                    }
                    case 4: {
                        $this->config[$names[0]][$names[1]][$names[2]][$names[3]] = $value;
                        break;
                    }
                }
            }
        }
    }

    /**
     * Leer un valor desde la configuración.
     * @param string $selector Variable / parámetro que se desea leer.
     * @param mixed $default Valor por defecto de la variable buscada.
     * @return mixed Valor determinado de la variable (real, defecto o null).
     */
    public function get(string $selector = null, $default = null)
    {
        // Si el selector no se especificó se devuelven todas las
        // configuraciones.
        if ($selector === null) {
            return $this->config;
        }
        // Si el selector coincide con una clave del arreglo se devuelve.
        if (array_key_exists($selector, $this->config)) {
            return $this->config[$selector];
        }
        // Si no hay puntos en el selector es simplemente que la variable
        // solicitada no es válida.
        if (strpos($selector, '.') === false) {
            return $default;
        }
        // En caso que existan puntos se separa para obtener todos los
        // niveles del selector.
        $names = explode('.', $selector, 4);
        // Si la variable no existe se retorna el valor por defecto.
        if (!isset($this->config[$names[0]])) {
            return $default;
        }
        // Si se llegó aquí es porque la variable principal, primera
        // clave del arreglo de configuraciones, existe. Por lo que se
        // hace una búsqueda por nivel.
        switch (count($names)) {
            case 2: {
                if (isset($this->config[$names[0]][$names[1]])) {
                    return $this->config[$names[0]][$names[1]];
                }
                break;
            }
            case 3: {
                if (isset($this->config[$names[0]][$names[1]][$names[2]])) {
                    return $this->config[$names[0]][$names[1]][$names[2]];
                }
                break;
            }
            case 4: {
                if (isset($this->config[$names[0]][$names[1]][$names[2]][$names[3]])) {
                    return $this->config[$names[0]][$names[1]][$names[2]][$names[3]];
                }
                break;
            }
        }
        // Si no se encontró definida la variable se entrega el valor por defecto
        return $default;
    }

    /**
     * Asignar un valor en la configuración.
     * Se puede pasar un arreglo con la configuración como un solo
     * parámetro.
     * @param string|array $config Ubicación de la configuración o
     * arreglo con la config.
     * @param mixed $value Valor que se quiere guardar.
     * @deprecated Los métodos estáticos quedaron obsoletos. Usar set().
     */
    public static function write($config, $value = null): void
    {
        app('config')->set($config, $value);
    }

    /**
     * Leer un valor desde la configuración.
     * @param string $selector Variable / parámetro que se desea leer.
     * @param mixed $default Valor por defecto de la variable buscada.
     * @return mixed Valor determinado de la variable (real, defecto o null).
     * @deprecated Los métodos estáticos quedaron obsoletos. Usar get().
     */
    public static function read(string $selector = null, $default = null)
    {
        return app('config')->get($selector, $default);
    }

}
