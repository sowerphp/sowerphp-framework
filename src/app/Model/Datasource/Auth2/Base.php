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

namespace sowerphp\app;

/**
 * Clase base para los sistemas de autenticación secundaria
 */
abstract class Model_Datasource_Auth2_Base
{

    protected $Auth2; ///< Instancia del objeto con la autenticación secundaria
    protected $config; ///< Configuración de la autenticación secundaria
    protected $need_token = false; ///< Por defecto los métodos no requieren token, se debe indicar en cada clase

    /**
     * Método que entrega el código del método de autenticación secundaria
     */
    public function getCode()
    {
        return \sowerphp\core\Utility_String::normalize($this->getName());
    }

    /**
     * Método que entrega todas las autenticaciones secundarias disponibles en la aplicación
     */
    public function getName()
    {
        return $this->config['name'];
    }

    /**
     * Método que la URL asociada al servicio secundario o su página con información
     */
    public function getUrl()
    {
        return isset($this->config['url']) ? $this->config['url'] : null;
    }

    /**
     * Método que crea el secreto en caso que sea requerido para hacer el pareo
     * Por defecto no se usa y se entrega falso
     */
    public function createSecret($user = null)
    {
        return false;
    }

    /**
     * Método que indica si el método de autenticación secundaria usa o no un token
     */
    public function needToken()
    {
        return $this->need_token;
    }

}
