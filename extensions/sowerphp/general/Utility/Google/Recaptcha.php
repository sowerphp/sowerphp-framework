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

namespace sowerphp\general;

/**
 * Clase para iteractuar con Google reCaptcha v3
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2022-08-14
 */
class Utility_Google_Recaptcha
{

    private static $jsAlreadyIncluded = false;

    /**
     * Método que genera genera el código javascript que va en el formulario
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2022-08-14
     */
    public static function form($form, $action = null)
    {
        $captcha_public_key = \sowerphp\core\Configure::read('recaptcha.public_key');
        if (empty($captcha_public_key)) {
            return '';
        }
        if ($action === null) {
            $action = $form.'_action';
        }
        $buffer = '';
        if (!self::$jsAlreadyIncluded) {
            $buffer .= '<script src="https://www.google.com/recaptcha/api.js?render='.$captcha_public_key.'"></script>';
            self::$jsAlreadyIncluded = true;
        }
        $buffer .= '<script> $(function() {'."\n";
        $buffer .= '    $("#suscribirForm").submit(function(event) {'."\n";
        $buffer .= '        event.preventDefault();'."\n";
        $buffer .= '        grecaptcha.ready(function() {'."\n";
        $buffer .= '            grecaptcha.execute("'.$captcha_public_key.'", {action: "'.$action.'"}).then(function(token) {'."\n";
        $buffer .= '                $("#suscribirForm").prepend(\'<input type="hidden" name="recaptcha-token" value="\' + token + \'">\');'."\n";
        $buffer .= '                $("#suscribirForm").prepend(\'<input type="hidden" name="recaptcha-action" value="'.$action.'">\');'."\n";
        $buffer .= '                $("#suscribirForm").unbind("submit").submit();'."\n";
        $buffer .= '            });'."\n";
        $buffer .= '        });'."\n";
        $buffer .= '    });'."\n";
        $buffer .= '}); </script>'."\n";
        return $buffer;
    }

    /**
     * Método que valida que el captcha esté ok en el backend
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2022-08-14
     */
    public static function check()
    {
        $captcha_private_key = \sowerphp\core\Configure::read('recaptcha.private_key');
        if (!$captcha_private_key) {
            return true;
        }
        $recaptchaToken = !empty($_POST['recaptcha-token']) ? $_POST['recaptcha-token'] : '';
        $remoteIp = $_SERVER['REMOTE_ADDR'];
        $recaptcha = new \ReCaptcha\ReCaptcha($captcha_private_key);
        $resp = $recaptcha->verify($recaptchaToken, $remoteIp);
        if (!$resp->isSuccess()) {
            $errors = $resp->getErrorCodes();
            throw new \Exception(implode(' / ', $errors));
        }
    }

}
