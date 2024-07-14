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

/**
 * Servicio de autenticación de la aplicación.
 */
class Service_Http_Auth implements Interface_Service
{

    protected $app;
    protected $configService;
    protected $request;
    protected $guards;
    protected $authorization;

    public function __construct(
        App $app,
        Service_Config $configService,
        Network_Request $request
    )
    {
        $this->app = $app;
        $this->configService = $configService;
        $this->request = $request;
    }

    /**
     * Registra el servicio de autenticación HTTP.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio de autenticación HTTP.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadGuards();
        $guard = $this->guard();
        $guard->boot();
        $this->authorization = new Auth_Authorization(
            $guard->user(),
            $this->request
        );
    }

    /**
     * Cargar las guards que estén configuradas para ser usadas en la
     * aplicación.
     *
     * @return void
     */
    protected function loadGuards(): void
    {
        $guards = $this->configService['auth.guards'];
        foreach ($guards as $guard => $config) {
            $this->configService['auth.guards.' . $guard . '.name'] = $guard;
            if (!isset($config['class'])) {
                $this->configService['auth.guards.' . $guard . '.class'] =
                    __NAMESPACE__
                    . '\Auth_Guard_' . ucfirst(Str::camel($guard))
                ;
            }
            $key = 'auth_' . $guard;
            $this->app->registerService(
                $key,
                $this->configService['auth.guards.' . $guard . '.class']
            );
            $this->guards[$guard] = app($key);
        }
    }

    /**
     * Finaliza el servicio de autenticación HTTP.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Entregar la guard solicitada, una según el contexto en que estemos
     * ejecutando la apliación o la por defecto.
     *
     * @param string|null $name Nombre de la guard que se desea obtener.
     * @return Auth_Guard Guard
     */
    public function guard(?string $name = null): Auth_Guard
    {
        if ($name === null) {
            if ($this->request->isHttpRequest()) {
                $name = $this->request->isApiRequest() ? 'api' : 'web';
            } else {
                $name = $this->configService['auth.defaults.guard'];
            }
        }
        return $this->guards[$name];
    }

    /**
     * Cualquier método que no esté definido en el servicio será llamado en la
     * instancia de autorización.
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->authorization, $method], $parameters);
    }

}
