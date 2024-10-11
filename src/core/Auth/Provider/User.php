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

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Proveedor de usuarios para autenticación.
 */
class Auth_Provider_User implements UserProvider
{

    protected $model;

    public function __construct(Service_Config $configService)
    {
        $this->model = $configService['auth.providers.users.model'];
    }

    /**
     * Recupera un usuario por su identificador único.
     *
     * @param mixed $identifier El identificador único del usuario,
     * generalmente el ID.
     * @return \Illuminate\Contracts\Auth\Authenticatable|null Retorna una
     * instancia de usuario o null si no se encuentra.
     */
    public function retrieveById($identifier): ?Authenticatable
    {
        $userClass = $this->model;
        $user = new $userClass($identifier);
        return $user->usuario ? $user : null;
    }

    /**
     * Recupera un usuario por un identificador y un token "recuérdame".
     *
     * @param mixed $identifier El identificador único del usuario.
     * @param string $token El token "recuérdame" asociado al usuario.
     * @return \Illuminate\Contracts\Auth\Authenticatable|null Retorna una
     * instancia de usuario o null si no se encuentra o el token no coincide.
     */
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        throw new \Exception(
            'Método Auth_Provider_User::retrieveByToken() no implementado.'
        );
    }

    /**
     * Actualiza el token "recuérdame" del usuario.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user El usuario cuyo
     * token se está actualizando.
     * @param string $token El nuevo token "recuérdame".
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token): void
    {
        throw new \Exception(
            'Método Auth_Provider_User::updateRememberToken() no implementado.'
        );
    }

    /**
     * Recupera un usuario utilizando un conjunto de credenciales.
     *
     * @param array $credentials Un arreglo de credenciales utilizadas para
     * encontrar al usuario.
     * @return \Illuminate\Contracts\Auth\Authenticatable|null Retorna una
     * instancia de usuario o null si no se encuentra un usuario con esas
     * credenciales.
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        // Obtener el ID a partir de las credenciales pasadas.
        $class = $this->model;
        $user = new $class();
        $id = $user->getPluralInstance()->getIdFromCredentials($credentials);
        if ($id === null) {
            return null;
        }
        // Instanciar el objeto del usuario.
        return $this->retrieveById($id);
    }

    /**
     * Valida las credenciales de un usuario.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user El usuario a
     * validar.
     * @param array $credentials Credenciales para validar contra el usuario,
     * como contraseña.
     * @return bool Retorna true si las credenciales son válidas, de lo
     * contrario false.
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $username = $credentials['username'] ?? '';
        $password = $credentials['password']
            ?? $credentials['hash']
            ?? $credentials['bearer']
            ?? $credentials['token']
            ?? ''
        ;
        // Validar credenciales mediante el hash.
        if ($username == 'X' && strlen($password) == 32) {
            return $user->hash == $password;
        }
        // Validar credenciales mediante contraseña.
        if ($password) {
            return $user->checkPassword($password);
        }
        // No se pudo validar.
        return false;
    }

}
