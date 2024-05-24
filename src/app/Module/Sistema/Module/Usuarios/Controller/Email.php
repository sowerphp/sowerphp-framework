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
namespace sowerphp\app\Sistema\Usuarios;

/**
 * Controlador para el envío masivo de correos electrónicos a usuarios de la
 * aplicación
 */
class Controller_Email extends \Controller_App
{

    /**
     * Acción que permite enviar correos masivos a los usuarios de ciertos
     * grupos de la aplicación
     */
    public function grupos()
    {
        $Grupos = new Model_Grupos();
        $page_title = config('page.header.title');
        $this->set([
            'grupos' => $Grupos->getList(),
            'page_title' => $page_title,
        ]);
        if (isset($_POST['submit'])) {
            if (!isset($_POST['grupos']) || empty($_POST['asunto']) || empty($_POST['mensaje'])) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'Debe completar todos los campos del formulario', 'error'
                );
            } else {
                $emails = $Grupos->emails($_POST['grupos']);
                if(($key = array_search($this->Auth->User->email, $emails)) !== false) {
                    unset($emails[$key]);
                    sort($emails);
                }
                $n_emails = count($emails);
                if (!$n_emails) {
                    \sowerphp\core\Model_Datasource_Session::message(
                        'No hay destinatarios para el correo electrónico con los grupos seleccionados.', 'error'
                    );
                } else {
                    // preparar mensaje a enviar
                    $layout = $this->layout;
                    $this->layout = null;
                    $this->set (array(
                        'mensaje' => $_POST['mensaje'],
                        'n_emails' => $n_emails,
                        'grupos' => $Grupos->getGlosas($_POST['grupos']),
                        'de_nombre' => $this->Auth->User->nombre,
                        'de_email' => $this->Auth->User->email,
                    ));
                    $msg = $this->render('Email/grupos_email')->body();
                    $this->layout = $layout;
                    // agrupar
                    if ($_POST['agrupar']) {
                        $grupo = -1;
                        $destinatarios = [];
                        for ($i=0; $i<$n_emails; $i++) {
                            if ($i % $_POST['agrupar'] == 0) {
                                $destinatarios[++$grupo] = [];
                            }
                            $destinatarios[$grupo][] = $emails[$i];
                        }
                    } else {
                        $destinatarios = [$emails];
                    }
                    // enviar email
                    $primero = true;
                    foreach ($destinatarios as $correos) {
                        $email = new \sowerphp\core\Network_Email();
                        $email->from($this->Auth->User->email, $this->Auth->User->nombre);
                        $email->replyTo($this->Auth->User->email, $this->Auth->User->nombre);
                        if ($primero) {
                            $email->to($this->Auth->User->email);
                            $primero = false;
                        }
                        if ($_POST['enviar_como'] == 'cc') {
                            $email->cc($correos);
                        } else {
                            $email->bcc($correos);
                        }
                        $email->subject('['.$page_title.'] '.$_POST['asunto']);
                        // adjuntar archivos si se pasaron
                        $n_adjuntos = !empty($_FILES['adjuntos']) ? count($_FILES['adjuntos']['name']) : 0;
                        for ($i=0; $i<$n_adjuntos; $i++) {
                            if (!$_FILES['adjuntos']['error'][$i]) {
                                $email->attach([
                                    'tmp_name' => $_FILES['adjuntos']['tmp_name'][$i],
                                    'name' => $_FILES['adjuntos']['name'][$i],
                                    'type' => $_FILES['adjuntos']['type'][$i],
                                ]);
                            }
                        }
                        // enviar archivo
                        $status = $email->send($msg);
                        if ($status!==true) {
                            break;
                        }
                    }
                    if ($status === true) {
                        \sowerphp\core\Model_Datasource_Session::message(
                            'Mensaje envíado a '.num($n_emails).' usuarios.', 'ok'
                        );
                    } else {
                        \sowerphp\core\Model_Datasource_Session::message(
                            'Ha ocurrido un error al intentar enviar su mensaje, por favor intente nuevamente.<br /><em>'.$status['message'].'</em>', 'error'
                        );
                    }
                    $this->redirect($this->request->request);
                }
            }
        }
    }

}
