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

namespace sowerphp\core;

use \Illuminate\Support\Str;
use \Twig\Environment;
use \Twig\TwigFunction;
use \Twig\Markup;
use \Twig\Error\Error as TwigException;

/**
 * Extensión para el renderizado de formularios en una plantilla twig.
 */
class View_Engine_Twig_Form extends \Twig\Extension\AbstractExtension
{

    /**
     * Metadatos utilizados en el formulario.
     *
     * @var array
     */
    protected $meta = [];

    /**
     * Registro de los campos que ya han sido renderizados.
     *
     * @var array
     */
    protected $renderedFields = [];

    /**
     * Entorno de Twig.
     *
     * @var \Twig\Environment
     */
    protected $env;

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
     * Constructor para inicializar el entorno de Twig.
     *
     * @param \Twig\Environment $env El entorno de Twig.
     */
    public function __construct(Environment $env)
    {
        $this->env = $env;
    }

    /**
     * Entrega las funciones de la extensión.
     *
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('form_submit', [$this, 'function_form_submit']),
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
            new TwigFunction('form_captcha', [$this, 'function_form_captcha']),
        ];
    }

    /**
     * Inicializa el formulario con metadatos que pueden incluir información
     * del modelo asociado o las relaciones que el formulario necesitará.
     *
     * @param array $metadata Metadatos del formulario, ej: model o relations.
     * @return void
     */
    /*public function function_form_init(array $meta = []): void
    {
        // Asignar metadatos del formulario.
        $this->meta = $meta;
        // Buscar errores de los campos del formulario.
        $formErrors = session()->get('errors.default');
        debug($formErrors);
        if ($formErrors) {
            foreach ($formErrors as $key => $errors) {
                if (!isset($this->meta['fields'][$key])) {
                    continue;
                }
                $this->meta['fields'][$key]['errors'] = $errors;
            }
        }
    }*/

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
        $attributes = $this->buildAttributes(array_merge([
            'type' => 'submit',
            'class' => 'btn btn-primary'
        ], $attributes));
        // Generar el HTML del botón.
        $html = sprintf(
            '<button %s>%s</button>',
            $attributes,
            $this->escape($label)
        );
        // Entregar el botón renderizado.
        return new \Twig\Markup($html, 'UTF-8');
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
        // Reiniciar los campos renderizados.
        $this->renderedFields = [];
        // Definir atributos del tag <form>.
        $attributes = $this->buildAttributes(array_merge([
            'action' => $form['url'] ?? null,
            'method' => $form['method'] ?? 'POST',
            'id' => $form['id'] ?? null,
            'class' => $form['class'] ?? null,
            'enctype' => $this->function_form_enctype($form),
            'role' => 'form',
        ], $form['attributes'] ?? []));
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
        return new Markup('</form>', $this->charset);
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
        // Generar subcomponentes del renderizado del campo.
        $label = $this->function_form_label($field);
        $widget = $this->function_form_widget($field);
        $errors = $this->function_form_errors($field);
        $help = $this->function_form_help($field);
        // Generar el HTML del campo.
        $html = sprintf(
            '<div class="row mb-3 form-group'.($field['required']?' required':'').'">
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
        // Obtener el nombre y la etiqueta del campo.
        $name = $this->getFieldName($field);
        $label = $this->getFieldLabel($field);
        if ($label === null) {
            return new Markup('', $this->charset);
        }
        $id = $field['id'] ?? $name . 'Field';
        // Atributos de la etiqueta.
        $attributes = $this->buildAttributes([
            'for' => $id,
            'class' => 'form-label'
        ]);
        // Generar el HTML de la etiqueta.
        $html = sprintf(
            '<label %s>%s</label>',
            $attributes,
            $this->escape($label)
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
        // Determinar widget, su configuración y método de renderizado.
        $field['widget'] = $this->getWidgetConfig($field);
        $renderMethod = $this->getWidgetRenderer($field['widget']['element']);
        // Generar el HTML del widget.
        $html = $this->$renderMethod($field);
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
        // Obtener los errores del campo.
        $errors = $field['errors'] ?? [];
        // Si no hay errores, devolver una cadena vacía.
        if (empty($errors)) {
            return new Markup('', $this->charset);
        }
        // Generar el HTML de los errores.
        $html = '<ul class="list-unstyled mb-0">';
        foreach ($errors as $error) {
            $html .= sprintf('<li><i class="fa-solid fa-exclamation-circle"></i> %s</li>', $this->escape($error));
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
        // Obtener el texto de ayuda del campo.
        $helpText = $field['help_text'] ?? '';
        // Si no hay texto de ayuda, devolver una cadena vacía.
        if (empty($helpText)) {
            return new Markup('', $this->charset);
        }
        // Atributos del texto de ayuda.
        $attributes = $this->buildAttributes([
            'class' => 'form-text text-muted'
        ]);
        // Generar el HTML del texto de ayuda.
        $html = sprintf(
            '<div %s>%s</div>',
            $attributes,
            $this->escape($helpText)
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
        $fields = $form['fields'] ?? [];
        // Generar el HTML de todos los campos que no han sido renderizados.
        $html = '';
        foreach ($fields as $field => $config) {
            if (!in_array($field, $this->renderedFields) && $config['editable']) {
                $html .= $this->function_form_row($config);
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
    public function function_form_enctype($form): ?string
    {
        $enctype = $form['enctype'] ?? null;
        return $enctype;
    }

    /**
     * Verifica si el formulario ha sido enviado.
     *
     * @param object|array $form El formulario completo.
     * @return bool True si el formulario ha sido enviado, de lo contrario False.
     */
    public function function_form_is_submitted($form): bool
    {
        $method = $form['method'] ?? 'POST';
        if (strtoupper($method) === 'POST') {
            return !empty($_POST);
        } elseif (strtoupper($method) === 'GET') {
            return !empty($_GET);
        }
        return false;
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
            if (!empty($field['errors'])) {
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
        $id = $form['id'] ?? null;
        $html = app('captcha')->render($id);
        return new Markup($html, $this->charset);
    }

    /**
     * Escapa una cadena de texto para ser segura en un entorno HTML.
     *
     * Este método utiliza el filtro `escape` de Twig para asegurar que la
     * cadena de texto sea escapada adecuadamente, previniendo posibles ataques
     * XSS.
     *
     * @param string $string La cadena de texto que se desea escapar.
     * @param string $strategy La estrategia de escape. Por defecto es 'html'.
     * Otras opciones pueden ser 'js', 'css', 'url', etc.
     * @return string La cadena de texto escapada.
     */
    protected function escape(string $string, string $strategy = 'html'): string
    {
        $twigTemplate = $this->env->createTemplate(
            '{{ string | escape(strategy, charset) }}'
        );
        return $twigTemplate->render([
            'string' => $string,
            'strategy' => $strategy,
            'charset' => $this->charset,
        ]);
    }

    /**
     * Construye una cadena de atributos HTML a partir de un arreglo.
     *
     * @param array $attributes Arreglo de atributos.
     * @return string Cadena de atributos HTML.
     */
    protected function buildAttributes(array $attributes): string
    {
        $attributes = array_filter($attributes, function($value) {
            return $value !== null;
        });
        return implode(' ', array_map(
            function($key, $value) {
                return sprintf(
                    '%s="%s"',
                    $this->escape($key),
                    $this->escape($value)
                );
            },
            array_keys($attributes),
            $attributes
        ));
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

    /**
     * Determina el método de renderizado adecuado para el campo.
     *
     * @param array $field La configuración del campo.
     * @return string El nombre del método de renderizado.
     * @throws \Twig\Error\Error Si el método de renderizado para el widget no existe.
     */
    protected function getWidgetRenderer(string $widget): string
    {
        $method = 'render' . ucfirst(Str::camel($widget)) . 'Widget';
        if (!method_exists($this, $method)) {
            throw new TwigException(__(
                'El método de renderizado "%s()", de la extensión de formularios, para el widget "%s" no existe.',
                $method,
                $widget
            ));
        }
        return $method;
    }

    /**
     * Determina el widget para el campo.
     *
     * @param array $field La configuración del campo.
     * @return string El tipo del widget que usará el campo.
     */
    protected function getWidgetElement(array $field): string
    {
        // Si el widget viene definido en la configuración del campo se usa.
        if (!empty($field['widget'])) {
            return is_string($field['widget'])
                ? $field['widget']
                : $field['widget']['element'] ?? 'default'
            ;
        }
        // Si el 'input_type' existe y tiene un renderizador asociado se define
        // el 'input_type' como widget.
        $input_type = $field['input_type'] ?? null;
        if ($input_type !== null) {
            try {
                $this->getWidgetRenderer($input_type);
                return $input_type;
            } catch (TwigException $e) {
                // Fallar silenciosamente para pasar a siguiente validación.
            }
        }
        // Si no se logró determinar el widget se usa el por defecto.
        return 'default';
    }

    /**
     * Entrega el nombre del campo.
     *
     * @param array $field Configuración del campo.
     * @return string Nombre del campo.
     */
    protected function getFieldName(array $field): string
    {
        return $field['name'] ?? null;
    }

    /**
     * Entrega la etiqueta del campo.
     *
     * @param array $field Configuración del campo.
     * @return string Etiqueta del campo.
     */
    protected function getFieldLabel(array $field): string
    {
        return $field['verbose_name']
            ?? $field['label']
            ?? $this->getFieldName($field)
        ;
    }

    /**
     * Obtiene el valor de un campo, teniendo en cuenta la prioridad de valores
     * establecidos, valores de entrada antiguos en la sesión y valores
     * predeterminados.
     *
     * Prioridad de valores (de mayor a menor):
     *
     *   1. `initial_value` - Un valor inicial específico proporcionado en el
     *      array del campo.
     *   2. `value` - Un valor actual proporcionado en el array del campo.
     *   3. `default` - Un valor predeterminado proporcionado en el array del
     *      campo.
     *   4. `null` - Si ninguno de los valores anteriores está presente.
     *
     * Finalmente, se verifica si hay un valor antiguo en la sesión (valores de
     * entrada previos almacenados en la sesión) para el nombre del campo
     * proporcionado. Si lo hay, tendrá la máxima prioridad.
     *
     * @param array $field Un array que contiene los detalles del campo,
     * incluyendo 'name', 'initial_value', 'value', y 'default'.
     * @return mixed El valor del campo, considerando los valores anteriores de
     * la sesión.
     */
    protected function getFieldValue(array $field)
    {
        if (empty($field['name'])) {
            return null;
        }
        $value = $field['initial_value']
            ?? $field['value']
            ?? $field['default']
            ?? null
        ;
        return session()->getOldInput($field['name'], $value);
    }

    /**
     * Determina la configuración del widget del elemento HTML a partir de los
     * datos del campo del formulario.
     *
     * @param array $field Datos del campo (normalmente los del modelo).
     * @return array Arreglo con la configuración del widget a renderizar.
     */
    protected function getWidgetConfig(array $field): array
    {
        // Determinar configuración base del widget.
        $widget = $field['widget'] ?? [];
        if (!is_array($widget)) {
            $widget = ['element' => $widget];
        }
        $widgetElement = $widget['element'] ?? $this->getWidgetElement($field);
        // Asignar atributos que pueden venir en el campo y no en el widget.
        // NOTE: Probablemente en el futuro se deba revisar y dejar obsoletos
        // algunos atributos que están en el field y que son solo del widget.
        $configWidgetInField = [
            'id' => 'id',
            'name' => 'name',
            'minlength' => 'min_length',
            'maxlength' => 'max_length',
            'min' => 'min_value',
            'max' => 'max_value',
            'step' => 'step',
            'required' => 'required',
            'pattern' => 'regex',
            'contenteditable' => 'editable',
            'type' => 'input_type',
            'placeholder' => 'placeholder',
            'readonly' => 'readonly',
            'class' => 'class',
        ];
        foreach ($configWidgetInField as $attribute => $alias) {
            if (!isset($widget[$attribute])) {
                $value = $field[$alias] ?? null;
                if ($value !== null) {
                    $widget[$attribute] = $value;
                }
            }
        }
        // Atributos globales de elementos HTML.
        $attributes = [
            'accesskey' => $widget['accesskey'] ?? null,
            'autocapitalize' => $widget['autocapitalize'] ?? null,
            'autofocus' => $widget['autofocus'] ?? null,
            'class' => $widget['class'] ?? null,
            'contenteditable' => $widget['contenteditable'] ?? null,
            'enterkeyhint' => $widget['enterkeyhint'] ?? null,
            'hidden' => $widget['hidden'] ?? null,
            'id' => $widget['id'] ?? null,
            'inputmode' => $widget['inputmode'] ?? null,
            'lang' => $widget['lang'] ?? null,
            'spellcheck' => $widget['spellcheck'] ?? true,
            'style' => $widget['style'] ?? null,
            'tabindex' => $widget['tabindex'] ?? null,
            'title' => $widget['title'] ?? null,
            'virtualkeyboardpolicy' => $widget['virtualkeyboardpolicy'] ?? null,
        ];
        // Agregar atributos data-* (también son globales).
        if (!empty($widget['data'])) {
            foreach ($widget['data'] as $key => $value) {
                if ($value !== null) {
                    $attributes['data-' . $key] = $value;
                }
            }
        }
        // Agregar atributos comunes de elementos de formularios.
        $attributes['form'] = $widget['form'] ?? null;
        $attributes['name'] = $widget['name'] ?? null;
        $attributes['disabled'] = !empty($widget['disabled']) ? 'disabled' : null;
        $attributes['readonly'] = !empty($widget['readonly']) ? 'readonly' : null;
        $attributes['required'] = !empty($widget['required']) ? 'required' : null;
        $attributes['id'] = $attributes['id']
            ?? ($attributes['name'] ?? null) . 'Field'
        ;
        // Agregar atributos que son de elementos like 'input'.
        if (in_array($widgetElement, ['default', 'input', 'date', 'datetime'])) {
            $attributes['type'] = $widget['type'] ?? 'text';
            $attributes['value'] = $widget['value'] ?? $this->getFieldValue($field);
            // Agregar class según su 'type'.
            $inputTypesWithoutFormControl = ['checkbox', 'radio', 'file', 'submit', 'reset', 'button', 'image'];
            if (!in_array($attributes['type'], $inputTypesWithoutFormControl)) {
                $attributes['class'] = 'form-control ' . ($widget['class'] ?? '');
            }
            // Agregar atributos para elementos 'input' según su 'type'.
            $inputTypes = ['text', 'search', 'url', 'tel', 'email', 'password'];
            if (in_array($attributes['type'], $inputTypes)) {
                $attributes['placeholder'] = $widget['placeholder'] ?? null;
                $attributes['maxlength'] = $widget['maxlength'] ?? null;
                $attributes['minlength'] = $widget['minlength'] ?? null;
                $attributes['pattern'] = $widget['pattern'] ?? null;
                $attributes['list'] = $widget['list'] ?? null;
            }
            $inputTypes = ['number', 'range'];
            if (in_array($attributes['type'], $inputTypes)) {
                $attributes['min'] = $widget['min'] ?? null;
                $attributes['max'] = $widget['max'] ?? null;
                $attributes['step'] = $widget['step'] ?? null;
            }
            $inputTypes = ['checkbox', 'radio'];
            if (in_array($attributes['type'], $inputTypes)) {
                $attributes['checked'] = $widget['checked'] ?? null;
                $attributes['class'] = 'form-check-input ' . ($widget['class'] ?? '');
            }
            $inputTypes = ['file'];
            if (in_array($attributes['type'], $inputTypes)) {
                $attributes['multiple'] = $widget['multiple'] ?? null;
                $attributes['accept'] = $widget['accept'] ?? null;
                $attributes['class'] = 'form-control-file ' . ($widget['class'] ?? '');
            }
            $inputTypes = ['image'];
            if (in_array($attributes['type'], $inputTypes)) {
                $attributes['src'] = $widget['src'] ?? null;
                $attributes['alt'] = $widget['alt'] ?? null;
            }
            $inputTypes = ['submit', 'reset', 'button'];
            if (in_array($attributes['type'], $inputTypes)) {
                $attributes['class'] = 'btn btn-primary ' . ($widget['class'] ?? '');
            }
        }
        // Agregar atributos que son de elementos like 'select'.
        if (in_array($widgetElement, ['select'])) {
            $attributes['multiple'] = $widget['multiple'] ?? null;
            $attributes['size'] = $widget['size'] ?? null;
            $attributes['class'] = 'form-control ' . ($widget['class'] ?? '');
        }
        // Agregar atributos que son de elementos like 'textarea'.
        if (in_array($widgetElement, ['textarea'])) {
            $attributes['rows'] = $widget['rows'] ?? 5;
            $attributes['cols'] = $widget['cols'] ?? 10;
            $attributes['wrap'] = $widget['wrap'] ?? null;
            $attributes['maxlength'] = $widget['maxlength'] ?? null;
            $attributes['minlength'] = $widget['minlength'] ?? null;
            $attributes['placeholder'] = $widget['placeholder'] ?? null;
            $attributes['class'] = 'form-control ' . ($widget['class'] ?? '');
        }
        // Ajustar class del elemento.
        $valid = empty($field['errors']) ? '' : ' is-invalid';
        $attributes['class'] = trim(($attributes['class'] ?? '') . $valid);
        // Entregar los atributos determinados.
        return [
            'element' => $widgetElement,
            'attributes' => $attributes,
        ];
    }

    /**
     * Renderiza el widget por defecto para un campo.
     *
     * @param array $field La configuración del campo.
     * @return string Código HTML para el widget del campo.
     */
    protected function renderDefaultWidget(array $field): string
    {
        // Atributos del campo.
        $attributes = $field['widget']['attributes'];
        // Generar el HTML del widget.
        $html = sprintf(
            '<input %s />',
            $this->buildAttributes($attributes)
        );
        // Entregar el HTML del widget.
        return $html;
    }

    protected function renderDateWidget(array $field): string
    {
        if (!empty($field['widget']['attributes']['value'])) {
            $field['widget']['attributes']['value'] = substr(
                $field['widget']['attributes']['value'], 0, 10
            );
        }
        return $this->renderDefaultWidget($field);
    }

    protected function renderDatetimeWidget(array $field): string
    {
        if (!empty($field['widget']['attributes']['value'])) {
            $field['widget']['attributes']['value'] = substr(
                $field['widget']['attributes']['value'], 0, 16
            );
        }
        return $this->renderDefaultWidget($field);
    }

    protected function renderTextareaWidget(array $field): string
    {
        $value = $this->getFieldValue($field);
        // Atributos del campo.
        $attributes = $field['widget']['attributes'];
        // Generar el HTML del widget.
        $html = sprintf(
            '<textarea %s />%s</textarea>',
            $this->buildAttributes($attributes),
            $value
        );
        // Entregar el HTML del widget.
        return $html;
    }

}
