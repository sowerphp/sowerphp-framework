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

use Illuminate\Support\Str;
use sowerphp\core\Facade_Session_Message as SessionMessage;
use Twig\Error\Error as TwigException;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

/**
 * Extensión para el renderizado de formularios en una plantilla twig.
 */
final class View_Engine_Twig_Form extends AbstractExtension
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
     * Se utiliza en el objeto Markup que se retorna en cada función.
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
            new TwigFunction('form_errors_global', [$this, 'function_form_errors_global']),
            new TwigFunction('form_fields', [$this, 'function_form_fields']),
            new TwigFunction('form_layout', [$this, 'function_form_layout']),
        ];
    }

    /**
     * Abre la etiqueta <form> y establece los atributos necesarios.
     *
     * @param object|array $form El formulario completo.
     * @param array $options Opciones adicionales para personalizar el
     * formulario. Algunas opciones son:
     *   - `attr` (array): Atributos HTML para el elemento `<form>`.
     *      Ejemplo: `['id' => 'my-form', 'class' => 'my-class']`.
     *   - `method` (string): Método HTTP para el formulario (por defecto es
     *      `POST`). Ejemplo: `GET`, `POST`.
     *   - `action` (string): URL a la que se envía el formulario.
     *     Ejemplo: `/submit-form`.
     *   - `multipart` (bool): Si se establece en true, se añade el atributo
     *     `enctype="multipart/form-data"` al formulario.
     * @return Markup Código HTML para iniciar el formulario.
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
        $attributes = html_attributes(array_merge(
            $form['attributes'] ?? [],
            [
                'action' => $options['action']
                    ?? $form['attributes']['action']
                    ?? null
                ,
                'method' => $options['method']
                    ?? $form['attributes']['method']
                    ?? 'POST'
                ,
                'id' => $form['attributes']['id'] ?? null,
                'class' => $form['attributes']['class'] ?? null,
                'enctype' => ($options['multipart'] ?? null) === true
                    ? 'multipart/form-data'
                    : $this->function_form_enctype($form)
                ,
                'role' => $form['attributes']['role'] ?? 'form',
            ],
            $options['attr'] ?? []
        ));

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
     *   - `render_rest` (bool): Indica si se deben renderizar los campos no
     *     renderizados (por defecto es `true`). Ejemplo: `false`.
     * @return Markup Código HTML para cerrar el formulario.
     */
    public function function_form_end($form, array $options = []): Markup
    {
        $html = '';
        $render_rest = $options['render_rest'] ?? false;

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
     *   - `label` (string): Cambia el texto de la etiqueta del campo.
     *     Ejemplo: `Nombre Completo`.
     *   - `attr` (array): Atributos HTML para el campo de entrada.
     *     Ejemplo: `['class' => 'form-control', 'placeholder' => 'Enter your name']`.
     *   - `label_attr` (array): Atributos HTML para la etiqueta del campo.
     *     Ejemplo: `['class' => 'control-label']`.
     *   - `label_translation_parameters` (array): Parámetros de traducción
     *     para la etiqueta del campo. Ejemplo: `['%name%' => 'John']`.
     *   - `row_attr` (array): Atributos HTML para el contenedor del campo.
     *     Ejemplo: `['class' => 'form-group']`.
     * @return Markup Código HTML para la fila del campo del formulario.
     */
    public function function_form_row($field, array $options = []): Markup
    {
        // Crear el campo $field como objeto si fue pasado como arreglo.
        $field = $this->createField($field);

        // Renderizar el widget del campo.
        $widget = $this->function_form_widget($field, [
            'attr' => $options['attr'] ?? null,
        ]);

        // Si el campo es oculto el HTML del campo es solo el widget.
        if ($field['widget']['name'] === 'hidden') {
            $html = $widget;
        }

        // Generar el resto de subcomponente y el HTML cuando no es oculto.
        else {
            // Generar subcomponentes del renderizado del campo.
            $label = $this->function_form_label($field, [
                'label' => $options['label'] ?? null,
                'label_attr' => $options['label_attr'] ?? null,
                'label_translation_parameters' =>
                    $options['label_translation_parameters'] ?? null
                ,
            ]);
            $errors = $this->function_form_errors($field);
            $help = $this->function_form_help($field);

            // Generar el HTML del campo.
            $required = ($field['required'] ?? null) ? ' required' : '';
            $html = sprintf(
                '<div class="row mb-3 form-group'.$required.'">
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
                $errors
                    ? sprintf(
                        '<div class="invalid-feedback d-block">%s</div>',
                        $errors
                    )
                    : ''
                ,
                $help ? sprintf('<div class="form-text">%s</div>', $help) : ''
            );
        }

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
     *   - `label` (string): Cambia el texto de la etiqueta del campo.
     *     Ejemplo: 'Correo Electrónico'.
     *   - `label_attr` (array): Atributos HTML para la etiqueta.
     *     Ejemplo: `['class' => 'control-label']`.
     *   - `label_translation_parameters` (array): Parámetros de traducción para
     *     la etiqueta del campo. Ejemplo: `['%email%' => 'example@example.com']`.
     *   - `required_label` (bool|string): para indicar si se debe mostrar si el
     *     campo es obligatorio (por defecto `true`). Si es un string, será el
     *     texto que se incluirá para indicar que el campo es obligatorio.
     * @return Markup Código HTML para la etiqueta del campo del formulario.
     */
    public function function_form_label($field, array $options = []): Markup
    {
        // Opciones por defecto de la etiqueta.
        $options = array_merge([
            'required_label' => true,
        ], $options);

        // Si el field no existe error.
        $field = $this->createField($field);

        // Obtener el nombre, etiqueta e ID del campo.
        $name = $field['name'];
        $label = $field['label'] ?? null;
        if (empty($label)) {
            return new Markup('', $this->charset);
        }
        $id = $field['widget']['attributes']['id'] ?? $name . 'Field';

        // Atributos de la etiqueta.
        $attributes = html_attributes(array_merge([
            'for' => $id,
            'class' => 'form-label',
        ], $options['label_attr'] ?? []));

        // Determinar la marca de label si es requerido el campo.
        $required_label = '';
        if ($options['required_label'] && ($field['required'] ?? false)) {
            if (is_string($options['required_label'])) {
                $required_label = ' ' . $options['required_label'];
            } else {
                $required_label = ' <sup><i class="fa-solid fa-asterisk small text-danger"></i></sup>';
            }
        }

        // Generar el HTML de la etiqueta.
        $html = sprintf(
            '<label %s>%s%s</label>',
            $attributes,
            e($label),
            $required_label
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
     *   - `attr` (array): Atributos HTML para el widget del campo.
     *     Ejemplo: `['class' => 'form-control', 'placeholder' => 'Enter your name']`.
     * @return Markup Código HTML para el widget del campo del formulario.
     */
    public function function_form_widget($field, array $options = []): Markup
    {
        // Si el field no existe error.
        $field = $this->createField($field);

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
     * @param object|array $field La configuración del campo.
     * @return Markup Código HTML para los errores del campo.
     */
    public function function_form_errors($field): Markup
    {
        // Si el field no existe error.
        $field = $this->createField($field);

        // Obtener los errores del campo.
        $errors = $field['error_messages'] ?? [];

        // Si no hay errores, devolver una cadena vacía.
        if (empty($errors)) {
            return new Markup('', $this->charset);
        }

        // Generar el HTML de los errores.
        $html = '<ul class="list-unstyled mb-0">';
        foreach ($errors as $error) {
            $html .= sprintf(
                '<li><i class="fa-solid fa-exclamation-circle"></i> %s</li>',
                e($error)
            );
        }
        $html .= '</ul>';

        // Entregar los errores renderizados.
        return new Markup($html, $this->charset);
    }

    /**
     * Renderiza el texto de ayuda asociado a un campo.
     *
     * @param object|array $field La configuración del campo.
     * @return Markup Código HTML para el texto de ayuda del campo.
     */
    public function function_form_help($field): Markup
    {
        // Si el field no existe error.
        $field = $this->createField($field);

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
     * @param array $options Opciones adicionales para personalizar el
     * renderizado del campo mediante form_row().
     * @return Markup Código HTML para los campos restantes.
     */
    public function function_form_rest($form, array $options = []): Markup
    {
        // Generar el HTML de todos los campos que no han sido renderizados.
        $html = '';
        foreach (($form['fields'] ?? []) as $field) {
            if (
                ($field['editable'] ?? true)
                && !in_array($field['name'], $this->renderedFields)
            ) {
                $html .= $this->function_form_row($field, $options);
            }
        }

        // Entregar los campos pendientes de renderizar renderizados.
        return new Markup($html, $this->charset);
    }

    /**
     * Renderiza el atributo `enctype` para el formulario.
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
     * @return bool `true` si el formulario ha sido enviado.
     */
    public function function_form_is_submitted($form): bool
    {
        return $form['is_bound'];
    }

    /**
     * Verifica si el formulario es válido.
     *
     * @param object|array $form El formulario completo.
     * @return bool `true` si el formulario es válido.
     */
    public function function_form_is_valid($form): bool
    {
        // Iterar sobre los campos y verificar si tienen errores.
        foreach (($form['fields'] ?? []) as $field) {
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
     * @return Markup
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
     * @return Markup
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
     * @return Markup Código HTML para el botón de envío.
     */
    public function function_form_submit(
        string $label = 'Enviar',
        array $attributes = []
    ): Markup
    {
        // Definir atributos del botón.
        $attributes = html_attributes(array_merge([
            'type' => 'submit',
            'class' => 'btn btn-primary w-100'
        ], $attributes));

        // Generar el HTML del botón.
        $html = sprintf(
            '<button %s>%s</button>',
            $attributes,
            e($label)
        );

        // Entregar el botón renderizado.
        return new Markup($html, 'UTF-8');
    }

    /**
     * Genera el HTML de un mensaje de error (en formato alerta) cuando el
     * formulario contiene campos con errores.
     *
     * @param object|array $form El formulario completo.
     * @param string|null $message Mensaje a mostrar o se usa uno por defecto.
     * @return Markup
     */
    public function function_form_errors_global($form, ?string $message = null): Markup
    {
        // Mensaje general si hay errores en el formulario.
        $html = SessionMessage::render([
            'type' => 'error',
            'text' => $message ?? $this->defaultErrorsMessage,
        ]);

        // Agregar errores de campos no renderizados.
        // NOTE: Esto normalmente debería ser un error de programación.
        // Se muestran para que al programar se sepa que existen y se puedan
        // controlar o corregir estos campos no renderizados con errores.
        // Esto solo funcionará correctamente si la función es llamada en la
        // plantilla después de renderizar los campos del formulario.
        foreach (($form['fields'] ?? []) as $field) {
            if (
                !empty($field['error_messages'])
                && !in_array($field['name'], $this->renderedFields)
            ) {
                foreach ($field['error_messages'] as $error) {
                    $html .= SessionMessage::render([
                        'type' => 'error',
                        'text' => $error,
                    ]);
                }
            }
        }

        // Entregar HTML de los errores globales.
        return new Markup($html, 'UTF-8');
    }

    /**
     * Renderiza todos los campos del formulario. Por defecto con el layout
     * usando "form_row()", pero se puede personalizar el layout.
     *
     * @param object|array $form El formulario completo.
     * @param array $options Opciones adicionales para personalizar el
     * formulario. Algunas opciones son:
     *   - `layout` (string|array): layout que se debe renderizar, por defecto
     *     se usa el string `row`, también se puede indicar un arreglo con la
     *     configuración del layout y campos.
     * Si el formato es el por defecto `row` $options puede contener cualquier
     * opción disponible en form_row().
     * @return Markup
     */
    public function function_form_fields($form, array $options = []): Markup
    {
        $layout = $options['layout'] ?? $form['layout'] ?? 'row';

        // Se especificó el nombre del layout que se debe utilizar para el
        // renderizado.
        if (is_string($layout)) {
            // Renderizado estándar con form_row() a través de form_rest().
            if ($layout == 'row') {
                $html = $this->function_form_rest($form, $options);
            }

            // Error porque no se soporta otro layout como string.
            else {
                throw new TwigException(__(
                    'Layout de formulario %s no soportado.',
                    $layout
                ));
            }
        }

        // Si el layout es un arreglo se genera según la configuración del
        // arreglo. El cual debe contener la estructura de un layout de
        // formulario.
        else if (is_array($layout)) {
            $html = $this->function_form_layout($form, $layout);
        }

        // Si layout es otro tipo de dato, error pues no está soportado.
        else {
            throw new TwigException(__('Layout de formulario inválido.'));
        }

        // Entregar HTML de los errores globales.
        return new Markup($html, 'UTF-8');
    }

    /**
     * Renderiza los campos del formulario mediante un layout.
     *
     * @param object|array $form El formulario completo.
     * @param array $layout Arreglo con la configuración del layout y campos.
     * @return Markup
     */
    public function function_form_layout($form, array $layout = []): Markup
    {
        $html = '';

        // Si hay pestañas, se genera un contenedor de pestañas.
        if (!empty($layout['tabs'])) {
            $html .= '<ul class="nav nav-tabs" role="tablist">';
            $tabContent = '<div class="tab-content pt-4">';

            // Iterar cada pestaña para incluir enlace y contenido de cada una.
            foreach ($layout['tabs'] as $index => $tab) {
                // Atributos generales de la pestaña (incluyendo su ícono).
                $id = $tab['id'] ?? Str::slug($tab['title']);
                $tabId = $id . '-tab';
                $activeClass = $index === 0 ? ' active' : '';
                $iconHtml = !empty($tab['icon'])
                    ? sprintf('<i class="%s fa-fw me-2"></i>', e($tab['icon']))
                    : ''
                ;

                // Generar enlace de la pestaña.
                $html .= sprintf(
                    '<li class="nav-item"><a href="#%s" class="nav-link%s" data-bs-toggle="tab" id="%s" role="tab" aria-controls="%s">%s%s</a></li>',
                    $id,
                    $activeClass,
                    $tabId,
                    $id,
                    $iconHtml,
                    e($tab['title'])
                );

                // Generar el contenido de pestaña.
                $tabContent .= sprintf(
                    '<div class="tab-pane%s" id="%s" role="tabpanel" aria-labelledby="%s">',
                    $activeClass,
                    $id,
                    $tabId
                );
                foreach ($tab['sections'] as $index => $section) {
                    $section['index'] = $index + 1;
                    $section['tab'] = [
                        'id' => $id,
                    ];
                    $tabContent .= $this->renderLayoutSection($form, $section);
                }
                $tabContent .= '</div>'; // Cerrar tab-pane.
            }
            $html .= '</ul>';
            $html .= $tabContent . '</div>'; // Cerrar tab-content.
        }

        // Si no hay pestañas, se renderizan solo secciones.
        else {
            foreach ($layout as $index => $section) {
                $section['index'] = $index + 1;
                $html .= $this->renderLayoutSection($form, $section);
            }
        }

        // Script para cambiar el ícono de "+" a "-" al colapsar.
        $html .= '
        <script>
        $(function() { __.tabs(); });
        document.querySelectorAll("div.card-header .toggle-link").forEach(function(link) {
            const target = document.querySelector(link.getAttribute("data-bs-target"));
            const icon = link.querySelector("i.toggle-icon");
            target.addEventListener("shown.bs.collapse", function() {
                icon.classList.remove("fa-caret-down");
                icon.classList.add("fa-caret-up");
            });
            target.addEventListener("hidden.bs.collapse", function() {
                icon.classList.remove("fa-caret-up");
                icon.classList.add("fa-caret-down");
            });
        });
        </script>';

        // Entregar el HTML renderizado para al vista.
        return new Markup($html, $this->charset);
    }

    /**
     * Renderiza una sección del layout del formulario.
     *
     * @param object|array $form El formulario completo.
     * @param array $section La sección del formulario que se debe renderizar.
     * @return string
     */
    protected function renderLayoutSection($form, $section): string
    {
        $sectionId = uniqid('section_');
        $isCollapsible = strpos($section['class'] ?? '', 'collapse') !== false;
        $iconHtml = !empty($section['icon'])
            ? sprintf('<i class="%s fa-fw me-2"></i>', e($section['icon']))
            : ''
        ;
        $cardId = sprintf(
            '%s_%s-card',
            $section['tab']['id'] ?? 'main',
            $section['id']
                ?? Str::slug($section['title'] ?? null)
                    ?: 'section_' . ($section['index'] ?? $sectionId)
            ,
        );

        $html = sprintf('<div class="card mb-4" id="%s">', $cardId);

        if ($isCollapsible) {
            $html .= sprintf(
                '
                    <div class="card-header" id="heading-%s">
                        <a data-bs-toggle="collapse" href="#collapse-%s" role="button" data-bs-target="#collapse-%s" aria-expanded="false" aria-controls="collapse-%s" class="toggle-link">
                            %s%s
                            <i class="fa-solid fa-caret-down ms-auto toggle-icon"></i>
                        </a>
                    </div>
                    <div id="collapse-%s" class="collapse" aria-labelledby="heading-%s">
                        <div class="card-body pt-0">
                ',
                $sectionId,
                $sectionId,
                $sectionId,
                $sectionId,
                $iconHtml,
                e($section['title'] ?? ''),
                $sectionId,
                $sectionId
            );
        } else {
            if (!empty($section['title']) || !empty($section['icon'])) {
                $html .= sprintf(
                    '<div class="card-header">%s%s</div>',
                    $iconHtml,
                    e($section['title'] ?? '')
                );
                $html .= '<div class="card-body">';
            } else if (isset($section['tab']['id'])) {
                $html .= '<div class="card-header"></div>';
                $html .= '<div class="card-body">';
            } else {
                $html .= '<div class="card-body pt-4">';
            }
        }

        foreach ($section['rows'] as $row) {
            if (!is_array($row)) {
                $row = [$row];
            }
            $html .= '<div class="row g-3">';
            foreach ($row as $fieldName) {
                if (isset($form['fields'][$fieldName])) {
                    $field = $form['fields'][$fieldName];
                    $errors = $this->function_form_errors($field);
                    $help = $this->function_form_help($field);
                    $label = $field['label'] ?? null;
                    $html .= '<div class="col">';
                    $html .= sprintf(
                        '<div class="%smb-3">',
                        $label ? 'form-floating ' : ''
                    );
                    $html .= $this->function_form_widget(
                        $field,
                        [
                            'attr' => [
                                'class' => 'form-control',
                            ],
                        ]
                    );
                    if ($label) {
                        $html .= $this->function_form_label(
                            $field,
                            [
                                'label_attr' => [
                                    // No se puede usar form-label por lo que se
                                    // quita si existe.
                                    'class' => '',
                                ],
                                'required_label' =>
                                    ' <sup><i class="fa-solid fa-asterisk small text-danger"></i></sup>'
                                ,
                            ]
                        );
                    }
                    if ($errors) {
                        $html .= sprintf(
                            '<div class="invalid-feedback d-block">%s</div>',
                            $errors
                        );
                    }
                    if ($help) {
                        $html .= sprintf(
                            '<div class="form-text">%s</div>',
                            $help
                        );
                    }
                    $html .= '</div>'; // Cerrar div de form-floating (interior col).
                    $html .= '</div>';
                }
            }
            $html .= '</div>'; // Cerrar fila.
        }

        $html .= '</div>'; // Cerrar card-body.

        if (!empty($section['footer'])) {
            $html .= sprintf(
                '<div class="card-footer small">%s</div>',
                e($section['footer'])
            );
        }

        if ($isCollapsible) {
            $html .= '</div>'; // Cerrar collapse.
        }

        $html .= '</div>'; // Cerrar card.

        // Entregar el buffer de HTML de la sección.
        return $html;
    }

    /**
     * Crea una instancia de View_Form_Field si lo que se pasó no es una
     * instancia de dicha clase.
     *
     * @param object|array|null $field
     * @return View_Form_Field
     */
    protected function createField($field): View_Form_Field
    {
        // Si el field no tiene un valor asignado (es `null`) entonces error.
        if (!isset($field)) {
            throw new TwigException(__(
                'Se solicitó renderizar un campo de formulario que no está asignado.'
            ));
        }

        // Si el $field no es una instancia de View_Form_Field crea como objeto.
        if (!$field instanceof View_Form_Field) {
            $field = new View_Form_Field($field);
        }

        // Entregar el campo como instancia de View_Form_Field.
        return $field;
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
