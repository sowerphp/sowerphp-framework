<?php

/**
 * SowerPHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
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
 * Fuente de datos para trabajar con Zimbra
 *
 * Se puede obtener la contraseña (y el servidor LDAP) a través del comando:
 *   $ zmlocalconfig -s zimbra_ldap_password ldap_master_url
 *
 * Para explorar el árbol de Zimbra usar:
 *   $ ldapsearch -x -H ldaps://localhost -D uid=zimbra,cn=admins,cn=zimbra -W '(objectclass=*)'
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-12-30
 */
class Model_Datasource_Zimbra extends \sowerphp\core\Model_Datasource
{

    public $config = [
        'ldap' => 'default',
        'sslv3' => false,
        'sslcheck' => true,
    ]; ///< Configuración de la fuente de datos
    public $Ldap; ///< Fuente de datos Ldap para el servidor Zimbra

    /**
     * Método que permite obtener un objeto Zimbra
     * @param name Nombre de la configuración o arreglo con la configuración
     * @param config Arreglo con la configuración
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-12-29
     */
    public static function &get($name = 'default', $config = [])
    {
        $config = parent::getDatasource('zimbra', $name, $config);
        if (is_object($config)) return $config;
        $class = __CLASS__;
        self::$datasources['zimbra'][$config['conf']] = new $class($config);
        return self::$datasources['zimbra'][$config['conf']];
    }

    /**
     * Constructor de la clase
     * @param config Arreglo con la configuración
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-12-29
     */
    public function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
        $this->Ldap = Model_Datasource_Ldap::get($this->config['ldap']);
    }

    /**
     * Método que obtiene la clave para preautenticación
     * @return zimbraPreAuthKey
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-12-29
     */
    public function getPreAuthKey()
    {
        if (!isset($this->config['preAuthKey'])) {
            $this->config['preAuthKey'] = $this->Ldap->getEntries(
                $this->Ldap->getBaseDN(),
                'objectClass=zimbraDomain',
                ['zimbraPreAuthKey']
            )[0]['zimbrapreauthkey'][0];
        }
        return $this->config['preAuthKey'];
    }

    /**
     * Método que ejecuta un comando SOAP en el servicio web de Zimbra
     * @param cmd Comando que se desea ejecutar
     * @param args Arreglo con los argumentos del comando
     * @return Resultado de la ejecución del comando
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-01-02
     */
    public function soap($cmd, $args = [])
    {
        $soap = new \SoapClient(
            'https://'.$this->config['host'].'/service/wsdl/ZimbraService.wsdl',
            [
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'verify_peer' => $this->config['sslcheck'],
                        'allow_self_signed' => !$this->config['sslcheck'],
                    ]
                ])
            ]
        );
        return (array)$soap->$cmd($args);
    }

    /**
     * Método que obtiene un objeto de tipo Account de Zimbra
     * @param uid Identificador de la cuenta (nombre de usuario)
     * @return Model_Datasource_Zimbra_Account
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-12-29
     */
    public function getAccount($uid)
    {
        return new Model_Datasource_Zimbra_Account($uid, $this);
    }

}
