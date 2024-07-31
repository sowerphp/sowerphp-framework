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

namespace sowerphp\app\Sistema\Usuarios;

use \stdClass;
use \sowerphp\autoload\Model;
use \Illuminate\Contracts\Auth\Authenticatable;

/**
 * Modelo singular de la tabla "usuario" de la base de datos.
 *
 * Permite interactuar con un registro de la tabla.
 */
class Model_Usuario extends Model implements Authenticatable
{

    /**
     * Metadatos del modelo.
     *
     * @var array
     */
    protected $meta = [
        'model' => [
            'db_table_comment' => 'Usuarios de la aplicación.',
            'ordering' => ['-ultimo_ingreso_fecha_hora'],
        ],
        'fields' => [
            'id' => [
                'type' => self::TYPE_INCREMENTS,
                'primary_key' => true,
                'verbose_name' => 'ID',
                'help_text' => 'Identificador (serial).',
            ],
            'nombre' => [
                'type' => self::TYPE_STRING,
                'max_length' => 50,
                'verbose_name' => 'Nombre',
                'help_text' => 'Nombre real del usuario.',
            ],
            'usuario' => [
                'type' => self::TYPE_STRING,
                'unique' => true,
                'max_length' => 30,
                'verbose_name' => 'Usuario',
                'help_text' => 'Nombre de usuario.',
            ],
            'email' => [
                'type' => self::TYPE_STRING,
                'unique' => true,
                'max_length' => 50,
                'verbose_name' => 'Email',
                'help_text' => 'Correo electrónico del usuario.',
                'validation' => ['email'],
                'sanitize' => ['strip_tags', 'spaces', 'trim', 'email'],
            ],
            'contrasenia' => [
                'type' => self::TYPE_STRING,
                'max_length' => 255,
                'verbose_name' => 'Contraseña',
                'help_text' => 'Contraseña del usuario.',
                'hidden' => true,
                'show_in_list' => false,
                'searchable' => false,
            ],
            'contrasenia_intentos' => [
                'type' => self::TYPE_SMALL_INTEGER,
                'default' => 3,
                'verbose_name' => 'Intentos de contraseña',
                'help_text' => 'Intentos de inicio de sesión con contraseña incorrecta antes de bloquear cuenta.',
                'hidden' => true,
                'show_in_list' => false,
                'searchable' => false,
            ],
            'hash' => [
                'type' => self::TYPE_CHAR,
                'blank' => true,
                'unique' => true,
                'length' => 32,
                'verbose_name' => 'Hash',
                'help_text' => 'Hash único del usuario (32 caracteres).',
                'hidden' => true,
                'show_in_list' => false,
                'searchable' => false,
            ],
            'token' => [
                'type' => self::TYPE_CHAR,
                'length' => 64,
                'verbose_name' => 'Token',
                'help_text' => 'Token para servicio secundario de autorización.',
                'hidden' => true,
                'show_in_list' => false,
                'searchable' => false,
            ],
            'activo' => [
                'type' => self::TYPE_BOOLEAN,
                'blank' => true,
                'default' => true,
                'verbose_name' => 'Activo',
                'help_text' => 'Indica si el usuario está o no activo en la aplicación.',
            ],
            'ultimo_ingreso_fecha_hora' => [
                'type' => self::TYPE_DATE_TIME,
                'auto' => true,
                'verbose_name' => 'Último ingreso',
                'help_text' => 'Fecha y hora del último ingreso del usuario.',
            ],
            'ultimo_ingreso_desde' => [
                'type' => self::TYPE_IP_ADDRESS,
                'auto' => true,
                'verbose_name' => 'Última IP',
                'help_text' => 'Dirección IP del último ingreso del usuario',
                'hidden' => true,
            ],
            'ultimo_ingreso_hash' => [
                'type' => self::TYPE_CHAR,
                'auto' => true,
                'length' => 32,
                'verbose_name' => 'Último hash',
                'help_text' => 'Hash del último ingreso del usuario.',
                'hidden' => true,
            ],
        ],
        'configurations' => [
            'fields' => [
            ],
        ],
    ];

