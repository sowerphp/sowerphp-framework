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

/**
 * Clase que gestiona la autorización de acciones dentro de la aplicación,
 * permitiendo controlar el acceso basado en si el usuario está autenticado y
 * si tiene permisos específicos para realizar ciertas acciones.
 */
class Auth_Authorization
{

    /**
     * Lista de acciones permitidas sin necesidad de estar autenticado.
     *
     * @var array
     */
    protected $allowedActionsWithoutLogin = [];

    /**
     * Lista de acciones que cualquier usuario autenticado puede realizar, sin
     * necesidad de permisos adicionales.
     *
     * @var array
     */
    protected $allowedActionsWithLogin = [];

    /**
     * Referencia al usuario actualmente autenticado. Este objeto debería
     * contener al menos la identidad del usuario y cualquier rol o permiso
     * asociado.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected $user;

    /**
     * Objeto que contiene información sobre la solicitud HTTP actual.
     *
     * @var Request
     */
    protected $request;

    /**
     * Constructor de Auth_Authorization.
     *
     * @param object $user El usuario actualmente autenticado.
     * @param Network_Request $request El objeto de la solicitud HTTP.
     */
    public function __construct($user, $request)
    {
        $this->user = $user;
        $this->request = $request;
    }

    /**
     * Agrega acciones a la lista de acciones permitidas sin autenticación.
     *
     * @param string ...$actions Las acciones para permitir.
     * @return void
     */
    public function allowActionsWithoutLogin(string ...$actions): void
    {
        foreach ($actions as $action) {
            if (
                !in_array($action, $this->allowedActionsWithoutLogin)
                && !in_array($action, $this->allowedActionsWithLogin)
            ) {
                $this->allowedActionsWithoutLogin[] = $action;
            }
        }
    }

    /**
     * Agrega acciones a la lista de acciones que cualquier usuario autenticado
     * puede ejecutar.
     *
     * @param string ...$actions Las acciones para permitir a usuarios
     * autenticados.
     * @return void
     */
    public function allowActionsWithLogin(string ...$actions): void
    {
        foreach ($actions as $action) {
            if (
                !in_array($action, $this->allowedActionsWithoutLogin)
                && !in_array($action, $this->allowedActionsWithLogin)
            ) {
                $this->allowedActionsWithLogin[] = $action;
            }
        }
    }

    /**
     * Determina si la acción solicitada puede ser ejecutada sin autenticación.
     *
     * @param string $action La acción solicitada que se desea validar.
     * @return bool True si la acción puede ser ejecutada sin autenticación,
     * false de lo contrario.
     */
    public function isActionAllowedWithoutLogin(string $action): bool
    {
        return in_array($action, $this->allowedActionsWithoutLogin);
    }

    /**
     * Determina si la acción solicitada puede ser ejecutada con sólo estar
     * autenticado, sin verificación de permisos adicionales.
     *
     * @param string $action La acción solicitada que se desea validar.
     * @return bool True si la acción puede ser ejecutada por cualquier usuario
     * autenticado, false de lo contrario.
     */
    public function isActionAllowedWithLogin(string $action): bool
    {
        return in_array($action, $this->allowedActionsWithLogin);
    }

    /**
     * Verifica si el usuario actual o especificado tiene permisos para acceder
     * al recurso solicitado.
     *
     * @param string $resource El recurso para el cual se verifica el permiso.
     * @return bool True si el usuario tiene permiso, false de lo contrario.
     */
    public function checkResourcePermission(string $resource): bool
    {
        return $this->user ? $this->user->auth($resource) : false;
    }

    /**
     * Verifica y autoriza la acción actual basada en las reglas de
     * autorización definidas. Implementa la lógica para redirigir o bloquear
     * el acceso si es necesario.
     *
     * @param string $action La acción solicitada que se desea validar.
     * @param string $resource El recurso para el cual se verifica el permiso.
     * @return bool True si se puede acceder a la acción, false si no se puede.
     */
    public function checkFullAuthorization(
        ?string $action = null,
        ?string $resource = null
    ): bool
    {
        // Definir acción que se verificará si no se pasó.
        if ($action === null) {
            $action = $this->request->getRouteConfig()['action'];
        }
        // Si la acción se encuentra dentro de las permitidas sin tener que
        // estar autenticado se autoriza inmediatamente.
        if ($this->isActionAllowedWithoutLogin($action)) {
            return true;
        }
        // Cualquier validación ahora necesita al usuario asignado.
        // Si el usuario no está asignado se retorna falso.
        if (!$this->user) {
            return false;
        }
        // Si la acción se encuentra dentro de las que solo requieren un
        // usuario asignado se acepta.
        if ($this->isActionAllowedWithLogin($action)) {
            return true;
        }
        // Definir recurso que se verificará si no se pasó.
        if ($resource === null) {
            $resource = $this->request->getRequestUriDecoded();
        }
        // La acción requiere permisos, por lo que se debe validar
        return $this->checkResourcePermission($resource);
    }

}
