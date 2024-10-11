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

namespace sowerphp\core;

use ReCaptcha\ReCaptcha;
use sowerphp\core\Network_Request as Request;

/**
 * Servicio de Captcha.
 *
 *
 */
class Service_Http_Captcha implements Interface_Service
{

    protected $jsAlreadyIncluded = false;

    /**
     * Registra el servicio de captcha.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Inicializa el servicio de captcha.
     *
     * @return void
     */
    public function boot(): void
    {
    }

    /**
     * Finaliza el servicio de captcha.
     *
     * @return void
     */
    public function terminate(): void
    {
    }

    /**
     * Método que valida que el captcha esté ok en el backend.
     */
    public function check(Request $request): bool
    {
        $captcha_private_key = config('services.recaptcha.private_key');
        if (!$captcha_private_key) {
            return true;
        }
        $recaptchaToken = $request->get('recaptcha-token', '');
        $remoteIp = $request->fromIp();
        $recaptcha = new ReCaptcha($captcha_private_key);
        $resp = $recaptcha->verify($recaptchaToken, $remoteIp);
        if (!$resp->isSuccess()) {
            $errors = $resp->getErrorCodes();
            throw new \Exception(implode(' / ', $errors));
        }
        return true;
    }

    /**
     * Método que genera genera el código que va en el formulario.
     */
    public function render(string $form, ?string $action = null): string
    {
        $captcha_public_key = config('services.recaptcha.public_key');
        if (empty($captcha_public_key)) {
            return '';
        }
        if ($action === null) {
            $action = $form . '_action';
        }
        $buffer = $this->renderJs();
        $buffer .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            google_recaptcha_v3_form("' . $form . '", "' . $action . '");
        });
        </script>' . "\n";
        return $buffer;
    }

    /**
     * Método que genera genera el código javascript general a todos los formularios
     */
    protected function renderJs(): string
    {
        if ($this->jsAlreadyIncluded) {
            return '';
        }
        $this->jsAlreadyIncluded = true;
        $captcha_public_key = config('services.recaptcha.public_key');
        if (empty($captcha_public_key)) {
            return '';
        }
        $buffer = '<script src="https://www.google.com/recaptcha/api.js?render=' . $captcha_public_key . '"></script>';
        $buffer .= '<script>
        function google_recaptcha_v3_form(formId, action) {
            var form = document.getElementById(formId);
            var onsubmit = form.onsubmit;

            if (onsubmit !== null) {
                form.removeAttribute("onsubmit");
            }

            form.addEventListener("submit", function(event) {
                event.preventDefault();

                var form_onsubmit = true;
                if (onsubmit !== null) {
                    form_onsubmit = onsubmit.call(form, event);
                }

                if (form_onsubmit) {
                    grecaptcha.ready(function() {
                        grecaptcha.execute("' . $captcha_public_key . '", {action: action}).then(function(token) {
                            var recaptchaTokenInput = document.createElement("input");
                            recaptchaTokenInput.type = "hidden";
                            recaptchaTokenInput.name = "recaptcha-token";
                            recaptchaTokenInput.value = token;

                            var recaptchaActionInput = document.createElement("input");
                            recaptchaActionInput.type = "hidden";
                            recaptchaActionInput.name = "recaptcha-action";
                            recaptchaActionInput.value = action;

                            form.prepend(recaptchaTokenInput);
                            form.prepend(recaptchaActionInput);

                            form.removeEventListener("submit", arguments.callee);
                            form.submit();
                        });
                    });
                }
            });
        }
        </script>' . "\n";
        return $buffer;
    }

}
