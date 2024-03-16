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

// namespace del modelo
namespace sowerphp\app\Sistema\Usuarios;

/**
 * Clase para mapear la tabla usuario de la base de datos
 * Comentario de la tabla: Usuarios de la aplicación
 * Esta clase permite trabajar sobre un registro de la tabla usuario
 */
class Model_Usuario extends \Model_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'usuario'; ///< Tabla del modelo

    public static $fkNamespace = array(); ///< Namespaces que utiliza esta clase

    // Atributos de la clase (columnas en la base de datos)
    public $id; ///< Identificador (serial): integer(32) NOT NULL DEFAULT 'nextval('usuario_id_seq'::regclass)' AUTO PK
    public $nombre; ///< Nombre real del usuario: character varying(50) NOT NULL DEFAULT ''
    public $usuario; ///< Nombre de usuario: character varying(30) NOT NULL DEFAULT ''
    public $usuario_ldap; ///< Nombre de usuario de LDAP: character varying(30) NOT NULL DEFAULT ''
    public $email; ///< Correo electrónico del usuario: character varying(50) NOT NULL DEFAULT ''
    public $contrasenia; ///< Contraseña del usuario: character(255) NOT NULL DEFAULT ''
    public $contrasenia_intentos; ///< Intentos de inicio de sesión antes de bloquear cuenta: SMALLINT(6) NOT NULL DEFAULT '3'
    public $hash; ///< Hash único del usuario (32 caracteres): character(32) NOT NULL DEFAULT ''
    public $token; ///< Token para servicio secundario de autorización: character(64) NULL DEFAULT ''
    public $activo; ///< Indica si el usuario está o no activo en la aplicación: boolean() NOT NULL DEFAULT 'true'
    public $ultimo_ingreso_fecha_hora; ///< Fecha y hora del último ingreso del usuario: timestamp without time zone() NULL DEFAULT ''
    public $ultimo_ingreso_desde; ///< Dirección IP del último ingreso del usuario: character varying(45) NULL DEFAULT ''
    public $ultimo_ingreso_hash; ///< Hash del último ingreso del usuario: character(32) NULL DEFAULT ''

    // Información de las columnas de la tabla en la base de datos
    public static $columnsInfo = array(
        'id' => array(
            'name'      => 'ID',
            'comment'   => 'Identificador (serial)',
            'type'      => 'integer',
            'length'    => 32,
            'null'      => false,
            'default'   => "nextval('usuario_id_seq'::regclass)",
            'auto'      => true,
            'pk'        => true,
            'fk'        => null
        ),
        'nombre' => array(
            'name'      => 'Nombre',
            'comment'   => 'Nombre real del usuario',
            'type'      => 'character varying',
            'length'    => 50,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'usuario' => array(
            'name'      => 'Usuario',
            'comment'   => 'Nombre de usuario',
            'type'      => 'character varying',
            'length'    => 30,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'usuario_ldap' => array(
            'name'      => 'Usuario LDAP',
            'comment'   => 'Nombre de usuario de LDAP',
            'type'      => 'character varying',
            'length'    => 30,
            'null'      => true,
            'default'   => "",
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'email' => array(
            'name'      => 'Email',
            'comment'   => 'Correo electrónico del usuario',
            'type'      => 'character varying',
            'length'    => 50,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => false,
            'fk'        => null,
            'check'     => ['email'],
        ),
        'contrasenia' => array(
            'name'      => 'Contraseña',
            'comment'   => 'Contraseña del usuario',
            'type'      => 'character',
            'length'    => 255,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'contrasenia_intentos' => array(
            'name'      => 'Contraseña Intentos',
            'comment'   => 'Intentos de inicio de sesión antes de bloquear cuenta',
            'type'      => 'smallint',
            'length'    => 6,
            'null'      => false,
            'default'   => "3",
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'hash' => array(
            'name'      => 'Hash',
            'comment'   => 'Hash único del usuario (32 caracteres)',
            'type'      => 'character',
            'length'    => 32,
            'null'      => false,
            'default'   => "",
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'token' => array(
            'name'      => 'Token',
            'comment'   => 'Token para servicio secundario de autorización',
            'type'      => 'character',
            'length'    => 64,
            'null'      => true,
            'default'   => "",
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'activo' => array(
            'name'      => 'Activo',
            'comment'   => 'Indica si el usuario está o no activo en la aplicación',
            'type'      => 'boolean',
            'length'    => null,
            'null'      => false,
            'default'   => "true",
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),
        'ultimo_ingreso_fecha_hora' => array(
            'name'      => 'Último ingreso',
            'comment'   => 'Fecha y hora del último ingreso del usuario',
            'type'      => 'timestamp without time zone',
            'length'    => null,
            'null'      => true,
            'default'   => "",
            'auto'      => true,
            'pk'        => false,
            'fk'        => null
        ),
        'ultimo_ingreso_desde' => array(
            'name'      => 'Última IP',
            'comment'   => 'Dirección IP del último ingreso del usuario',
            'type'      => 'character varying',
            'length'    => 45,
            'null'      => true,
            'default'   => "",
            'auto'      => true,
            'pk'        => false,
            'fk'        => null
        ),
        'ultimo_ingreso_hash' => array(
            'name'      => 'Último hash',
            'comment'   => 'Hash del último ingreso del usuario',
            'type'      => 'character',
            'length'    => 32,
            'null'      => true,
            'default'   => "",
            'auto'      => true,
            'pk'        => false,
            'fk'        => null
        ),

    );

    // Comentario de la tabla en la base de datos
    public static $tableComment = 'Usuarios de la aplicación';

    // atributos para caché
    protected $groups = null; ///< Grupos a los que pertenece el usuario
    protected $auths = null; ///< Permisos que tiene el usuario
    protected $LdapPerson = null; ///< Caché para objeto Model_Datasource_Ldap_Person (y para Model_Datasource_Zimbra_Account)

    // configuración asociada a la tabla usuario_config (configuración extendida y personalizada según la app)
    public static $config_encrypt = []; ///< columnas de la configuración que se deben encriptar para guardar en la base de datos
    public static $config_default = []; ///< valores por defecto para columnas de la configuración en caso que no estén especificadas
    protected $config = null; ///< Caché para configuraciones

    /**
     * Constructor de la clase usuario
     * Permite crear el objeto usuario ya sea recibiendo el id del usuario, el
     * email, el nombre de usuario o el hash de usuario.
     */
    public function __construct($id = null)
    {
        if ($id !== null && !is_array($id) && !is_numeric($id)) {
            $this->db = \sowerphp\core\Model_Datasource_Database::get($this->_database);
            // se crea usuario a través de su correo electrónico
            if (strpos($id, '@')) {
                $id = $this->getDB()->getValue('
                    SELECT id
                    FROM usuario
                    WHERE email = :email
                ', [':email' => mb_strtolower($id)]);
            }
            // se crea usuario a través de su nombre de usuario
            else if (!isset($id[31])) {
                $id = $this->getDB()->getValue('
                    SELECT id
                    FROM usuario
                    WHERE usuario = :usuario
                ', [':usuario' => $id]);
            }
            // se crea usuario a través de su hash
            else {
                $id = $this->getDB()->getValue('
                    SELECT id
                    FROM usuario
                    WHERE hash = :hash
                ', [':hash' => $id]);
            }
        }
        parent::__construct((int)$id);
        $this->getConfig();
    }

    /**
     * Método que entrega las configuraciones y parámetros extras para el
     * usuario
     */
    public function getConfig()
    {
        if ($this->config === false || !$this->id || !class_exists('\sowerphp\app\Sistema\Usuarios\Model_UsuarioConfig')) {
            return null;
        }
        if ($this->config === null) {
            if (!$this->db) {
                $this->db = \sowerphp\core\Model_Datasource_Database::get($this->_database);
            }
            $config = $this->getDB()->getAssociativeArray('
                SELECT configuracion, variable, valor, json
                FROM usuario_config
                WHERE usuario = :id
            ', [':id' => $this->id]);
            if (!$config) {
                $this->config = false;
                return null;
            }
            foreach ($config as $configuracion => $datos) {
                if (!isset($datos[0])) {
                    $datos = [$datos];
                }
                $this->config[$configuracion] = [];
                foreach ($datos as $dato) {
                    $class = get_called_class();
                    if (in_array($configuracion . '_' . $dato['variable'], $class::$config_encrypt)) {
                        $dato['valor'] = \sowerphp\core\Utility_Data::decrypt(
                            $dato['valor'],
                            \sowerphp\core\Configure::read('app.pkey')
                        );
                    }
                    $this->config[$configuracion][$dato['variable']] =
                        $dato['json'] ? json_decode($dato['valor']) : $dato['valor']
                    ;
                }
            }
        }
        return $this->config;
    }

    /**
     * Método mágico para obtener configuraciones del usuario
     */
    public function __get($name)
    {
        if (strpos($name, 'config_') === 0 && class_exists('\sowerphp\app\Sistema\Usuarios\Model_UsuarioConfig')) {
            $this->getConfig();
            $key = str_replace('config_', '', $name);
            $c = substr($key, 0, strpos($key, '_'));
            $v = substr($key, strpos($key, '_') + 1);
            if (!isset($this->config[$c][$v])) {
                $class = get_called_class();
                return isset($class::$config_default[$c . '_' . $v]) ? $class::$config_default[$c . '_' . $v] : null;
            }
            $this->$name = $this->config[$c][$v];
            return $this->$name;
        } else {
            throw new \Exception(
                'Atributo '.$name.' del usuario no existe (no se puede obtener).'
            );
        }
    }

    /**
     * Método mágico para asignar una configuración del usuario
     */
    public function __set($name, $value)
    {
        if (strpos($name, 'config_') === 0 && class_exists('\sowerphp\app\Sistema\Usuarios\Model_UsuarioConfig')) {
            $key = str_replace('config_', '', $name);
            $c = substr($key, 0, strpos($key, '_'));
            $v = substr($key, strpos($key, '_') + 1);
            $value = ($value === false || $value === 0)
                ? '0'
                : (
                    (!is_array($value) && !is_object($value))
                        ? (string)$value
                        : (
                            (is_array($value) && empty($value))
                                ? null
                                : $value
                        )
                )
            ;
            $this->config[$c][$v] = (!is_string($value) || isset($value[0])) ? $value : null;
            $this->$name = $this->config[$c][$v];
        } else {
            throw new \Exception(
                'Atributo '.$name.' del usuario no existe (no se puede asignar).'
            );
        }
    }

    /**
     * Método para setear los atributos del usuario
     * @param array Arreglo con los datos que se deben asignar
     */
    public function set($array)
    {
        parent::set($array);
        foreach($array as $name => $value) {
            if (strpos($name, 'config_') === 0) {
                $this->__set($name, $value);
            }
        }
    }

    /**
     * Método que guarda el usuario y su configuración personalizada si existe
     */
    public function save()
    {
        if ($this->db === null) {
            $this->db = \sowerphp\core\Model_Datasource_Database::get($this->_database);
        }
        // guardar usuario
        if (!parent::save()) {
            return false;
        }
        // guardar configuración
        if ($this->config && class_exists('\sowerphp\app\Sistema\Usuarios\Model_UsuarioConfig')) {
            $app_pkey = \sowerphp\core\Configure::read('app.pkey');
            foreach ($this->config as $configuracion => $datos) {
                foreach ($datos as $variable => $valor) {
                    $Config = new Model_UsuarioConfig($this->id, $configuracion, $variable);
                    if (!is_array($valor) && !is_object($valor)) {
                        $Config->json = 0;
                    } else {
                        $valor = json_encode($valor);
                        $Config->json = 1;
                    }
                    $class = get_called_class();
                    if (in_array($configuracion . '_' . $variable, $class::$config_encrypt) && $valor !== null) {
                        if (!$app_pkey) {
                            throw new \Exception(
                                'No está definida la configuración app.pkey para encriptar configuración del usuario.'
                            );
                        }
                        $valor = \sowerphp\core\Utility_Data::encrypt($valor, $app_pkey);
                    }
                    $Config->valor = $valor;
                    if ($valor !== null) {
                        $Config->save();
                    } else {
                        $Config->delete();
                    }
                }
            }
        }
        // todo ok
        return true;
    }

    /**
     * Método que hace un UPDATE del usuario en la BD
     * Actualiza todos los campos, excepto: contrasenia, contrasenia_internos y
     * token, lo anterior ya que hay métodos especiales para actualizar dichas
     * columnas.
     */
    protected function update($columns = null)
    {
        if ($columns) {
            return parent::update($columns);
        } else {
            return parent::update([
                'nombre' => $this->nombre,
                'usuario' => $this->usuario,
                'usuario_ldap' => $this->usuario_ldap,
                'email' => $this->email,
                'hash' => $this->hash,
                'activo' => $this->activo,
                'ultimo_ingreso_fecha_hora' => $this->ultimo_ingreso_fecha_hora,
                'ultimo_ingreso_desde' => $this->ultimo_ingreso_desde,
                'ultimo_ingreso_hash' => $this->ultimo_ingreso_hash,
            ]);
        }
    }

    /**
     * Método que revisa si el nombre de usuario ya existe en la base de datos
     * @return bool =true si el nombre de usuario ya existe
     */
    public function checkIfUserAlreadyExists()
    {
        if (empty($this->id)) {
            return (bool)$this->getDB()->getValue('
                SELECT COUNT(*) FROM usuario WHERE LOWER(usuario) = :usuario
            ', [':usuario' => strtolower($this->usuario)]);
        } else {
            return (bool)$this->getDB()->getValue('
                SELECT COUNT(*)
                FROM usuario
                WHERE id != :id AND LOWER(usuario) = :usuario
            ', [':id' => $this->id, ':usuario' => strtolower($this->usuario)]);
        }
    }

    /**
     * Método que revisa si el email ya existe en la base de datos
     * @return bool =true si el correo ya existe
     */
    public function checkIfEmailAlreadyExists()
    {
        if (empty($this->id)) {
            return (bool)$this->getDB()->getValue('
                SELECT COUNT(*) FROM usuario WHERE email = :email
            ', [':email' => $this->email]);
        } else {
            return (bool)$this->getDB()->getValue('
                SELECT COUNT(*)
                FROM usuario
                WHERE id != :id AND email = :email
            ', [':id' => $this->id, ':email' => $this->email]);
        }
    }

    /**
     * Método que revisa si el hash del usuario ya existe en la base de datos
     * @return bool =true si el hash ya existe
     */
    public function checkIfHashAlreadyExists()
    {
        if (empty($this->id)) {
            return (bool)$this->getDB()->getValue('
                SELECT COUNT(*) FROM usuario WHERE hash = :hash
            ', [':hash' => $this->hash]);
        } else {
            return (bool)$this->getDB()->getValue('
                SELECT COUNT(*)
                FROM usuario
                WHERE id != :id AND hash = :hash
            ', [':id' => $this->id, ':hash' => $this->hash]);
        }
    }

    /**
     * Método que cambia la contraseña del usuario
     * @param new Contraseña nueva en texto plano
     * @param old Contraseña actual en texto plano
     * @return bool =true si la contraseña pudo ser cambiada
     */
    public function savePassword($new, $old = null)
    {
        if ($this->getLdapPerson()) {
            if (!$this->getLdapPerson()->savePassword($new, $old)) {
                return false;
            }
        }
        return $this->savePasswordLocal($new);
    }

    /**
     * Método que cambia la contraseña del usuario en la base de datos
     * @param new Contraseña nueva en texto plano
     * @return bool =true si la contraseña pudo ser cambiada
     */
    private function savePasswordLocal($new)
    {
        $this->contrasenia = $this->hashPassword($new);
        $this->getDB()->query('
            UPDATE usuario SET contrasenia = :contrasenia WHERE id = :id
        ', [':contrasenia' => $this->contrasenia, ':id' => $this->id]);
        return true;
    }

    /**
     * Método que calcula el hash para la contraseña según el algoritmo más
     * fuerte disponible en PHP y usando un salt automático.
     * @param password Contraseña que se desea encriptar
     * @return string Contraseña encriptada (su hash)
     */
    public function hashPassword($password)
    {
        return password_hash($password, \PASSWORD_DEFAULT, ['cost' => 9]);
    }

    /**
     * Método que revisa si la contraseña entregada es igual a la contraseña del
     * usuario almacenada en la base de datos
     * @param password Contrasela que se desea verificar
     * @return bool =true si la contraseña coincide con la de la base de datos
     */
    public function checkPassword($password)
    {
        if ($this->getLdapPerson()) {
            if ($this->getLdapPerson()->checkPassword($password)) {
                $this->savePasswordLocal($password);
                return true;
            }
            return false;
        } else {
            if ($this->contrasenia[0] != '$') {
                $status = $this->contrasenia == hash('sha256', $password);
                if ($status) {
                    $this->savePasswordLocal($password);
                }
                return $status;
            }
            return password_verify($password, $this->contrasenia);
        }
    }

    /**
     * Método que revisa si el hash indicado es igual al hash que tiene el
     * usuario para su último ingreso (o sea si la sesión es aun válida)
     * @return bool =true si el hash aun es válido
     */
    public function checkLastLoginHash($hash)
    {
        return $this->ultimo_ingreso_hash == $hash;
    }

    /**
     * Método que indica si el usuario está o no activo
     * @return bool =true si el usuario está activo
     */
    public function isActive()
    {
        if ($this->getEmailAccount()) {
            return $this->getEmailAccount()->isActive();
        }
        return (bool)$this->activo;
    }

    /**
     * Método que entrega un arreglo con los datos del último acceso del usuario
     * @return array Arreglo con índices: fecha_hora, desde, hash
     */
    public function lastLogin()
    {
        return [
            'fecha_hora' => $this->ultimo_ingreso_fecha_hora,
            'desde' => $this->ultimo_ingreso_desde,
            'hash' => $this->ultimo_ingreso_hash,
        ];
    }

    /**
     * Método que actualiza el último ingreso del usuario
     */
    public function updateLastLogin($ip, $multipleLogins = false)
    {
        if ($this->config_login_multiple !== null) {
            $multipleLogins = $this->config_login_multiple;
        }
        $timestamp = date('Y-m-d H:i:s');
        $hash = md5($multipleLogins ? $this->contrasenia : ($ip . $timestamp . $this->contrasenia));
        $this->update ([
            'ultimo_ingreso_fecha_hora' => $timestamp,
            'ultimo_ingreso_desde' => $ip,
            'ultimo_ingreso_hash' => $hash
        ]);
        return $hash;
    }

    /**
     * Método que entrega el listado de grupos a los que pertenece el usuario
     * @return array Arreglo asociativo con el GID como clave y el nombre del grupo como valor
     */
    public function groups($forceGet = false)
    {
        if ($this->groups === null || $forceGet) {
            $this->groups = $this->getDB()->getAssociativeArray('
                SELECT g.id, g.grupo
                FROM grupo AS g, usuario_grupo AS ug
                WHERE ug.usuario = :usuario AND g.id = ug.grupo
                ORDER BY g.grupo
            ', [':usuario' => $this->id]);
        }
        return $this->groups;
    }

    /**
     * Método que fuerza los grupos de un usuario
     */
    public function setGroups(array $groups = [])
    {
        $this->groups = $groups ? $groups : null;
    }

    /**
     * Método que permite determinar si un usuario pertenece a cierto grupo.
     * Además se revisará si pertenece al grupo sysadmin, en cuyo caso también
     * entregará la cantidad de grupos
     * @param grupos Arreglo con los grupos que se desean revisar
     * @return int La cantidad de grupos a los que el usuario pertenece
     */
    public function inGroup($grupos = [])
    {
        $grupos_usuario = $this->groups();
        if (!is_array($grupos)) {
            $grupos = [$grupos];
        }
        $n_grupos = count($grupos);
        if (in_array('sysadmin', $grupos_usuario)) {
            return $n_grupos;
        }
        $n_encontrados = 0;
        foreach ($grupos as $g) {
            if (in_array($g, $grupos_usuario)) {
                $n_encontrados++;
            }
        }
        return $n_encontrados;
    }

    /**
     * Método que permite determinar si un usuario pertenece a todos los grupos
     * que están en la consulta
     * @param grupos Arreglo con los grupos que se desean revisar
     * @return bool =true si pertenece a todos los grupos que se solicitaron
     */
    public function inAllGroups($grupos = [])
    {
        $n_grupos = count($grupos);
        $n_encontrados = $this->inGroup($grupos);
        return $n_grupos == $n_encontrados;
    }

    /**
     * Método que asigna los grupos al usuario, eliminando otros que no están
     * en el listado
     * @param grupos Arreglo con los GIDs de los grupos que se deben asignar/mantener
     */
    public function saveGroups($grupos)
    {
        if (!$grupos) {
            return false;
        }
        sort($grupos);
        if (!is_numeric($grupos[0])) {
            $grupos = (new Model_Grupos())->getIDs($grupos);
        }
        $grupos = array_map('intval', $grupos);
        $this->getDB()->beginTransaction();
        if ($grupos) {
            $this->getDB()->query ('
                DELETE FROM usuario_grupo
                WHERE
                    usuario = :usuario
                    AND grupo NOT IN ('.implode(', ', $grupos).')
            ', [':usuario' => $this->id]);
            foreach ($grupos as &$grupo) {
                (new Model_UsuarioGrupo($this->id, $grupo))->save();
            }
        } else {
            $this->getDB()->query ('
                DELETE FROM usuario_grupo
                WHERE usuario = :usuario
            ', [':usuario' => $this->id]);
        }
        $this->getDB()->commit();
    }

    /**
     * Método que entrega los grupos a los que realmente pertenece un usuario dado determinados grupos
     */
    public function getGroups(array $groups = [])
    {
        $where = ['ug.usuario = :usuario', 'g.activo = :activo'];
        $vars = [':usuario' => $this->id, ':activo' => true];
        if ($groups) {
            $grupos = [];
            $i = 1;
            foreach ($groups as $g) {
                $grupos[] = ':grupo' . $i;
                $vars[':grupo' . $i] = $g;
                $i++;
            }
            $where[] = 'g.grupo IN (' . implode(', ', $grupos) . ')';
        }
        return $this->getDB()->getAssociativeArray('
            SELECT g.id, g.grupo
            FROM usuario_grupo AS ug JOIN grupo AS g ON ug.grupo = g.id
            WHERE '.implode(' AND ', $where).'
        ', $vars);
    }

    /**
     * Método que entrega el listado de recursos sobre los que el usuario tiene
     * permisos para acceder.
     * @return array Listado de recursos a los que el usuario tiene acceso
     */
    public function auths($forceGet = false)
    {
        if ($this->auths === null || $forceGet) {
            $this->auths = $this->getAuths();
        }
        return $this->auths;
    }

    /**
     * Método que entrega los recursos a los que tiene acceso el usuario dado determinados grupos
     */
    public function getAuths(array $groups = [])
    {
        $where = ['ug.usuario = :usuario', 'g.activo = :activo'];
        $vars = [':usuario' => $this->id, ':activo' => true];
        if ($groups) {
            $grupos = [];
            $i = 1;
            foreach ($groups as $g) {
                $grupos[] = ':grupo' . $i;
                $vars[':grupo' . $i] = $g;
                $i++;
            }
            $where[] = 'g.grupo IN (' . implode(', ', $grupos) . ')';
        }
        if ($this->db === null) {
            $this->db = \sowerphp\core\Model_Datasource_Database::get($this->_database);
        }
        return $this->getDB()->getCol('
            SELECT a.recurso
            FROM auth AS a, usuario_grupo AS ug, grupo AS g
            WHERE a.grupo = ug.grupo AND ug.grupo = g.id AND ' . implode(' AND ', $where) . '
        ', $vars);
    }

    /**
     * Método que asigna manualmente un listado de recursos a los que el usuario tiene acceso
     */
    public function setAuths(array $auths = [])
    {
        $this->auths = $auths ? $auths : null;
    }

    /**
     * Método que verifica si el usuario tiene permiso para acceder a cierto
     * recurso.
     * @return bool =true si tiene permiso
     */
    public function auth($recurso)
    {
        $recurso = is_string($recurso) ? $recurso : $recurso->request;
        $permisos = $this->auths();
        // buscar permiso de forma exacta
        if (in_array($recurso, $permisos)) {
            return true;
        }
        // buscar si el usuario tiene permiso para acceder a todo
        if (in_array('*', $permisos)) {
            return true;
        }
        // revisar por cada permiso
        foreach ($permisos as &$permiso) {
            // buscar si el permiso es del tipo recurso*
            if ($permiso[strlen($permiso)-1] == '*' && strpos($recurso, substr($permiso, 0, -1)) === 0) {
                return true;
            }
            // buscar por partes
            $partes = explode('/', $permiso);
            array_shift($partes);
            $aux = '';
            foreach ($partes as &$parte) {
                if ($parte == '*') {
                    if (strpos($recurso, $aux) !== false) {
                        return true;
                    }
                } else {
                    $aux .= '/' . $parte;
                    if ($recurso === $aux) {
                        return true;
                    }
                }
            }
        }
        // si no se encontró permiso => false
        return false;
    }

    /**
     * Método que asigna los intentos de contraseña
     */
    public function savePasswordRetry($intentos)
    {
        $this->contrasenia_intentos = $intentos;
        $this->getDB()->query(
            'UPDATE usuario SET contrasenia_intentos = :intentos WHERE id = :id'
        , [':id' => $this->id, ':intentos' => $intentos]);
    }

    /**
     * Método que entrega las autenticaciones secundarias que el usuario tiene habilitadas
     * @return array Arreglo con listado de instancias de la auths2 habilitadas
     */
    public function getAuth2()
    {
        $auths2_usuario = [];
        $auths2 = \sowerphp\app\Model_Datasource_Auth2::getAll();
        foreach($auths2 as $Auth2) {
            if ($this->{'config_auth2_' . $Auth2->getName()}) {
                $auths2_usuario[] = $Auth2;
            }
        }
        return $auths2_usuario;
    }

    /**
     * Método que crea el token para el usuario
     * @param codigo Código que se usará para crear el token
     * @return bool =true si el token pudo ser creado
     */
    public function createAuth2(array $data = [])
    {
        $Auth2 = \sowerphp\app\Model_Datasource_Auth2::get($data['auth2']);
        $user_data = $Auth2->create($data);
        $this->config['auth2'][$data['auth2']] = $user_data;
        return $this->save();
    }

    /**
     * Método que destruye el token en la autorización secundaria
     * @return bool =true si el token pudo ser destruído
     */
    public function destroyAuth2(array $data = [])
    {
        $Auth2 = \sowerphp\app\Model_Datasource_Auth2::get($data['auth2']);
        $Auth2->destroy($data + (array)($this->config['auth2'][$data['auth2']]));
        $this->config['auth2'][$data['auth2']] = null;
        return $this->save();
    }

    /**
     * Método que valida el estado de todas las autorizaciones secundarias
     * que el usuario pudiese tener habilitadas
     * @return bool =true si todo está ok o Exception con el error si falla
     */
    public function checkAuth2($token)
    {
        $auths2 = $this->getAuth2();
        foreach($auths2 as $Auth2) {
            $datos = ['token' => $token] + (array)($this->config['auth2'][$Auth2->getName()]);
            $Auth2->check($datos);
        }
        return true;
    }

    /**
     * Método que recupera la persona LDAP asociada al usuario
     * @return Model_Datasource_Ldap_Person o Model_Datasource_Zimbra_Account
     */
    public function getLdapPerson()
    {
        if ($this->getEmailAccount() !== null) {
            return $this->LdapPerson;
        }
        if ($this->LdapPerson === null && \sowerphp\core\Configure::read('ldap.default')) {
            try {
                $this->LdapPerson = \sowerphp\app\Model_Datasource_Ldap::get()->getPerson(
                    $this->{\sowerphp\app\Model_Datasource_Ldap::get()->config['person_uid']}
                );
                if (!$this->LdapPerson->exists()) {
                    $this->LdapPerson = false;
                }
            } catch (\Exception $e) {
                $this->LdapPerson = false;
            }
        }
        return $this->LdapPerson;
    }

    /**
     * Método que recupera la cuenta Zimbra asociada al usuario
     * @return Model_Datasource_Zimbra_Account
     */
    public function getEmailAccount()
    {
        if ($this->LdapPerson && get_class($this->LdapPerson) != 'sowerphp\app\Model_Datasource_Zimbra_Account') {
            return false;
        }
        if ($this->LdapPerson === null && \sowerphp\core\Configure::read('zimbra.default')) {
            try {
                $this->LdapPerson = \sowerphp\app\Model_Datasource_Zimbra::get()->getAccount(
                    $this->{\sowerphp\app\Model_Datasource_Ldap::get()->config['person_uid']}
                );
                if (!$this->LdapPerson->exists()) {
                    $this->LdapPerson = false;
                }
            } catch (\Exception $e) {
                $this->LdapPerson = false;
            }
        }
        return $this->LdapPerson;
    }

    /**
     * Método que entrega el correo del usuario seleccionando el que tiene en
     * su cuenta o bien el de la cuenta de correo (Zimbra) si existe una
     * asociada.
     * @return string Cuenta de correo oficial del usuario
     */
    public function getEmail()
    {
        if ($this->getEmailAccount()) {
            return $this->getEmailAccount()->getEmail();
        }
        return $this->email;
    }

    /**
     * Método que entrega la URL del avatar del usuario
     * @param size Tamaño de la imagen en pixeles (un sólo lado ya que es cuadrada)
     */
    public function getAvatar($size = 100)
    {
        return 'https://gravatar.com/avatar/'.md5(strtolower(trim($this->email))).'?size='.(int)$size;
    }

    /**
     * Método que envía un correo al usuario
     */
    public function email($subject, $msg, $replyTo = null)
    {
        $email = new \sowerphp\core\Network_Email();
        if ($replyTo) {
            $email->replyTo($replyTo);
        }
        $email->to($this->email);
        $email->subject('['.\sowerphp\core\Configure::read('page.body.title').'] '.$subject);
        $msg = $msg."\n\n".'-- '."\n".\sowerphp\core\Configure::read('page.body.title');
        return $email->send($msg);
    }

}
