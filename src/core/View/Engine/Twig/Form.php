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

use \Twig\TwigFunction;
use \Twig\Markup;
use \Twig\Error\Error as TwigException;
use \sowerphp\core\Facade_Session_Message as SessionMessage;

/**
 * Extensión para el renderizado de formularios en una plantilla twig.
 */
class View_Engine_Twig_Form extends \Twig\Extension\AbstractExtension
{

    /**
     * Mensaje de error por defecto que se renderizará cuando el formulario
     * tenga errores.
     *
     * @var string
     */
    protected $defaultErrorsMessage = 'El formulario enviado contiene errores. Por favor, revisa los campos del formulario y corrige los errores antes de continuar.';

    /**
     * Codificación de caracteres para los renderizados devueltos por las
     * funciones de la extensión.
     *
     * Se utiliza en el objeto \Twig\Markup que se retorna en cada función.
     *
     * @var string
     */
    protected $charset = 'UTF-8';

    /**
     * Registro de los campos que ya han sido renderizados.
     *
     * @var array
     */
    protected $renderedFields = [];

    /**
     * Entrega las funciones de la extensión.
     *
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            // Funciones estándares.
            new TwigFunction('form_start', [$this, 'function_form_start']),
            new TwigFunction('form_end', [$this, 'function_form_end']),
            new TwigFunction('form_row', [$this, 'function_form_row']),
            new TwigFunction('form_label', [$this, 'function_form_label']),
            new TwigFunction('form_widget', [$this, 'function_form_widget']),
            new TwigFunction('form_errors', [$this, 'function_form_errors']),
            new TwigFunction('form_help', [$this, 'function_form_help']),
            new TwigFunction('form_rest', [$this, 'function_form_rest']),
            new TwigFunction('form_enctype', [$this, 'function_form_enctype']),
            new TwigFunction('form_is_submitted', [$this, 'function_form_is_submitted']),
            new TwigFunction('form_is_valid', [$this, 'function_form_is_valid']),
            // Funciones extras.
            new TwigFunction('form_captcha', [$this, 'function_form_captcha']),
            new TwigFunction('form_csrf', [$this, 'function_form_csrf']),
            new TwigFunction('form_submit', [$this, 'function_form_submit']),
            new TwigFunction('form_errors_message', [$this, 'function_form_errors_message']),
        ];
    }

    /**
     * Abre la etiqueta <form> y establece los atributos necesarios.
     *
     * @param object|array $form El formulario completo.
     * @param array $options Opciones adicionales para personalizar el
     * formulario. Algunas opciones son:
     *   - 'attr' (array): Atributos HTML para el elemento <form>.
     *      Ejemplo: ['id' => 'my-form', 'class' => 'my-class'].
     *   - 'method' (string): Método HTTP para el formulario (por defecto es
     *      'POST'). Ejemplo: 'GET', 'POST'.
     *   - 'action' (string): URL a la que se envía el formulario.
     *     Ejemplo: '/submit-form'.
     *   - 'multipart' (bool): Si se establece en true, se añade el atributo
     *     'enctype="multipart/form-data"' al formulario.
     * @return \Twig\Markup Código HTML para iniciar el formulario.
     */
    public function function_form_start($form, array $options = []): Markup
    {
        // Si el form no existe error.
        if ($form === null) {
            throw new TwigException(__(
                'Se solicitó renderizar un formulario que no existe.'
            ));
        }
        // Reiniciar los campos renderizados.
        $this->renderedFields = [];
        // Definir atributos del tag <form>.
        $attributes = html_attributes(array_merge($form['attributes'] ?? [], [
            'action' => $options['action'] ?? $form['attributes']['action'] ?? null,
            'method' => $options['method'] ?? $form['attributes']['method'] ?? 'POST',
            'id' => $form['attributes']['id'] ?? null,
            'class' => $form['attributes']['class'] ?? null,
            'enctype' => ($options['multipart'] ?? null) === true
                ? 'multipart/form-data'
                : $this->function_form_enctype($form)
            ,
            'role' => $form['attributes']['role'] ?? 'form',
        ], $options['attr'] ?? []));
        // Generar el HTML del tag <form>.
        $html = sprintf('<form %s>', $attributes);
        // Entregar el tag <form> renderizado.
        return new Markup($html, $this->charset);
    }