    // atributos para caché
    protected $groups = null; ///< Grupos a los que pertenece el usuario
    protected $auths = null; ///< Permisos que tiene el usuario

    /**
     * Obtiene el usuario solicitado.
     *
     * @param array $id Clave primaria del modelo.
     * @return stdClass|null
     */
    public function retrieve(array $id): ?stdClass
    {
        $realId = $this->getPluralInstance()->getIdFromCredentials($id);
        if ($realId === null) {
            return null;
        }
        return parent::retrieve(['id' => $realId]);
    }

    /**
     * Método que hace un UPDATE del usuario en la base de datos,
     * Actualiza todos los campos, excepto: contrasenia, contrasenia_internos y
     * token, lo anterior ya que hay métodos especiales para actualizar dichas
     * columnas.
     */
    protected function update(array $columns = []): bool
    {
        if ($columns) {
            return parent::update($columns);
        } else {
            return parent::update([
                'nombre' => $this->nombre,
                'usuario' => $this->usuario,
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
     * Método que revisa si el nombre de usuario ya existe en la base de datos.
     * @return bool =true si el nombre de usuario ya existe.
     */
    public function checkIfUserAlreadyExists(): bool
    {
        if (empty($this->id)) {
            return (bool)$this->getDatabaseConnection()->getValue('
                SELECT COUNT(*)
                FROM usuario
                WHERE LOWER(usuario) = :usuario
            ', [
                ':usuario' => strtolower($this->usuario),
            ]);
        } else {
            return (bool)$this->getDatabaseConnection()->getValue('
                SELECT COUNT(*)
                FROM usuario
                WHERE id != :id AND LOWER(usuario) = :usuario
            ', [
                ':id' => $this->id,
                ':usuario' => strtolower($this->usuario),
            ]);
        }
    }

    /**
     * Método que revisa si el email ya existe en la base de datos.
     *
     * @return bool =true si el correo ya existe.
     */
    public function checkIfEmailAlreadyExists(): bool
    {
        if (empty($this->id)) {
            return (bool)$this->getDatabaseConnection()->getValue('
                SELECT COUNT(*)
                FROM usuario
                WHERE email = :email
            ', [
                ':email' => $this->email,
            ]);
        } else {
            return (bool)$this->getDatabaseConnection()->getValue('
                SELECT COUNT(*)
                FROM usuario
                WHERE id != :id AND email = :email
            ', [
                ':id' => $this->id,
                ':email' => $this->email,
            ]);
        }
    }

