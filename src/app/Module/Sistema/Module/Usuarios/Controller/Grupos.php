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

namespace sowerphp\app\Sistema\Usuarios;

use \sowerphp\core\Network_Request as Request;
use \sowerphp\core\Facade_Session_Message as SessionMessage;

/**
 * Clase para el controlador asociado a la tabla grupo de la base de
 * datos
 * Comentario de la tabla: Grupos de la aplicación
 * Esta clase permite controlar las acciones entre el modelo y vista para la
 * tabla grupo
 */
class Controller_Grupos extends \sowerphp\autoload\Controller_Model
{

    /**
     * Acción que permite enviar correos masivos a los usuarios de ciertos
     * grupos de la aplicación.
     */
    public function email(Request $request)
    {
        $user = $request->user();
        $page_title = config('app.name');
        $grupos = database()
            ->query()
            ->from('grupo')
            ->setMapClass(Model_Grupo::class)
            ->orderBy('grupo', 'asc')
            ->get()
            ->map(function($grupo) {
                return [$grupo->id, $grupo->grupo, $grupo->usuarios()->count()];
            })
            ->all()
        ;
        $this->set([
            'grupos' => $grupos,
            'page_title' => $page_title,
        ]);
        if (!empty($_POST)) {
            if (
                !isset($_POST['grupos'])
                || empty($_POST['asunto'])
                || empty($_POST['mensaje'])
            ) {
                SessionMessage::error(
                    __('Debe completar todos los campos del formulario.')
                );
            } else {
                $Grupos = new Model_Grupos();
                $emails = $Grupos->emails($_POST['grupos']);
                if(($key = array_search($user->email, $emails)) !== false) {
                    unset($emails[$key]);
                    sort($emails);
                }
                $n_emails = count($emails);
                if (!$n_emails) {
                    SessionMessage::error(
                        __('No hay destinatarios para el correo electrónico con los grupos seleccionados.')
                    );
                } else {
                    // preparar mensaje a enviar
                    $msg = view()->render('Grupos/email_mensaje', [
                        'mensaje' => $_POST['mensaje'],
                        'n_emails' => $n_emails,
                        'grupos' => $Grupos->getGlosas($_POST['grupos']),
                        'de_nombre' => $user->nombre,
                        'de_email' => $user->email,
                    ]);
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
                        $email->from($user->email, $user->nombre);
                        $email->replyTo($user->email, $user->nombre);
                        if ($primero) {
                            $email->to($user->email);
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
                        return redirect($request->getRequestUriDecoded())
                            ->withSuccess(
                                __('Mensaje envíado a %(num_emails)s usuarios.',
                                    [
                                        'num_emails' => num($n_emails)
                                    ]
                                )
                            );
                    } else {
                        return redirect($request->getRequestUriDecoded())
                            ->withError(
                                __('Ha ocurrido un error al intentar enviar su mensaje, por favor intente nuevamente.<br /><em>%(status_message)s</em>',
                                    [
                                        'status_message' => $status['message']
                                    ]
                                )
                            );
                    }
                }
            }
        }
    }

}
