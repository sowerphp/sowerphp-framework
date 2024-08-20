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

use \Illuminate\Contracts\Auth\Authenticatable;

/**
 * Guard de autenticación para la web.
 */
class Auth_Guard_Web extends Auth_Guard
{

    protected $cacheService;
    protected $sessionService;
    protected $sessionKey = 'session.auth';
    protected $sessionHash;

    public function __construct(
        App $app,
        Service_Config $configService,
        Network_Request $request,
        Service_Cache $cacheService,
        Service_Http_Session $sessionService
    )
    {
        parent::__construct($app, $configService, $request);
        $this->cacheService = $cacheService;
        $this->sessionService = $sessionService;
    }

    /**
     * Inicializa la guard.
     *
     * @return void
     */
    public function boot(): void
    {
        // Si hay sesión se obtiene el objeto del usuario de la sesión.
        $sessionUser = $this->sessionService->get($this->sessionKey);
        if (
            $sessionUser
            && !empty($sessionUser['id'])
            && !empty($sessionUser['hash'])
        ) {
            $this->sessionHash = $sessionUser['hash'];
            // Usuario podría estar en caché, si lo está se saca de ahí.
            $cacheKey = $this->sessionKey . '.' . $sessionUser['id'];
            $this->user = $this->cacheService->get($cacheKey);
            // Si el usuario no estaba en la caché, se instancia.
            if (!$this->user) {
                try {
                    $this->user = $this->provider->retrieveById(
                        $sessionUser['id']
                    );
                } catch (\Exception $e) {
                    $this->user = null;
                }
            }
            // Si se logró obtener el usuario se asignan sus grupos, permisos
            // y se guarda (actualiza) en la caché para futuras consultas.
            if ($this->user) {
                $this->user->groups();
                $this->user->auths();
                $this->save();
            }
        }
    }

    /**
     * Método que guarda el usuario autenticado en la caché.
     *
     * @return boolean True si fue posible guardar al usuario, false si no.
     */
    public function save(): bool
    {
        if ($this->guest()) {
            return false;
        }
        $cacheKey = $this->sessionKey . '.' . $this->user->id;
        $cacheExpires = config('session.lifetime') * 60;
        return $this->cacheService->set($cacheKey, $this->user, $cacheExpires);
    }

    /**
     * Determina si el usuario actual está autenticado.
     *
     * @return bool
     */
    public function check(): bool
    {
        // Validaciones de la guard heredada.
        if (!parent::check()) {
            return false;
        }
        // Validar que usuario esté asignado.
        $user = $this->user();
        if (!$user) {
            return false;
        }
        // Validar múltiples logins.
        if ($this->sessionHash != $user->getRememberToken()) {
            $this->logout();
            return $this->error(__(
                'Sesión del usuario %s fue cerrada, pues dejó de ser válida.',
                $user->usuario
            ));
        }
        // Todo ok.
        return true;
    }

    /**
     * Intenta autenticar al usuario con las credenciales dadas.
     *
     * @param array $credentials
     * @return bool
     */
    public function attempt(array $credentials): bool
    {
        // Obtener instancia del usuario que se quiere autenticar.
        $user = $this->provider->retrieveByCredentials($credentials);
        if ($user === null) {
            return $this->error(__('Usuario solicitado no existe.'));
        }
        // Validar que el usuario esté activo.
        if (!$user->isActive()) {
            return $this->error(__(
                'Cuenta de usuario %s no se encuentra activa.',
                $user->usuario
            ));
        }
        // Validar intentos de sesión.
        if (!$user->contrasenia_intentos) {
            return $this->error(__(
                'Cuenta de usuario %s fue bloqueada por exceder intentos de sesión, debe recuperar su contraseña.',
                $user->usuario
            ));
        }
        // Validar el captcha.
        // Se valida el captcha solo si ya hubo un intento de sesión fallido.
        $max_login_attempts = config('auth.max_login_attempts');
        if (
            $max_login_attempts
            && $user->contrasenia_intentos < $max_login_attempts
        ) {
            try {
                \sowerphp\general\Utility_Google_Recaptcha::check();
            } catch (\Exception $e) {
                return $this->error(__(
                    'Captcha incorrecto para el usuario %s.',
                    $user->usuario,
                ) . ' ' . $e->getMessage());
            }
        }
        // Validar credenciales.
        if (!$this->provider->validateCredentials($user, $credentials)) {
            if ($max_login_attempts) {
                $user->savePasswordRetry($user->contrasenia_intentos - 1);
            }
            return $this->error(__('Contraseña inválida.'));
        }
        // Validar Token 2FA.
        $auth2_token = $credentials['2fa_token']
            ?? $credentials['auth2_token']
            ?? null
        ;
        try {
            $user->checkAuth2($auth2_token);
        } catch (\Exception $e) {
            return $this->error(__(
                'Autenticación secundaria del usuario %s falló: %s',
                $user->usuario,
                $e->getMessage()
            ));
        }
        // Todas las validaciones pasaron.
        // Crear sesión del usuario.
        $this->setUser($user);
        return true;
    }

    /**
     * Establece el usuario autenticado para el guard.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    public function setUser(Authenticatable $user): void
    {
        parent::setUser($user);
        $user->savePasswordRetry(config('auth.max_login_attempts'));
        $this->sessionHash = $user->createRememberToken(
            $this->request->fromIp(true)
        );
        $user->setRememberToken($this->sessionHash);
        $this->sessionService->put($this->sessionKey, [
            'id' => $user->id,
            'hash' => $this->sessionHash,
        ]);
    }

    /**
     * Cierra la sesión del usuario autenticado.
     *
     * @return void
     */
    public function logout(): void
    {
        $cacheKey = $this->sessionKey . '.' . $this->user()->id;
        $this->cacheService->forget($cacheKey);
        $this->sessionService->flush();
        $this->user = null;
    }

    /**
     * Método que realiza el login del usuario a través de preautenticación
     */
    /*public function preauth($token, $usuario = null, $auth2_token = null)
    {
        // autenticar solo con token (este será el hash del usuario)
        if (!$usuario) {
            $this->User = new $this->settings['model']($token);
        }
        // autenticar con los datos del token
        else {
            $key = config('app.key');
            if (!$key) {
                return false;
            }
            $real_token = md5($usuario . date('Ymd') . $key);
            if ($token != $real_token) {
                return false;
            }
            $this->User = new $this->settings['model']($usuario);
        }
        // si el usuario no existe error
        if (!$this->User->exists() || !$this->User->isActive()) {
            return false;
        }
        // verificar token de autenticación secundaria
        try {
            $this->User->checkAuth2($auth2_token);
        } catch (\Exception $e) {
            return false;
        }
        // crear sesión
        $this->createSession();
        return true;
    }*/

}