    /**
     * Cierra la etiqueta <form>.
     *
     * @param object|array $form El formulario completo.
     * @param array $options Opciones adicionales para personalizar el cierre
     * del formulario. Algunas opciones son:
     *   - 'render_rest' (bool): Indica si se deben renderizar los campos no
     *     renderizados (por defecto es true). Ejemplo: false.
     * @return \Twig\Markup Código HTML para cerrar el formulario.
     */
    public function function_form_end($form, array $options = []): Markup
    {
        $render_rest = $options['render_rest'] ?? false;
        $html = '';
        if ($render_rest) {
            $html .= $this->function_form_rest($form);
        }
        $html .= '</form>';
        return new Markup($html, $this->charset);
    }

    /**
     * Renderiza una fila de formulario completa, incluyendo la etiqueta, el
     * campo de entrada y los errores.
     *
     * @param object|array $field El campo del formulario.
     * @param array $options Opciones adicionales para personalizar el
     * renderizado del campo. Algunas opciones son:
     *   - 'label' (string): Cambia el texto de la etiqueta del campo.
     *     Ejemplo: 'Nombre Completo'.
     *   - 'attr' (array): Atributos HTML para el campo de entrada.
     *     Ejemplo: ['class' => 'form-control', 'placeholder' => 'Enter your name'].
     *   - 'label_attr' (array): Atributos HTML para la etiqueta del campo.
     *     Ejemplo: ['class' => 'control-label'].
     *   - 'label_translation_parameters' (array): Parámetros de traducción
     *     para la etiqueta del campo. Ejemplo: ['%name%' => 'John'].
     *   - 'row_attr' (array): Atributos HTML para el contenedor del campo.
     *     Ejemplo: ['class' => 'form-group'].
     * @return \Twig\Markup Código HTML para la fila del campo del formulario.
     */
    public function function_form_row($field, array $options = []): Markup
    {
        // Si el field no existe error.
        if ($field === null) {
            throw new TwigException(__(
                'Se solicitó renderizar un campo de formulario que no existe.'
            ));
        }
        // Generar subcomponentes del renderizado del campo.
        $label = $this->function_form_label($field, [
            'label' => $options['label'] ?? null,
            'label_attr' => $options['label_attr'] ?? null,
            'label_translation_parameters' => $options['label_translation_parameters'] ?? null,
        ]);
        $widget = $this->function_form_widget($field, [
            'attr' => $options['attr'] ?? null,
        ]);
        $errors = $this->function_form_errors($field);
        $help = $this->function_form_help($field);
        // Generar el HTML del campo.
        $html = sprintf(
            '<div class="row mb-3 form-group'.(($field['required'] ?? null)?' required':'').'">
                <div class="col-sm-2">
                    %s
                </div>
                <div class="col-sm-10">
                    %s
                    %s
                    %s
                </div>
            </div>',
            $label,
            $widget,
            $errors ? sprintf('<div class="invalid-feedback d-block">%s</div>', $errors) : '',
            $help ? sprintf('<div class="form-text">%s</div>', $help) : ''
        );
        // Marcar el campo como renderizado.
        if (!empty($field['name'])) {
            $this->markFieldAsRendered($field['name']);
        }
        // Entregar el campo renderizado.
        return new Markup($html, $this->charset);
    }

    /**
     * Renderiza la etiqueta de un campo del formulario.
     *
     * @param object|array $field El campo del formulario.
     * @param array $options Opciones adicionales para personalizar la etiqueta.
     * Algunas opciones son:
     *   - 'label' (string): Cambia el texto de la etiqueta del campo.
     *     Ejemplo: 'Correo Electrónico'.
     *   - 'label_attr' (array): Atributos HTML para la etiqueta.
     *     Ejemplo: ['class' => 'control-label'].
     *   - 'label_translation_parameters' (array): Parámetros de traducción
     *     para la etiqueta del campo. Ejemplo: ['%email%' => 'example@example.com'].
     * @return \Twig\Markup Código HTML para la etiqueta del campo del formulario.
     */
    public function function_form_label($field, array $options = []): Markup
    {
        // Si el field no existe error.
        if ($field === null) {
            throw new TwigException(__(
                'Se solicitó renderizar un campo de formulario que no existe.'
            ));
        }
        // Obtener el nombre y la etiqueta del campo.
        $name = $field['name'];
        $label = $field['label'] ?? null;
        if ($label === null) {
            return new Markup('', $this->charset);
        }
        $id = $field['widget']['attributes']['id'] ?? $name . 'Field';
        // Atributos de la etiqueta.
        $attributes = html_attributes([
            'for' => $id,
            'class' => 'form-label'
        ]);
        // Generar el HTML de la etiqueta.
        $html = sprintf(
            '<label %s>%s</label>',
            $attributes,
            e($label)
        );
        // Entregar la etiqueta renderizada.
        return new Markup($html, $this->charset);
    }

