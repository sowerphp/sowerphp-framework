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

namespace sowerphp\core;

use Illuminate\Support\Str;
use Illuminate\Contracts\Auth\Guard;
use \Illuminate\Contracts\Auth\Authenticatable;

/**
 * Clase base para las Auth_Guard_X.
 */
abstract class Auth_Guard implements Guard
{

    protected $name;
    protected $configService;
    protected $request;
    protected $provider;
    protected $user;

    /**
     * Constructor de la guard.
     *
     * Asigna parámetros e instancias necesarias para poder usar la guard con
     * el servicio de autenticación.
     *
     * @param Service_Config $configService
     */
    public function __construct(
        App $app,
        Service_Config $configService,
        Network_Request $request
    )
    {
        $this->configService = $configService;
        $this->request = $request;
        if (!isset($this->name)) {
            $this->name = Str::snake(
                explode('Auth_Guard_', get_class($this))[1]
            );
        }
        $providerName = app('inflector')->singularize(
            $this->getConfig()['provider']
        );
        $providerClass =
            __NAMESPACE__
            . '\Auth_Provider_' . ucfirst(Str::camel($providerName))
        ;
        if (!$app->getContainer()->bound($providerClass)) {
            $app->getContainer()->singleton($providerClass, $providerClass);
        }
        $this->provider = $app->getContainer()->make($providerClass);
    }

    /**
     * Inicializa la guard.
     *
     * @return void
     */
    abstract public function boot();

    /**
     * Entrega la configuración de la guard.
     *
     * @return array
     */
    protected function getConfig(): array
    {
        return $this->configService['auth.guards.' . $this->name];
    }

    /**
     * Determina si el usuario actual está autenticado.
     *
     * @return bool
     */
    public function check(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }
        return true;
    }

    /**
     * Determina si el usuario actual no está autenticado.
     *
     * @return bool
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Devuelve el usuario autenticado actualmente.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user(): ?Authenticatable
    {
        return $this->user;
    }

    /**
     * Devuelve el ID del usuario autenticado actualmente.
     *
     * @return mixed
     */
    public function id(): ?int
    {
        return $this->user() ? $this->user()->id : null;
    }

    /**
     * Establece el usuario autenticado para el guard.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    public function setUser(Authenticatable $user): void
    {
        $this->user = $user;
    }

    /**
     * Valida las credenciales de un usuario.
     *
     * @param  array $credentials
     * @return bool
     */
    public function validate(array $credentials = []): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);
        if (!$user) {
            return false;
        }
        return $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Procesa de manera adecuada, según configuración, el error generado en la
     * guard al ser utilizada.
     *
     * @param string $message Mensaje con el error que ocurrió en la guard.
     * @return boolean Se retornará falso siempre si la respuesta debe ser bool.
     */
    protected function error(string $message): bool
    {
        // Generar excepción con el error si así está configurado para la guard.
        if ($this->getConfig()['error_as_exception']) {
            throw new \Exception($message, 401);
        }
        // Retornar falso al ser error.
        return false;
    }

}
