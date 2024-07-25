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

namespace sowerphp\general;

use \sowerphp\core\Facade_Session_Message as SessionMessage;

/**
 * Controlador para página de contacto.
 */
class Controller_Contacto extends \sowerphp\autoload\Controller
{

    /**
     * Inicializar controlador.
     */
    public function boot(): void
    {
        app('auth')->allowActionsWithoutLogin('index');
        parent::boot();
    }

    /**
     * Método que desplegará y procesará el formulario de contacto
     */
    public function index()
    {
        // Si no hay datos para el envió del correo electrónico no
        // permirir cargar página de contacto
        if (config('mail.default') === null) {
            return redirect('/')
                ->withError(
                    __('La página de contacto no se encuentra disponible.')
                );
        }
        // Si se envió el formulario se procesa.
        if (isset($_POST['submit'])) {
            // Validar captcha.
            try {
                \sowerphp\general\Utility_Google_Recaptcha::check();
            } catch (\Exception $e) {
                SessionMessage::error(__(
                    'Falló validación captcha: %s',
                    $e->getMessage()
                ));
                return;
            }
            // Enviar email.
            $_POST['nombre'] = trim(strip_tags($_POST['nombre']));
            $_POST['correo'] = trim(strip_tags($_POST['correo']));
            $_POST['mensaje'] = trim(strip_tags($_POST['mensaje']));
            if (
                !empty($_POST['nombre'])
                && !empty($_POST['correo'])
                && !empty($_POST['mensaje'])
            ) {
                $email = new \sowerphp\core\Network_Email();
                $email->replyTo($_POST['correo'], $_POST['nombre']);
                $email->to(config('mail.to.address'));
                $email->subject(
                    !empty($_POST['asunto'])
                    ? trim(strip_tags($_POST['asunto']))
                    : __('Contacto desde %s #%d', url(), date('YmdHis'))
                );
                $msg = $_POST['mensaje']."\n\n".'-- '."\n".$_POST['nombre']."\n".$_POST['correo'];
                $status = $email->send($msg);
                if ($status === true) {
                    return redirect('/contacto')->withSuccess(__(
                        'Su mensaje ha sido enviado, se responderá a la brevedad.'
                    ));
                } else {
                    SessionMessage::error(__(
                        'Ha ocurrido un error al enviar su mensaje, por favor intente nuevamente.<br /><em>%s</em>', $status['message']
                    ));
                }
            } else {
                SessionMessage::error(__(
                    'Por favor, completar todos los campos del formulario.'
                ));
            }
        }
    }

}
