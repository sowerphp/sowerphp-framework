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

namespace sowerphp\general;

use Symfony\Component\Mime\Address;
use \sowerphp\core\Network_Request as Request;
use \sowerphp\autoload\Controller;

/**
 * Controlador para página de contacto.
 */
class Controller_Contacto extends Controller
{

    /**
     * Inicializar controlador.
     */
    public function boot(): void
    {
        app('auth')->allowActionsWithoutLogin('index', 'send');
        parent::boot();
    }

    /**
     * Método que mostrará el formulario de contacto.
     */
    public function index(Request $request)
    {
        // Si no hay datos para el envió del correo electrónico no se permite
        // la carga de la página de contacto.
        if (config('mail.default') === null) {
            return redirect('/')
                ->withError(
                    __('La página de contacto no se encuentra disponible.')
                );
        }
        $form = View_Form_Contacto::create();
        return $this->render(null, [
            'form' => $form,
        ]);
    }

    /**
     * Método que procesará el formulario de contacto.
     */
    public function send(Request $request)
    {
        // Crear formulario con los posibles datos enviados.
        $form = View_Form_Contacto::create([
            'data' => $request->input(),
            'files' => $request->file(),
        ]);
        // Validar captcha del formulario.
        try {
            app('captcha')->check($request);
        } catch (\Exception $e) {
            return redirect()->back(422)->withInput()->withError(__(
                'Falló la validación del captcha: %s',
                $e->getMessage()
            ));
        }
        // Validar formmulario.
        if (!$form->is_valid()) {
            return redirect()
                ->back(422)
                ->withInput()
                ->withErrors($form->errors, $form->errors_key)
            ;
        }
        // Armar correo electrónico que se enviará.
        $data = $form->cleaned_data;
        $from = new Address($data['email'], $data['name']);
        $text = sprintf(
            "%s\n\n-- \n%s\n%s\n",
            $data['message'],
            $data['name'],
            $data['email']
        );
        //$html = '<p>' . str_replace("\n", '</p><p>', $text) . '</p>';
        $email = (new \sowerphp\core\Network_Mail_Email())
            ->from($from)
            ->to($from)
            ->replyTo($from)
            ->subject(__('Contacto desde %s #%d', url(), date('YmdHis')))
            ->text($text)
            //->html($html)
        ;
        // Enviar correo electrónico.
        try {
            app('mail')->send($email);
            return redirect('/contacto')->withSuccess(__(
                'Su mensaje ha sido enviado, se responderá a la brevedad.'
            ));
        } catch (\Exception $e) {
            return redirect()
                ->back(422)
                ->withInput()
                ->withError(__(
                    'Ha ocurrido un error al enviar su mensaje, por favor intente nuevamente.<br /><br /><em>%s</em>',
                    $e->getMessage()
                ))
            ;
        }
    }

}