    /**
     * Renderiza el widget (campo de entrada) de un campo del formulario.
     *
     * @param object|array $field El campo del formulario.
     * @param array $options Opciones adicionales para personalizar el widget.
     * Algunas opciones son:
     *   - 'attr' (array): Atributos HTML para el widget del campo.
     *     Ejemplo: ['class' => 'form-control', 'placeholder' => 'Enter your name'].
     * @return \Twig\Markup Código HTML para el widget del campo del formulario.
     */
    public function function_form_widget($field, array $options = []): Markup
    {
        // Si el field no existe error.
        if ($field === null) {
            throw new TwigException(__(
                'Se solicitó renderizar un campo de formulario que no existe.'
            ));
        }
        // Si el widget no es un objeto se crea como objeto.
        if (!is_object($field['widget'] ?? null)) {
            $field['widget'] = new View_Form_Widget(
                is_string($field['widget'] ?? [])
                    ? $field['widget']
                    : $field['widget']['name']
                        ?? $field['input_type']
                        ?? 'default'
                ,
                $field['widget']['value'] ?? null,
                $field['widget']['attributes'] ?? []
            );
        }
        // Generar el HTML del widget.
        $html = $field['widget']->render($options);
        // Marcar el campo como renderizado.
        if (!empty($field['name'])) {
            $this->markFieldAsRendered($field['name']);
        }
        // Entregar el widget renderizado.
        return new Markup($html, $this->charset);
    }

    /**
     * Renderiza los errores asociados a un campo.
     *
     * @param array $field La configuración del campo.
     * @return \Twig\Markup Código HTML para los errores del campo.
     */
    public function function_form_errors($field): Markup
    {
        // Si el field no existe error.
        if ($field === null) {
            throw new TwigException(__(
                'Se solicitó renderizar un campo de formulario que no existe.'
            ));
        }
        // Obtener los errores del campo.
        $errors = $field['error_messages'] ?? [];
        // Si no hay errores, devolver una cadena vacía.
        if (empty($errors)) {
            return new Markup('', $this->charset);
        }
        // Generar el HTML de los errores.
        $html = '<ul class="list-unstyled mb-0">';
        foreach ($errors as $error) {
            $html .= sprintf('<li><i class="fa-solid fa-exclamation-circle"></i> %s</li>', e($error));
        }
        $html .= '</ul>';
        // Entregar los errores renderizados.
        return new Markup($html, $this->charset);
    }

    /**
     * Renderiza el texto de ayuda asociado a un campo.
     *
     * @param array $field La configuración del campo.
     * @return \Twig\Markup Código HTML para el texto de ayuda del campo.
     */
    public function function_form_help($field): Markup
    {
        // Si el field no existe error.
        if ($field === null) {
            throw new TwigException(__(
                'Se solicitó renderizar un campo de formulario que no existe.'
            ));
        }
        // Obtener el texto de ayuda del campo.
        $helpText = $field['help_text'] ?? '';
        // Si no hay texto de ayuda, devolver una cadena vacía.
        if (empty($helpText)) {
            return new Markup('', $this->charset);
        }
        // Atributos del texto de ayuda.
        $attributes = html_attributes([
            'class' => 'form-text text-muted'
        ]);
        // Generar el HTML del texto de ayuda.
        $html = sprintf(
            '<div %s>%s</div>',
            $attributes,
            e($helpText)
        );
        // Entregar el texto de ayuda renderizado.
        return new Markup($html, $this->charset);
    }

