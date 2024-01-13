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
 * Modelo para trabajar con una persona de LDAP
 */
class Model_Datasource_Ldap_Person extends Model_Datasource_Ldap_Entry
{

    public $dn;
    public $uid;
    public $cn;
    public $givenName;
    public $sn;
    public $displayName;
    public $title;
    public $company;
    public $mail;
    public $mobile;
    public $userPassword;

    protected $Ldap = null; ///< Objeto que representa la conexión al servidor LDAP

    /**
     * Constructor del modelo
     * @param uid UID de la entrada de la persona o bien el arreglo de LDAP con la entrada
     * @param ldap Configuración para LDAP o nombre de la configuración
     */
    public function __construct($uid = null, $Ldap = 'default')
    {
        $this->Ldap = is_object($Ldap) ? $Ldap : Model_Datasource_Ldap::get($Ldap);
        if ($uid) {
            $this->setFromEntry(is_array($uid) ? $uid : $this->Ldap->getEntries(
                'ou=people,'.$this->Ldap->getBaseDN(),
                'uid='.$this->Ldap->sanitize($uid)
            )[0]);
        }
    }

    /**
     * Método que indica si la persona existe o no en el servidor LDAP
     * @return bool =true si la persona existe
     */
    public function exists()
    {
        return (bool)$this->dn;
    }

    /**
     * Método que cambia la contraseña del usuario
     * @param new Contraseña nueva en texto plano
     * @param old Contraseña actual en texto plano
     * @return bool =true si la contraseña pudo ser cambiada
     */
    public function savePassword($new, $old = null)
    {
        $entry = [
            'userPassword' => [$this->hashPassword($new)]
        ];
        $status = $this->Ldap->modify($this->dn, $entry);
        if ($status) {
            $this->userPassword = $entry['userPassword'][0];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Método que valida si la contraseña que se pasa como argumento es la
     * contraseña del usuario
     * @link http://php.net/manual/en/function.sha1.php#40226
     * @param string $plain Contraseña en texto plano que se desea validar
     * @return bool =true si la contraseña es correcta
     */
    public function checkPassword(string $plain)
    {
        if (substr($this->userPassword,0,6) == '{SSHA}') {
            $hash = base64_decode(substr($this->userPassword, 6));
            $original_hash = substr($hash, 0, 20);
            $salt = substr($hash, 20);
            $new_hash = mhash(MHASH_SHA1, $plain.$salt);
            return !(bool)strcmp($original_hash, $new_hash);
        }
        return false;
    }

    /**
     * Función para calcular hash de un texto plano usando SSHA
     * @link http://php.net/manual/en/function.sha1.php#40226
     * @param string $plain Texto plano
     * @return string Hash SSHA usando salt
     */
    public function hashPassword(string $plain)
    {
        mt_srand((double)microtime() * 1000000);
        $salt = mhash_keygen_s2k(MHASH_SHA1, $plain, substr(pack('h*', md5(mt_rand())), 0, 8), 4);
        return '{SSHA}'.base64_encode(mhash(MHASH_SHA1, $plain.$salt).$salt);
    }

}
