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

namespace sowerphp\core;

class Service_Http_Kernel implements Interface_Service
{

    use Trait_Service;

    protected $layersService;
    protected $moduleService;

    protected $request;
    protected $response;

    public function boot()
    {
        $this->layersService = $this->app->make('layers');
        $this->moduleService = $this->app->make('module');
        $this->request = new Network_Request();
        $this->response = new Network_Response();
    }

    public function handle()
    {
        // Revisar si la solicitud es por un archivo estático.
        $filepath = $this->getFilePath($this->request->request);
        if ($filepath) {
            return $this->response->sendFile($filepath);
        }
        // Procesar la solicitud con un controlador.
        return $this->handleWithController();
    }

    /**
     * Busca si lo solicitado existe físicamente en el servidor y lo entrega.
     * La búsqueda se realiza en todos los posibles directorios webroot de la
     * aplicación, incluyendo las rutas de los modulos si la solicitud es de un
     * módulo.
     * @param string $url Ruta de los que se está solicitando.
     * @return string Ruta del archivo estático o null si no se encontró.
     */
    private function getFilePath(string $filename): ?string
    {
        // Si no hay archivo se retorna inmediatamente.
        if ($filename == '') {
            return null;
        }
        // Si hay más de dos slash en la url podría ser un módulo así que se
        // busca path para el módulo.
        $paths = null;
        $slashPos = strpos($filename, '/', 1);
        if ($slashPos) {
            // Rutas de módulos
            $module = $this->moduleService->findModuleByUrl($filename);
            if (isset($module[0])) {
                $this->moduleService->loadModule($module);
                $paths = $this->moduleService->getModulePaths($module);
            }
            // Si existe el módulo en las rutas entonces si es un módulo lo que
            // se está pidiendo, y es un módulo ya cargado. Si este no fuera el
            // caso podría no ser un módulo o no estar cargado.
            if ($paths) {
                $removeCount = count(explode('.', $module)) + 1;
                $aux = explode('/', $filename);
                while ($removeCount-- > 0) {
                    array_shift($aux);
                }
                $filename = '/' . implode('/', $aux);
            }
        }
        // Si no están definidas las rutas para buscar el archivos, entonces no
        // era de módulo y se deberá buscar en las rutas base de la aplicación.
        if (!$paths) {
            $paths = $this->layersService->getPaths();
        }
        // Buscar el archivo solicitado en las rutas determinadas.
        $filepath = null;
        foreach ($paths as &$path) {
            $file = $path . '/webroot' . $filename;
            if (file_exists($file) && !is_dir($file)) {
                $filepath = $file;
                break;
            }
        }
        return $filepath;
    }

    private function handleWithController()
    {
        $controller = $this->getController(
            $this->request->params['controller'],
            $this->request->params['module'] ?? null
        );
        if (!($controller instanceof Controller)) {
            throw new Exception_Controller_Missing(array(
                'class' => 'Controller_'.Utility_Inflector::camelize(
                    $this->request->params['controller']
                )
            ));
        }
        return $this->dispatchController($controller);
    }

    /**
     * Método que obtiene la instancia del controlador.
     */
    private function getController(string $controller, ?string $module)
    {
        $this->loadModule($module);
        // Determinar nombre de la clase que se cargará mágicamente.
        $class = 'Controller_'.Utility_Inflector::camelize(
            $controller
        );
        if ($module && $class != 'Controller_Module') {
            $class = str_replace('.', '\\', $module) . '\\' . $class;
        }
        $class = '\\sowerphp\\magicload\\' . $class;
        // Cargar clase del controlador.
        if (!class_exists($class)) {
            return null;
        }
        // Se verifica que la clase no sea abstracta
        $reflection = new \ReflectionClass($class);
        if ($reflection->isAbstract()) {
            return null;
        }
        // Se retorna la clase instanciada del controlador con los parámetros
        // $request y $response al constructor.
        return $reflection->newInstance($this->request, $this->response);
    }

    /**
     * Si se solicita un módulo tratar de cargar y verificar que quede activo.
     */
    private function loadModule(?string $module)
    {
        if (!empty($module)) {
            $this->moduleService->loadModule($module);
            if (!$this->moduleService->isModuleLoaded($module)) {
                throw new Exception_Module_Missing(array(
                    'module' => $module
                ));
            }
        }
    }

    private function dispatchController($controller)
    {
        // Iniciar el proceso.
        $controller->startupProcess();
        // Ejecutar acción.
        $result = $controller->invokeAction();
        // Renderizar proceso.
        if ($controller->autoRender) {
            $this->response = $controller->render();
        } elseif ($this->response->body() === null) {
            $this->response->body($result);
        }
        // Detener el proceso.
        $controller->shutdownProcess();
        // Retornar respuesta al cliente.
        if (isset($this->request->params['return'])) {
            return $this->response->body();
        }
        // Enviar respuesta al cliente.
        return $this->response->send();
    }

}