    /**
     * Renderiza todos los campos no renderizados previamente en el formulario.
     *
     * @param object|array $form El formulario completo.
     * @return \Twig\Markup Código HTML para los campos restantes.
     */
    public function function_form_rest($form): Markup
    {
        // Generar el HTML de todos los campos que no han sido renderizados.
        $html = '';
        foreach ($form['fields'] as $field) {
            if (!in_array($field['name'], $this->renderedFields) && $field['editable']) {
                $html .= $this->function_form_row($field);
            }
        }
        // Entregar los campos pendientes de renderizar renderizados.
        return new Markup($html, $this->charset);
    }

    /**
     * Renderiza el atributo enctype para el formulario.
     *
     * @param object|array $form El formulario completo.
     * @return string El atributo enctype para el formulario.
     */
    public function function_form_enctype($form): string
    {
        foreach (($form['fields'] ?? []) as $field) {
            $type = $field['widget']['attributes']['type'] ?? null;
            if ($type == 'file') {
                return 'multipart/form-data';
            }
        }
        return $form['attributes']['enctype']
            ?? 'application/x-www-form-urlencoded'
        ;
    }

    /**
     * Verifica si el formulario ha sido enviado.
     *
     * @param object|array $form El formulario completo.
     * @return bool True si el formulario ha sido enviado, de lo contrario False.
     */
    public function function_form_is_submitted($form): bool
    {
        return $form['is_bound'];
    }

    /**
     * Verifica si el formulario es válido.
     *
     * @param object|array $form El formulario completo.
     * @return bool True si el formulario es válido, de lo contrario False.
     */
    public function function_form_is_valid($form): bool
    {
        // Iterar sobre los campos y verificar si tienen errores.
        foreach ($form['fields'] as $field) {
            // Si algún campo tiene errores, el formulario no es válido.
            if (!empty($field['error_messages'])) {
                return false;
            }
        }
        // Si no se encontraron errores, el formulario es válido.
        return true;
    }

    /**
     * Agrega los elementos al formulario para usar el captcha.
     *
     * @param object|array $form El formulario completo.
     * @return \Twig\Markup
     */
    public function function_form_captcha($form): Markup
    {
        $id = $form['attributes']['id'] ?? null;
        $html = app('captcha')->render($id);
        return new Markup($html, $this->charset);
    }

    /**
     * Agrega el Token CSRF al formulario.
     *
     * @param object|array $form El formulario completo.
     * @return \Twig\Markup
     */
    public function function_form_csrf($form): Markup
    {
        $html = sprintf(
            '<input type="hidden" name="csrf_token" value="%s" />',
            $form['csrf_token']
        );
        return new Markup($html, $this->charset);
    }

    /**
     * Renderiza el botón de envío del formulario.
     *
     * @param string $label El texto del botón.
     * @param array $attributes Atributos adicionales para el botón.
     * @return \Twig\Markup Código HTML para el botón de envío.
     */
    public function function_form_submit(
        string $label = 'Enviar',
        array $attributes = []
    ): Markup
    {
        // Definir atributos del botón.
        $attributes = html_attributes(array_merge([
            'type' => 'submit',
            'class' => 'btn btn-primary'
        ], $attributes));
        // Generar el HTML del botón.
        $html = sprintf(
            '<div class="row mb-3 form-group">
                <div class="offset-sm-2 col-sm-10">
                    <button %s>%s</button>
                </div>
            </div>',
            $attributes,
            e($label)
        );
        // Entregar el botón renderizado.
        return new \Twig\Markup($html, 'UTF-8');
    }

    /**
     * Función que genera el HTML de un mensaje de error cuando el formulario
     * contiene errores.
     *
     * @param string|null $message Mensaje a mostrar o se usa uno por defecto.
     * @return Markup
     */
    public function function_form_errors_message(?string $message = null): Markup
    {
        $html = SessionMessage::render([
            'type' => 'error',
            'text' => $message ?? $this->defaultErrorsMessage,
        ]);
        return new \Twig\Markup($html, 'UTF-8');
    }

    /**
     * Marcar campos como renderizados.
     *
     * @param string $field Nombre del campo.
     * @return void
     */
    protected function markFieldAsRendered(string $field): void
    {
        if (!isset($this->renderedFields)) {
            $this->renderedFields = [];
        }
        if (!in_array($field, $this->renderedFields)) {
            $this->renderedFields[] = $field;
        }
    }

}
