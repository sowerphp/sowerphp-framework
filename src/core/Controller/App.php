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
 * Clase para manejar acciones específicas con páginas que no requieren
 * ejecutar una acción o bien esta es parametrizable y se ejecuta en base a
 * configuraciones.
 */
class Controller_App extends \sowerphp\app\Controller
{

    /**
     * Método que se ejecuta antes de ejecutar la acción del controlador.
     */
    public function boot(): void
    {
        if (isset($this->Auth)) {
            $this->Auth->allow(
                'page',
                'error',
                'redirect',
            );
            $this->Auth->allowWithLogin('session');
        }
        parent::boot();
    }

    /**
     * Asignar un valor a una configuración de la sesión.
     *
     * @param string $config Nombre de la configuración de la sesión.
     * @param string $value Valor de la configuración que se asignará.
     * @param string|null $redirect Hacia donde se debe redirigir al asignar.
     * @return Network_Response
     */
    public function session(
        string $config,
        string $value,
        ?string $redirect = null
    ): Network_Response
    {
        session(['config.' . $config => $value]);
        $url = $redirect ? base64_decode($redirect) : '/';
        session()->reflash();
        return redirect($url);
    }

    /**
     * Renderizar la vista de una página "estática" (sin acción).
     *
     * @param string $page Vista que se desea renderizar ubicada en View/Pages.
     * @return Network_Response
     */
    public function page(string $page): Network_Response
    {
        $page = $page ? $page : config('app.ui.homepage');
        return $this->render('Pages' . $page);
    }

    /**
     * Mostrar la página principal para el módulo (con sus opciones de menú).
     *
     * @return Network_Response
     */
    public function module(): Network_Response
    {
        $moduleService = app('module');
        $module = $this->request->getRouteConfig()['module'];
        // Si existe una vista para el del modulo se usa.
        if ($moduleService->getFilePath($module, '/View/index.php')) {
            return $this->render('index');
        }
        // Si no se incluye el archivo con el título y el menú para el módulo.
        else {
            // Menú del módulo.
            $module_nav = $moduleService->getModuleNav($module);
            // Nombre del módulo para URL.
            $module_url = str_replace(
                '.',
                '/',
                \sowerphp\core\Utility_Inflector::underscore($module)
            );
            // Verificar permisos.
            foreach ($module_nav as $category_id => &$category) {
                foreach ($category['menu'] as $link => &$info) {
                    // Si info no es un arreglo es solo el nombre y se arma.
                    if (!is_array($info)) {
                        $info = [
                            'name' => $info,
                            'desc' => '',
                            'icon' => 'fa-solid fa-link',
                        ];
                    }
                    // Si es un arreglo colocar opciones por defecto.
                    else {
                        $info = array_merge([
                            'name' => $link,
                            'desc' => '',
                            'icon' => 'fa-solid fa-link',
                        ], $info);
                    }
                    // Verificar permisos para acceder al enlace.
                    if (!$this->Auth->check('/' . $module_url . $link)) {
                        unset($module_nav[$category_id]['menu'][$link]);
                    }
                }
                $module_nav[$category_id]['quantity'] = count(
                    $module_nav[$category_id]['menu']
                );
                if (!$module_nav[$category_id]['quantity']) {
                    unset($module_nav[$category_id]);
                }
            }
            // Renderizar la vista.
            return $this->render('App/module', [
                'title' => config('modules.' . $module . '.title')
                    ?? str_replace ('.', ' &raquo; ', $module)
                ,
                'nav' => $module_nav,
                'module' => $module_url,
            ]);
        }
    }

    /**
     * Renderizar error.
     *
     * @param \Exception $exception Excepción con el error que se renderizará.
     * @return Network_Response
     */
    public function error($exception): Network_Response
    {
        ob_clean();
        // Es una solicitud mediante un servicio web.
        if ($this->request->isApiRequest()) {
            return $this->Api->sendException($exception);
        }
        // Es una solicitud mediante la interfaz web.
        $data = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'code' => $exception->getCode(),
            'severity' => $exception->severity ?? LOG_ERR,
        ];
        // Armar datos para la vista HTML (web).
        $this->layout .= '.min';
        $data['error_reporting'] = config('app.debug');
        $layersService = app('layers');
        $data['message'] = htmlspecialchars($data['message']);
        $data['trace'] = str_replace(
            [
                $layersService->getFrameworkPath(),
                $layersService->getProjectPath(),
            ],
            [
                'framework:',
                'project:',
            ],
            $data['trace']
        );
        $data['soporte'] = config('mail.to.address') !== null;
        // Registrar log con el error.
        $this->Log->write([
            'exception' => $data['exception'],
            'message' => $data['message'],
            'trace' => $data['trace'],
            'code' => $data['code'],
        ], $data['severity']);
        // Renderizar página de error.
        $response = $this->render('App/error', $data);
        $response->status($data['code']);
        return $response;
    }

    /**
     * Redireccionar a una ruta nueva o a una URL completa (incluso externa).
     *
     * @param string $destination Ruta a la que se quiere redireccionar.
     * @param int $status Código de redireccionamiento (3xx).
     * @return Network_Response
     */
    public function redirect(string $destination, int $status = 302): Network_Response
    {
        session()->reflash();
        return redirect($destination, $status);
    }

}