    /**
     * Método que revisa si el hash del usuario ya existe en la base de datos.
     * @return bool =true si el hash ya existe.
     */
    public function checkIfHashAlreadyExists(): bool
    {
        if (empty($this->id)) {
            return (bool)$this->getDatabaseConnection()->getValue('
                SELECT COUNT(*)
                FROM usuario
                WHERE hash = :hash
            ', [
                ':hash' => $this->hash,
            ]);
        } else {
            return (bool)$this->getDatabaseConnection()->getValue('
                SELECT COUNT(*)
                FROM usuario
                WHERE id != :id AND hash = :hash
            ', [
                ':id' => $this->id,
                ':hash' => $this->hash,
            ]);
        }
    }

    /**
     * Método que cambia la contraseña del usuario.
     *
     * @param string $new Contraseña nueva en texto plano.
     * @param string|null $old Contraseña actual en texto plano.
     * @return bool =true si la contraseña pudo ser cambiada.
     */
    public function savePassword(string $new, string $old = null): bool
    {
        return $this->savePasswordLocal($new);
    }

    /**
     * Método que cambia la contraseña del usuario en la base de datos.
     *
     * @param string $new Contraseña nueva en texto plano.
     * @return bool =true si la contraseña pudo ser cambiada.
     */
    private function savePasswordLocal(string $new): bool
    {
        $this->contrasenia = $this->hashPassword($new);
        $this->getDatabaseConnection()->executeRawQuery('
            UPDATE usuario
            SET contrasenia = :contrasenia
            WHERE id = :id
        ', [
            ':id' => $this->id,
            ':contrasenia' => $this->contrasenia,
        ]);
        return true;
    }

    /**
     * Método que calcula el hash para la contraseña según el algoritmo más
     * fuerte disponible en PHP y usando un salt automático.
     *
     * @param string $password Contraseña que se desea encriptar.
     * @return string Contraseña encriptada (su hash).
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, \PASSWORD_DEFAULT, ['cost' => 9]);
    }

    /**
     * Método que revisa si la contraseña entregada es igual a la contraseña
     * del usuario almacenada en la base de datos.
     *
     * @param string $password Contrasela que se desea verificar.
     * @return bool =true si la contraseña coincide con la de la base de datos.
     */
    public function checkPassword(string $password): bool
    {
        if ($this->contrasenia[0] != '$') {
            $status = $this->contrasenia == hash('sha256', $password);
            if ($status) {
                $this->savePasswordLocal($password);
            }
            return $status;
        }
        return password_verify($password, $this->contrasenia);
    }

    /**
     * Método que indica si el usuario está o no activo.
     *
     * @return bool =true si el usuario está activo.
     */
    public function isActive(): bool
    {
        return (bool)$this->activo;
    }

    /**
     * Método que entrega un arreglo con los datos del último acceso del
     * usuario.
     *
     * @return array Arreglo con índices: fecha_hora, desde y hash.
     */
    public function lastLogin(): array
    {
        return [
            'fecha_hora' => $this->ultimo_ingreso_fecha_hora,
            'desde' => $this->ultimo_ingreso_desde,
            'hash' => $this->ultimo_ingreso_hash,
        ];
    }

    /**
     * Método que actualiza el último ingreso del usuario.
     */
    public function createRememberToken(string $ip): string
    {
        if ($this->config_login_multiple !== null) {
            $multipleLogins = $this->config_login_multiple;
        } else {
            $multipleLogins = config('auth.multiple_logins', false);
        }
        $timestamp = date('Y-m-d H:i:s');
        $sessionHash = md5(hash(
            'sha256',
            $multipleLogins
                ? $this->contrasenia
                : ($ip . $timestamp . $this->contrasenia)
        ));
        $this->forceFill([
            'ultimo_ingreso_fecha_hora' => $timestamp,
            'ultimo_ingreso_desde' => $ip,
            'ultimo_ingreso_hash' => $sessionHash
        ]);
        $this->save();
        return $sessionHash;
    }

    /**
     * Método que entrega el listado de grupos a los que pertenece el usuario.
     *
     * @return array Arreglo asociativo con el GID como clave y el nombre del
     * grupo como valor.
     */
    public function groups(bool $forceGet = false): array
    {
        if ($this->groups === null || $forceGet) {
            $this->groups = $this->getDatabaseConnection()->getTableWithAssociativeIndex('
                SELECT g.id, g.grupo
                FROM grupo AS g, usuario_grupo AS ug
                WHERE ug.usuario = :usuario AND g.id = ug.grupo
                ORDER BY g.grupo
            ', [
                ':usuario' => $this->id,
            ]);
        }
        return $this->groups;
    }

    /**
     * Método que fuerza los grupos de un usuario.
     */
    public function setGroups(array $groups = []): void
    {
        $this->groups = $groups ? $groups : null;
    }

    /**
     * Método que permite determinar si un usuario pertenece a cierto grupo.
     *
     * Además se revisará si pertenece al grupo sysadmin, en cuyo caso también
     * entregará la cantidad de grupos.
     *
     * @param array|string $grupos Arreglo con los grupos que se desean revisar.
     * @return int La cantidad de grupos a los que el usuario pertenece.
     */
    public function inGroup($grupos = []): int
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
     * que están en la consulta.
     *
     * @param array $grupos Arreglo con los grupos que se desean revisar.
     * @return bool =true si pertenece a todos los grupos que se solicitaron.
     */
    public function inAllGroups(array $grupos = []): bool
    {
        $n_grupos = count($grupos);
        $n_encontrados = $this->inGroup($grupos);
        return $n_grupos == $n_encontrados;
    }

    /**
     * Método que asigna los grupos al usuario, eliminando otros que no están
     * en el listado.
     *
     * @param array $grupos Arreglo con los GIDs de los grupos que se deben
     * asignar/mantener.
     */
    public function saveGroups($grupos): bool
    {
        if (!$grupos) {
            return false;
        }
        sort($grupos);
        if (!is_numeric($grupos[0])) {
            $grupos = (new Model_Grupos())->getIDs($grupos);
        }
        $grupos = array_map('intval', $grupos);
        $this->getDatabaseConnection()->beginTransaction();
        if ($grupos) {
            $this->getDatabaseConnection()->query ('
                DELETE FROM usuario_grupo
                WHERE
                    usuario = :usuario
                    AND grupo NOT IN ('.implode(', ', $grupos).')
            ', [
                ':usuario' => $this->id,
            ]);
            foreach ($grupos as &$grupo) {
                (new Model_UsuarioGrupo($this->id, $grupo))->save();
            }
        } else {
            $this->getDatabaseConnection()->query ('
                DELETE FROM usuario_grupo
                WHERE usuario = :usuario
            ', [
                ':usuario' => $this->id,
            ]);
        }
        return $this->getDatabaseConnection()->commit();
    }

    /**
     * Método que entrega los grupos a los que realmente pertenece un usuario
     * dado determinados grupos.
     */
    public function getGroups(array $groups = []): array
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
        return $this->getDatabaseConnection()->getTableWithAssociativeIndex('
            SELECT g.id, g.grupo
            FROM usuario_grupo AS ug JOIN grupo AS g ON ug.grupo = g.id
            WHERE '.implode(' AND ', $where).'
        ', $vars);
    }

    /**
     * Método que entrega el listado de registros sobre los que el usuario tiene
     * permisos para acceder.
     *
     * @return array Listado de registros a los que el usuario tiene acceso.
     */
    public function auths($forceGet = false): array
    {
        if ($this->auths === null || $forceGet) {
            $this->auths = $this->getAuths();
        }
        return $this->auths;
    }

    /**
     * Método que entrega los registros a los que tiene acceso el usuario dado
     * determinados grupos.
     */
    public function getAuths(array $groups = []): array
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
        return $this->getDatabaseConnection()->getCol('
            SELECT a.recurso
            FROM auth AS a, usuario_grupo AS ug, grupo AS g
            WHERE
                a.grupo = ug.grupo
                AND ug.grupo = g.id
                AND ' . implode(' AND ', $where) . '
        ', $vars);
    }

    /**
     * Método que asigna manualmente un listado de registros a los que el
     * usuario tiene acceso.
     */
    public function setAuths(array $auths = []): void
    {
        $this->auths = $auths ? $auths : null;
    }

    /**
     * Método que verifica si el usuario tiene permiso para acceder a cierto
     * recurso.
     *
     * @return bool =true si tiene permiso.
     */
    public function auth($recurso): bool
    {
        if (!is_string($recurso)) {
            $recurso = $recurso->getRequestUriDecoded();
        }
        $permisos = $this->auths();
        // Buscar permiso de forma exacta.
        if (in_array($recurso, $permisos)) {
            return true;
        }
        // Buscar si el usuario tiene permiso para acceder a todo.
        if (in_array('*', $permisos)) {
            return true;
        }
        // Revisar por cada permiso.
        foreach ($permisos as &$permiso) {
            // Buscar si el permiso es del tipo recurso*.
            if (
                $permiso[strlen($permiso)-1] == '*'
                && strpos($recurso, substr($permiso, 0, -1)) === 0
            ) {
                return true;
            }
            // Buscar por partes.
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
        // Si no se encontró permiso => false.
        return false;
    }

    /**
     * Método que asigna los intentos de contraseña.
     */
    public function savePasswordRetry(int $intentos): void
    {
        $this->contrasenia_intentos = $intentos;
        $this->getDatabaseConnection()->executeRawQuery('
            UPDATE usuario
            SET contrasenia_intentos = :intentos
            WHERE id = :id
        ', [
            ':id' => $this->id,
            ':intentos' => $intentos,
        ]);
    }

    /**
     * Método que entrega las autenticaciones secundarias que el usuario tiene
     * habilitadas.
     *
     * @return array Arreglo con listado de instancias de la auth2 habilitadas.
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
     * Método que crea el token para el usuario.
     *
     * @param array $data Datos que se usarán para crear el token.
     * @return bool =true si el token pudo ser creado.
     */
    public function createAuth2(array $data = []): bool
    {
        $Auth2 = \sowerphp\app\Model_Datasource_Auth2::get($data['auth2']);
        $user_data = $Auth2->create($data);
        $this->config['auth2'][$data['auth2']] = $user_data;
        return $this->save();
    }

    /**
     * Método que destruye el token en la autorización secundaria.
     *
     * @return bool =true si el token pudo ser destruído.
     */
    public function destroyAuth2(array $data = []): bool
    {
        $Auth2 = \sowerphp\app\Model_Datasource_Auth2::get($data['auth2']);
        $Auth2->destroy($data + (array)($this->config['auth2'][$data['auth2']]));
        $this->config['auth2'][$data['auth2']] = null;
        return $this->save();
    }

    /**
     * Método que valida el estado de todas las autorizaciones secundarias
     * que el usuario pudiese tener habilitadas.
     *
     * @return bool =true si todo está ok o Exception con el error si falla.
     */
    public function checkAuth2($token): bool
    {
        $auths2 = $this->getAuth2();
        foreach($auths2 as $Auth2) {
            $datos = ['token' => $token]
                + (array)($this->config['auth2'][$Auth2->getName()])
            ;
            $Auth2->check($datos);
        }
        return true;
    }

    /**
     * Método que entrega el correo del usuario.
     *
     * @return string Cuenta de correo oficial del usuario.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Método que entrega la URL del avatar del usuario.
     *
     * @param int $size Tamaño de la imagen en pixeles (un solo lado ya que es
     * cuadrada).
     */
    public function getAvatar(int $size = 100)
    {
        return 'https://gravatar.com/avatar/'
            . md5(strtolower(trim($this->email)))
            . '?size=' . (int)$size
        ;
    }

    /**
     * Método que envía un correo al usuario.
     */
    public function email($subject, $msg, $replyTo = null)
    {
        $email = new \sowerphp\core\Network_Email();
        if ($replyTo) {
            $email->replyTo($replyTo);
        }
        $email->to($this->email);
        $email->subject('[' . config('app.name') . '] ' . $subject);
        $msg = $msg . "\n\n" . '-- ' . "\n" . config('app.name');
        return $email->send($msg);
    }

    /**
     * Obtener la llave única identificable para el usuario.
     *
     * @return string
     */
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    /**
     * Obtener el valor clave único identificable del usuario.
     *
     * @return int
     */
    public function getAuthIdentifier(): int
    {
        return $this->id;
    }

    /**
     * Obtener el valor de la contraseña para el usuario.
     *
     * @return string
     */
    public function getAuthPassword(): string
    {
        return $this->contrasenia;
    }

    /**
     * Obtener el token de "recuérdame" del usuario.
     *
     * @return string
     */
    public function getRememberToken(): string
    {
        return (string)$this->ultimo_ingreso_hash;
    }

    /**
     * Establecer el token de "recuérdame" para el usuario.
     *
     * @param string $value
     * @return void
     */
    public function setRememberToken($value): void
    {
        $this->ultimo_ingreso_hash = $value;
        $this->save();
    }

    /**
     * Obtener el nombre de la columna que se usa para el token de "recuérdame".
     *
     * @return string
     */
    public function getRememberTokenName(): string
    {
        return 'ultimo_ingreso_hash';
    }

}
