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

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Guard de autenticación para la API.
 */
class Auth_Guard_Api extends Auth_Guard
{

    /**
     * Inicializa la guard.
     *
     * @return void
     */
    public function boot(): void
    {
        $token = $this->authenticate();
    }

    /**
     * Autentica las credenciales y devuelve un token si son válidas.
     *
     * @param array $credentials Credenciales para autenticar al usuario.
     * @return string|null Retorna un token si la autenticación es exitosa.
     */
    public function authenticate(array $credentials = []): ?string
    {
        // Obtener usario autenticado en la API.
        $user = $this->getAuthUser($credentials);
        if ($user == null) {
            return null;
        }
        // Validar el usuario.
        // Generar y retornar token.
        $token = md5(hash('sha256', $user->getRememberToken()));
        return $token;
    }

    /**
     * Obtener el usuario autenticado en la API o null si no existe uno.
     *
     * Este método buscará usando las credenciales si existe un usuario
     * autenticado en la web, si existe lo usará. Si no existe intentará
     * autenticar al usuario a través de las credenciales provistas en la API.
     *
     * @param array $credentials
     * @return Authenticatable|null
     */
    protected function getAuthUser(array $credentials = []): ?Authenticatable
    {
        // Si hay un usuario con sesión iniciada en la web se usa ese.
        $user = $this->getAuthUserWeb();
        // Autenticar al usuario con los parámetros pasados a la API.
        if ($user === null) {
            $user = $this->getAuthUserApi($credentials);
        }
        // Si se encontró un usuario se asigna.
        if ($user !== null) {
            $this->setUser($user);
            return $this->user();
        }
        // Si no se encontró un usuario se retorna NULL.
        return null;
    }

    /**
     * Obtener el usuario autenticado en la web.
     *
     * @return Authenticatable|null
     */
    protected function getAuthUserWeb(): ?Authenticatable
    {
        $auth_web = auth('web');
        $auth_web->boot();
        return $auth_web->user();
    }

    /**
     * Obtener el usuario autenticado en la API.
     *
     * @param array $credentials
     * @return Authenticatable|null
     */
    protected function getAuthUserApi(array $credentials = []): ?Authenticatable
    {
        // Obtener instancia de usuario y validación básica de credenciales.
        if (empty($credentials)) {
            $credentials = $this->getUserCredentials();
        }
        $user = $this->provider->retrieveByCredentials($credentials);
        if (!$user) {
            return null;
        }
        if (!$this->provider->validateCredentials($user, $credentials)) {
            return null;
        }
        // Validaciones de contraseña y 2FA si se autentica con usuario y
        // contraseña. Si se autentica con el hash se ignoran estas validaciones.
        if ($user->hash != $credentials['password']) {
            // Error si el usuario tiene bloqueada su cuenta por intentos máximos.
            if (!$user->contrasenia_intentos) {
                $this->error(__(
                    'Cuenta de usuario %s fue bloqueada por exceder intentos de sesión, debe recuperar su contraseña.',
                    $user->usuario
                ));
                return null;
            }
            // Verificar token en sistema secundario de autorización.
            try {
                $user->checkAuth2($credentials['2fa_token'] ?? null);
            } catch (\Exception $e) {
                $this->error(__(
                    'Autenticación secundaria del usuario %s falló: %s',
                    $user->usuario,
                    $e->getMessage()
                ));
                return null;
            }
            // Actualizar intentos de contraseña.
            $user->savePasswordRetry(config('auth.max_login_attempts'));
        }
        // Entregar el usuario encontrado.
        return $user;
    }

    /**
     * Buscar las credenciales del usuario que se podrían haber pasado a la API.
     *
     * Se busca en cabecera Authorization, o bien en api_hash o api_key por GET.
     * Esto último no se recomienda usar, pues expone las credenciales en la
     * URL, pero existe por compatibilidad con sistemas integrados antiguos.
     *
     * @return array Arreglo con las credenciales encontradas.
     */
    protected function getUserCredentials(): array
    {
        $credentials = [];
        // Buscar en la cabecera Authorization.
        $header = $this->request->headers->get('Authorization');
        if ($header) {
            list($type, $value) = explode(' ', $header);
            if ($type == 'Basic') {
                $valueDecoded = base64_decode($value);
                $valueSplitted = explode(':', $valueDecoded);
                list($credentials['username'], $credentials['password']) = [
                    $valueSplitted[0],
                    $valueSplitted[1] ?? null,
                ];
            } else if ($type == 'Hash') {
                $credentials['hash'] = $value;
            } else if ($type == 'Bearer') {
                $credentials['bearer'] = $value;
            } else if ($type == 'Token') {
                $credentials['token'] = $value;
            }
        }
        // Buscar si vienen en la URL mediante GET.
        else {
            if (!empty($_GET['api_hash'])) {
                $credentials['hash'] = $_GET['api_hash'];
            }
            else if (!empty($_GET['api_key'])) {
                $valueDecoded = base64_decode($_GET['api_key']);
                $valueSplitted = explode(':', $valueDecoded);
                list($credentials['username'], $credentials['password']) = [
                    $valueSplitted[0],
                    $valueSplitted[1] ?? null,
                ];
            }
        }
        $username = $credentials['username'] ?? 'X';
        $password = $credentials['password']
            ?? $credentials['hash']
            ?? $credentials['bearer']
            ?? $credentials['token']
            ?? null
        ;
        // Si no se encontraron credenciales se retorna.
        if (empty($password)) {
            return [];
        }
        // Parche para soportar el hash pasado como usuario. Esto ya está
        // obsoleto y se recomienda usar la forma correcta al utilizar el HASH
        // del usuario con HTTP Basic Auth.
        //   - username: 'X'
        //   - password: HASH
        if ($password == 'X' && strlen($username) == 32) {
            list($username, $password) = [$password, $username];
        }
        // Se agrega las credenciales el token de autenticación si se pasó.
        $auth2_token = $_GET['2fa_token'] ?? $_GET['auth2_token'] ?? null;
        // Entregar credenciales encontradas.
        return [
            'username' => $username,
            'password' => $password,
            '2fa_token' => $auth2_token,
        ];
    }

}
