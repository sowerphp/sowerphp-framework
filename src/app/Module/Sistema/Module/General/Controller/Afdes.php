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

// namespace del controlador
namespace sowerphp\app\Sistema\General;

use \sowerphp\core\Facade_Session_Message as SessionMessage;

/**
 * Clase para el controlador asociado a la tabla afd de la base de
 * datos
 * Comentario de la tabla:
 * Esta clase permite controlar las acciones entre el modelo y vista para la
 * tabla afd
 */
class Controller_Afdes extends \Controller_Maintainer
{

    protected $namespace = __NAMESPACE__; ///< Namespace del controlador y modelos asociados

    public function beforeFilter()
    {
        $this->Auth->allowWithLogin('grafo');
        parent::beforeFilter();
    }

    /**
     * Acción para crear un AFD
     */
    public function crear()
    {
        $filterListar = !empty($_GET['listar']) ? base64_decode($_GET['listar']) : '';
        // si se envió el formulario se procesa
        if (isset($_POST['submit'])) {
            $Afd = new Model_Afd();
            $Afd->codigo = $_POST['codigo'];
            $Afd->nombre = $_POST['nombre'];
            $Afd->estados = [
                'codigos' => $_POST['estado_codigo'],
                'nombres' => $_POST['estado_nombre']
            ];
            $Afd->transiciones = [
                'desdes' => $_POST['desde'],
                'valores' => $_POST['valor'],
                'hastas' => $_POST['hasta']
            ];
            $Afd->save();
            SessionMessage::success('AFD <em>'.$Afd->nombre.'</em> creado.');
            $this->redirect(
                $this->module_url . $this->request->getRouteConfig()['controller'] . '/listar' . $filterListar
            );
        }
        // setear variables
        $this->set([
            'accion' => 'Crear',
            'listarUrl' => $this->module_url . $this->request->getRouteConfig()['controller'] . '/listar' . $filterListar,
        ]);
        // renderizar
        $this->autoRender = false;
        $this->render('Afdes/crear_editar');
    }

    /**
     * Acción para editar un AFD
     * @param codigo Código del AFD a editar
     */
    public function editar($codigo)
    {
        $filterListar = !empty($_GET['listar']) ? base64_decode($_GET['listar']) : '';
        $Afd = new Model_Afd($codigo);
        // si el registro que se quiere editar no existe error
        if(!$Afd->exists()) {
            SessionMessage::error(
                'AFD <em>'.$codigo.'</em> no existe, no se puede editar.'
            );
            $this->redirect(
                $this->module_url . $this->request->getRouteConfig()['controller'] . '/listar' . $filterListar
            );
        }
        // si no se ha enviado el formulario se mostrará
        if(!isset($_POST['submit'])) {
            $this->set(array(
                'Afd' => $Afd,
                'accion' => 'Editar',
                'listarUrl' => $this->module_url . $this->request->getRouteConfig()['controller'] . '/listar' . $filterListar,
            ));
            // renderizar
            $this->autoRender = false;
            $this->render('Afdes/crear_editar');
        }
        // si se envió el formulario se procesa
        else {
            $Afd->codigo = $_POST['codigo'];
            $Afd->nombre = $_POST['nombre'];
            $Afd->estados = [
                'codigos' => $_POST['estado_codigo'],
                'nombres' => $_POST['estado_nombre']
            ];
            $Afd->transiciones = [
                'desdes' => $_POST['desde'],
                'valores' => $_POST['valor'],
                'hastas' => $_POST['hasta']
            ];
            $Afd->save();
            SessionMessage::success('AFD <em>'.$Afd->nombre.'</em> editado.');
            $this->redirect(
                $this->module_url . $this->request->getRouteConfig()['controller'] . '/editar/' . $codigo
                . (!empty($_GET['listar']) ? '?listar='.$_GET['listar'] : '')
            );
        }
    }

    /**
     * Acción que genera la imagen del grafo del AFD
     * @param codigo Código del AFD a generar su imagen
     */
    public function grafo($codigo)
    {
        $image = (new \sowerphp\general\Utility_Automata_AFD(
            (new Model_Afd($codigo))->getTransiciones()
        ))->image();
        $this->response->header('Content-type', 'image/png');
        $this->response->sendAndExit($image);
    }

}
