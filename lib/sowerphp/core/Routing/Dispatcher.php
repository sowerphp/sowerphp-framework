<?php

/**
 * SowerPHP: Minimalist Framework for PHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

namespace sowerphp\core;

/**
 * Clase para despachar la página que se esté solicitando
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-21
 */
class Routing_Dispatcher
{

    /**
     * Método que despacha la página solicitada
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-21
     */
    public static function dispatch ()
    {
        $request = new Network_Request ();
        $response = new Network_Response ();
        // Verificar si el recurso solicitado es un archivo físico dentro del
        // directorio webroot
        if (self::_asset($request->request, $response)) {
            // retorna el método Dispatcher::dispatch, con lo cual termina el
            // procesado de la página
            return;
        }
        // Parsear parámetros del request
        $request->params = Routing_Router::parse ($request->request);
        // Si se solicita un módulo tratar de cargar y verificar que quede activo
        if (!empty($request->params['module'])) {
            Module::load($request->params['module']);
            if (!Module::loaded($request->params['module'])) {
                throw new Exception_Module_Missing(array(
                    'module' => $request->params['module']
                ));
            }
        }
        // Obtener controlador
        $controller = self::_getController($request, $response);
        // Verificar que lo obtenido sea una instancia de la clase Controller
        if (!($controller instanceof Controller)) {
            throw new Exception_Controller_Missing(array(
                'class' => 'Controller_'.Utility_Inflector::camelize(
                    $request->params['controller']
                )
            ));
        }
        // Invocar a la acción del controlador
        return self::_invoke($controller, $request, $response);
    }

    /**
     * Busca si lo solicitado existe físicamente en el servidor y lo entrega
     * @param url Ruta de los que se está solicitando
     * @param response Objeto Response
     * @return Verdadero si lo solicitado existe dentro de /webroot
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-21
     */
    private static function _asset($url, Network_Response $response)
    {
        // Si la URL es vacía se retorna falso
        if ($url=='')
            return false;
        // Inicializar el archivo como null
        $assetFile = null;
        // Buscar el archivo en los posibles directorios webroot, incluyendo
        // los paths de los modulos
        $paths = null;
        // si hay más de dos slash en la url podría ser un módulo así que se
        // busca path para el modulo
        $slashPos = strpos($url, '/', 1);
        if ($slashPos) {
            // paths de plugins
            $module = Module::find($url);
            if (isset($module[0])) {
                Module::load($module);
                $paths = Module::paths($module);
            }
            // si existe el módulo en los paths entonces si es un módulo lo que
            // se está pidiendo, y es un módulo ya cargado. Si este no fuera el
            // caso podría no ser plugin, o no estar cargado
            if ($paths) {
                $removeCount = count(explode('.', $module)) + 1;
                $aux = explode('/', $url);
                while ($removeCount-->0)
                    array_shift($aux);
                $url = '/'.implode('/', $aux);
            }
        }
        // si no está definido el path entonces no era de módulo y se deberá
        // buscar en los paths de la aplicación
        if (!$paths)
            $paths = App::paths();
        // en cada paths encontrado buscar el archivo solicitado
        foreach ($paths as &$path) {
            $file = $path.'/webroot'.$url;
            if (file_exists($file) && !is_dir($file)) {
                $assetFile = $file;
                break;
            }
        }
        // Si se encontró el archivo se envía al cliente
        if ($assetFile!==null) {
            // Solo se entrega mediante PHP si el archivo no está en
            // DIR_WEBSITE/webroot
            if (!strpos($assetFile, DIR_WEBSITE)!==false) {
                $response->sendFile($assetFile);
            }
            return true;
        }
        // Si no se encontró se retorna falso
        return false;
    }

    /**
     * Método que obtiene el controlador
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-22
     */
    private static function _getController (Network_Request $request,
                                                    Network_Response $response)
    {
        // Cargar clase del controlador
        $class = App::findClass (
            'Controller_'.Utility_Inflector::camelize(
                $request->params['controller']
            ),
            $request->params['module']
        );
        if (!class_exists($class)) {
            return false;
        }
        // Se verifica que la clase no sea abstracta
        $reflection = new \ReflectionClass($class);
        if ($reflection->isAbstract()) {
                return false;
        }
        // Se retorna la clase instanciada del controlador con los parámetros
        // $request y $response al constructor
        return $reflection->newInstance($request, $response);
    }

    /**
     * Método que se encarga de invocar a la acción del controlador y
     * entregar la respuesta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-22
     */
    private static function _invoke(Controller $controller,
                        Network_Request $request, Network_Response $response)
    {
        // Iniciar el proceso
        $controller->startupProcess();
        // Ejecutar acción
        $result = $controller->invokeAction();
        // Renderizar proceso
        if ($controller->autoRender) {
            $response = $controller->render();
        } elseif ($response->body() === null) {
            $response->body($result);
        }
        // Detener el proceso
        $controller->shutdownProcess();
        // Retornar respuesta al cliente
        if (isset($request->params['return'])) {
            return $response->body();
        }
        // Enviar respuesta al cliente
        $response->send();
    }

}
