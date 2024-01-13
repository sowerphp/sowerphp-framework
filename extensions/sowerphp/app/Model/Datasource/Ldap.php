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

namespace sowerphp\app;

/**
 * Modelo para trabajar con LDAP
 */
class Model_Datasource_Ldap extends \sowerphp\core\Model_Datasource
{
    public $config = [
        'host' => 'ldaps://localhost',
        'port' => 636,
        'user' => 'uid=zimbra,cn=admins,cn=zimbra',
        'version' => 3,
        'timeout' => 3,
        'person_uid' => 'usuario',
    ]; ///< Configuración de la fuente de datos
    protected $link; ///< Conexión al servidor LDAP

    /**
     * Método que permite obtener un objeto Ldap
     * @param name Nombre de la configuración o arreglo con la configuración
     * @param config Arreglo con la configuración
     */
    public static function &get($name = 'default', $config = [])
    {
        $config = parent::getDatasource('ldap', $name, $config);
        if (is_object($config)) {
            return $config;
        }
        $class = __CLASS__;
        self::$datasources['ldap'][$config['conf']] = new $class($config);
        return self::$datasources['ldap'][$config['conf']];
    }

    /**
     * Constructor de la clase
     * @param config Arreglo con la configuración
     */
    public function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
        if (!$this->connect()) {
            throw new \sowerphp\core\Exception (array(
                'msg' => error_get_last()['message']
            ));
        }
    }

    /**
     * Método que realiza la conexión con el servidor LDAP
     * @return bool =true si se pudo realizar la conexión
     */
    private function connect()
    {
        $this->link = ldap_connect(
            $this->config['host'], $this->config['port']
        );
        if (!$this->link) {
            return false;
        }
        if (!ldap_set_option($this->link, LDAP_OPT_PROTOCOL_VERSION, $this->config['version'])) {
            return false;
        }
        if (!ldap_set_option($this->link, LDAP_OPT_NETWORK_TIMEOUT, $this->config['timeout'])) {
            return false;
        }
        $status = @ldap_bind(
            $this->link, $this->config['user'], $this->config['pass']
        );
        return !$status ? false : true;
    }

    /**
     * Método para obtener el valor de una opción del servidor LDAP
     * @param option Opción que se desea consultar (una de http://php.net/manual/en/ldap.constants.php)
     * @return mixed Valor de la opción o null si no se pudo determinar
     */
    public function getOption($option)
    {
        $val = null;
        if (!ldap_get_option($this->link, $option, $val)) {
            return null;
        }
        return $val;
    }

    /**
     * Método que cierra la conexión con el servidor LDAP
     */
    public function __destruct()
    {
        if (is_resource($this->link)) {
            ldap_close($this->link);
        }
    }

    /**
     * Método que realiza sanitizado de caracteres especiales de acuerdo a la RFC 2254
     * @link http://www.ietf.org/rfc/rfc2254.txt
     * @param string String que se quiere sanitizar
     * @return string String sanitizado
     */
    public function sanitize($string)
    {
        return str_replace(
            ['*',   '(',   ')',   '\\',  "\x00"],
            ['\2a', '\28', '\29', '\5c', '\00'],
            $string
        );
    }

    /**
     * Método que modifica una entrada en el servidor LDAP
     * @param dn distinguished name del objeto a modificar
     * @param entrey Arreglo asociativo con los nuevos valores para la entrada
     * @return bool =true si se pudo hacer la modificación
     */
    public function modify($dn, $entry)
    {
        return ldap_modify($this->link, $dn, $entry);
    }

    /**
     * Método que entrega el dn base el servidor LDAP
     * @return dn base
     */
    public function getBaseDN()
    {
        return $this->config['base'];
    }

    /**
     * Método que obtiene una entrada desde el servidor LDAP
     */
    public function getEntries($base_dn, $filter, $attributes = [])
    {
        $r = ldap_search($this->link, $base_dn, $filter, $attributes);
        if (!ldap_count_entries($this->link, $r)) {
            return false;
        }
        $entry = ldap_get_entries($this->link, $r);
        ldap_free_result($r);
        return $entry;
    }

    /**
     * Método que obtiene un objeto de tipo Persona con los datos desde el
     * servidor LDAP
     * @param uid Identificador de la persona (nombre de usuario)
     * @return Model_Datasource_Ldap_Person
     */
    public function getPerson($uid)
    {
        return new Model_Datasource_Ldap_Person($uid, $this);
    }

}
