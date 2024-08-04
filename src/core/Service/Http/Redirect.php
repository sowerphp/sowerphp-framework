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

/**
 * Servicio de redirección HTTP.
 *
 * Este servicio maneja las redirecciones HTTP en la aplicación.
 */
class Service_Http_Redirect extends Network_Response implements Interface_Service
{

    /**
     * Servicio de enrutamiento.
     *
     * @var Service_Http_Router
     */
    protected $routerService;

    /**
     * Constructor del servicio.
     *
     * @param Service_Http_Router $routerService Instancia con el servicio de
     * enrutamiento.
     */
    public function __construct(Service_Http_Router $routerService)
    {
        $this->routerService = $routerService;
    }

    /**
     * Registra el servicio de redirección HTTP.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio de redirección HTTP.
     *
     * @return void
     */
    public function boot(): void
    {
    }

    /**
     * Finaliza el servicio de redirección HTTP.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Redirecciona a una URL específica.
     *
     * @param string $to La URL a la que redirigir.
     * @param int $status El código de estado HTTP de la redirección.
     * @param array $headers Encabezados adicionales para la respuesta.
     * @return self Instancia del servicio.
     */
    public function to(string $to, int $status = 302, array $headers = []): self
    {
        // Asignar URL de la redirección.
        $url = $to ? ($to[0] == '/' ? url($to) : $to) : url();
        $this->header('Location', $url);
        // Asignar estado HTTP de la dirección.
        $this->status($status);
        // Asignar cabeceras HHTP de la redirección.
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }
        // Entregar la instancia del servicio para usar o encadenar.
        return $this;
    }

    /**
     * Redirecciona a la URL anterior.
     *
     * @param int $status El código de estado HTTP de la redirección.
     * @param array $headers Encabezados adicionales para la respuesta.
     * @return self
     */
    public function back(int $status = 302, array $headers = []): self
    {
        $url = request()->headers->get('referer') ?? '/';
        return $this->to($url, $status, $headers);
    }

    /**
     * Redirecciona a una ruta específica.
     *
     * @param string $name El nombre de la ruta.
     * @param array $parameters Los parámetros de la ruta.
     * @param int $status El código de estado HTTP de la redirección.
     * @param array $headers Encabezados adicionales para la respuesta.
     * @return self
     */
    public function route(
        string $name,
        array $parameters = [],
        int $status = 302,
        array $headers = []
    ): self
    {
        $url = $this->routerService->resolveRouteToUrl($name, $parameters);
        if ($url === null) {
            throw new \Exception(__('No se encontró la ruta HTTP "%s".', $name));
        }
        return $this->to($url, $status, $headers);
    }

    /**
     * Añade datos flash a la sesión o mensajes de estado específicos.
     * Si la clave coincide con tipos de mensaje ('info', 'success',
     * 'warning', 'error'), usa la fachada de mensaje para manejarlos.
     * De lo contrario, añade el valor a la sesión como dato flash.
     *
     * @param string $key Clave para el dato o mensaje.
     * @param mixed $value Valor del dato o contenido del mensaje.
     * @return self Instancia actual para permitir encadenamiento.
     */
    public function with(string $key, $value): self
    {
        $statusMessages = ['info', 'success', 'warning', 'error'];
        if (in_array($key, $statusMessages)) {
            \sowerphp\core\Facade_Session_Message::$key($value);
        } else {
            session()->flash($key, $value);
        }
        return $this;
    }

    /**
     * Añade un mensaje informativo a la sesión utilizando la clave 'info'.
     * Útil para notificaciones generales al usuario.
     *
     * @param string $value Mensaje informativo a añadir.
     * @return self Instancia actual para permitir encadenamiento.
     */
    public function withInfo(string $value): self
    {
        return $this->with('info', $value);
    }

    /**
     * Añade un mensaje de éxito a la sesión utilizando la clave 'success'.
     * Ideal para confirmaciones de operaciones exitosas.
     *
     * @param string $value Mensaje de éxito a añadir.
     * @return self Instancia actual para permitir encadenamiento.
     */
    public function withSuccess(string $value): self
    {
        return $this->with('success', $value);
    }

    /**
     * Añade un mensaje de advertencia a la sesión utilizando la clave 'warning'.
     * Usado para alertas o advertencias importantes para el usuario.
     *
     * @param string $value Mensaje de advertencia a añadir.
     * @return self Instancia actual para permitir encadenamiento.
     */
    public function withWarning(string $value): self
    {
        return $this->with('warning', $value);
    }

    /**
     * Añade un mensaje de error a la sesión utilizando la clave 'error'.
     * Específicamente para errores críticos que necesitan atención inmediata.
     *
     * @param string $value Mensaje de error a añadir.
     * @return self Instancia actual para permitir encadenamiento.
     */
    public function withError(string $value): self
    {
        return $this->with('error', $value);
    }

    /**
     * Añade múltiples mensajes de error a la sesión. Si se pasa una cadena,
     * se convierte en un arreglo con la clave 'error' antes de procesarlo.
     * Ideal para manejar errores no específicos de campos en formularios.
     *
     * @param array|string $errors Puede ser un array asociativo de errores
     * donde cada clave es el campo y cada valor es el mensaje asociado,
     * o un mensaje de error único como string.
     * @return self Instancia actual para permitir encadenamiento.
     */
    public function withErrors($errors, string $key = 'default'): self
    {
        if (!is_array($errors)) {
            $errors = ['error' => $errors];
        }
        $this->with('errors.' . $key, $errors);
        return $this;
    }

    /**
     * Almacena los datos de entrada en la sesión para que estén disponibles en
     * la próxima solicitud. Esto es útil para redirigir a un formulario con
     * los datos que el usuario ingresó previamente.
     *
     * @param array $input Arreglo con los datos de entrada a almacenar en la
     * sesión. Si no se proporciona, se utilizarán los datos de la solicitud
     * actual.
     * @return $this Instancia actual para permitir el encadenamiento.
     */
    public function withInput(array $input = [])
    {
        $input = $input ?: request()->input();
        session()->flashInput($input);
        return $this;
    }

    /**
     * Forzar la ejecución del redireccionamiento.
     *
     * @return void
     */
    public function now(): void
    {
        $this->send();
        exit();
    }

}
