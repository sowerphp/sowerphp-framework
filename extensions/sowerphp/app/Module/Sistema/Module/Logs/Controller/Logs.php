<?php

/**
 * SowerPHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
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
namespace sowerphp\app\Sistema\Logs;

/**
 * Clase para el controlador asociado a la tabla log de la base de
 * datos
 * Comentario de la tabla:
 * Esta clase permite controlar las acciones entre el modelo y vista para la
 * tabla log
 * @author SowerPHP Code Generator
 * @version 2015-04-28 11:56:29
 */
class Controller_Logs extends \Controller_Maintainer
{

    protected $namespace = __NAMESPACE__; ///< Namespace del controlador y modelos asociados

    /**
     * Acción para visualizar logs de manera en línea
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-04-28
     */
    public function online()
    {
    }

    /**
     * Acción para buscar logs de un usuario en particular
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-09-23
     */
    public function buscar()
    {
        if (isset($_POST['submit'])) {
            // crear usuario y verificar que exista
            $Usuario = new \sowerphp\app\Sistema\Usuarios\Model_Usuario($_POST['usuario']);
            if (!$Usuario->exists()) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Usuario solicitado no existe', 'error'
                );
                return;
            }
            // buscar eventos
            $eventos = [];
            $Logs = new Model_Logs();
            $Logs->setOrderByStatement('id DESC');
            $where = ['usuario = :usuario'];
            $vars = [':usuario' => $Usuario->id];
            if (!empty($_POST['desde'])) {
                $where[] = 'fechahora >= :desde';
                $vars[':desde'] = $_POST['desde'];
            }
            if (!empty($_POST['hasta'])) {
                $where[] = 'fechahora <= :hasta';
                $vars[':hasta'] = $_POST['hasta'].' 23:59:59';
            }
            $Logs->setWhereStatement($where, $vars);
            $logs = $Logs->getObjects();
            foreach ($logs as $Log) {
                $eventos[] = [
                    $Log->fechahora,
                    $Log->getFacility()->glosa.'.'.$Log->getSeverity()->glosa,
                    $Log->usuario ? ($Log->getUsuario()->usuario.'<br/><span>'.$Log->ip.'</span>') : $Log->ip,
                    strlen($Log->mensaje)>100 ? substr($Log->mensaje, 0, 100).'...': $Log->mensaje,
                    '<button type="button" class="btn btn-'.$Log->getSeverity()->style.'" data-toggle="modal" data-target="#modal-log" data-log_id="'.$Log->id.'" title="Ver detalle del evento"><span class="fa fa-search" aria-hidden="true"></span></button>',
                ];
            }
            $this->set([
                'eventos' => $eventos
            ]);
        }
    }

    /**
     * Acción de la API para obtener listado de logs o un log en particular
     * @param id ID del Log en caso que se desee obtener uno en particular
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-04-29
     */
    public function _api_crud_GET($id = null)
    {
        // obtener usuario autenticado
        if (($User=$this->Auth->User)===false and is_string($User=$this->Api->getAuthUser())) {
            $this->Api->send('Usuario no autenticado', 401);
        }
        // verificar permisos
        if (!$User->inGroup(['logs'])) {
            $this->Api->send('Usuario autenticado no puede acceder a los logs', 403);
        }
        // entregar listado de logs
        if ($id == null) {
            $eventos = ['data'=>[]];
            $Logs = new Model_Logs();
            $Logs->setOrderByStatement('id DESC');
            $Logs->setLimitStatement(100);
            $logs = $Logs->getObjects();
            foreach ($logs as $Log) {
                $eventos['data'][] = [
                    $Log->fechahora,
                    $Log->getFacility()->glosa.'.'.$Log->getSeverity()->glosa,
                    $Log->usuario ? ($Log->getUsuario()->usuario.'<br/><span>'.$Log->ip.'</span>') : $Log->ip,
                    strlen($Log->mensaje)>100 ? substr($Log->mensaje, 0, 100).'...': $Log->mensaje,
                    '<button type="button" class="btn btn-'.$Log->getSeverity()->style.'" data-toggle="modal" data-target="#modal-log" data-log_id="'.$Log->id.'" title="Ver detalle del evento"><span class="fa fa-search" aria-hidden="true"></span></button>',
                ];
            }
            return $eventos;
        } else {
            $Log = new Model_Log($id, true);
            if (!$Log->exists())
                $this->Api->send('Log no encontrado', 404);
            return [
                'identificador' => $Log->identificador,
                'solicitud' => $Log->solicitud,
                'mensaje' => $Log->mensaje,
                'origen' => $Log->getFacility()->glosa,
                'gravedad' => $Log->getSeverity()->glosa,
            ];
        }
    }

}
