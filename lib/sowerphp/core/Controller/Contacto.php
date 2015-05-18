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
 * Controlador para página de contacto
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-12-10
 */
class Controller_Contacto extends \Controller_App
{

    /**
     * Método para autorizar la carga de index en caso que hay autenticación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-03-18
     */
    public function beforeFilter()
    {
        if (isset($this->Auth))
            $this->Auth->allow('index');
        parent::beforeFilter();
    }

    /**
     * Método que desplegará y procesará el formulario de contacto
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-04-12
     */
    public function index()
    {
        // si no hay datos para el envió del correo electrónico no
        // permirir cargar página de contacto
        if (Configure::read('email.default')===NULL) {
            Model_Datasource_Session::message(
                'Página de contacto deshabilitada', 'error'
            );
            $this->redirect('/');
        }
        // si se envió el formulario se procesa
        if (isset($_POST['submit'])) {
            $_POST['nombre'] = trim(strip_tags($_POST['nombre']));
            $_POST['correo'] = trim(strip_tags($_POST['correo']));
            $_POST['mensaje'] = trim(strip_tags($_POST['mensaje']));
            if (!empty($_POST['nombre']) and !empty($_POST['correo']) and !empty($_POST['mensaje'])) {
                $email = new Network_Email();
                $email->replyTo($_POST['correo'], $_POST['nombre']);
                $email->to(Configure::read('email.default.to'));
                $email->subject('Contacto desde la web #'.date('YmdHis'));
                $msg = $_POST['mensaje']."\n\n".'-- '."\n".$_POST['nombre']."\n".$_POST['correo'];
                $status = $email->send($msg);
                if ($status===true) {
                    Model_Datasource_Session::message(
                        'Su mensaje ha sido enviado, se responderá a la brevedad.', 'ok'
                    );
                    $this->redirect('/contacto');
                } else {
                    Model_Datasource_Session::message(
                        'Ha ocurrido un error al intentar enviar su mensaje, por favor intente nuevamente.<br /><em>'.$status['message'].'</em>', 'error'
                    );
                }
            } else {
                Model_Datasource_Session::message(
                    'Debe completar todos los campos del formulario', 'error'
                );
            }
        }
    }

}
