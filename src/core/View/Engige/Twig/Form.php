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
    public function escape(string $string, string $strategy = 'html'): string
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
     * Entrega las funciones de la extensión.
     *
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('form_init', [$this, 'function_form_init']),
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
        ];
    }

    /**
     * Inicializa el formulario con metadatos que pueden incluir información
     * del modelo asociado o las relaciones que el formulario necesitará.
     *
     * @param array $metadata Metadatos del formulario, ej: model o relations.
     * @return void
     */
    public function function_form_init(array $meta = []): void
    {
        // Asignar metadatos del formulario.
        $this->meta = $meta;
        // Reiniciar los campos renderizados.
        $this->renderedFields = [];
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
     * @param array $form El array de configuración del formulario.
     * @return \Twig\Markup Código HTML para iniciar el formulario.
     */
    public function function_form_start(array $form = []): Markup
    {
        // Definir atributos del tag <form>.
        $attributes = $this->buildAttributes(array_merge([
            'action' => $form['url'],
            'method' => $form['method'] ?? 'POST',
            'id' => $form['id'] ?? null,
            'class' => $form['class'] ?? null,
            'enctype' => $this->function_form_enctype($form),
        ], $form['attributes'] ?? []));
        // Generar el HTML del tag <form>.
        $html = sprintf('<form %s>', $attributes);
        // Entregar el tag <form> renderizado.
        return new Markup($html, $this->charset);
    }

    /**
     * Cierra la etiqueta <form>.
     *
     * @param array $form El array de configuración del formulario.
     * @return \Twig\Markup Código HTML para cerrar el formulario.
     */
    public function function_form_end(array $form = []): Markup
    {
        return new Markup('</form>', $this->charset);
    }

    /**
     * Renderiza una fila completa de un campo.
     *
     * Incluye: etiqueta, widget, errores y ayuda del campo.
     *
     * @param array $field La configuración del campo.
     * @return \Twig\Markup Código HTML para la fila del campo.
     */
    public function function_form_row(array $field): Markup
    {
        // Generar subcomponentes del renderizado del campo.
        $label = $this->function_form_label($field);
        $widget = $this->function_form_widget($field);
        $errors = $this->function_form_errors($field);
        $help = $this->function_form_help($field);
        // Generar el HTML del campo.
        $html = sprintf(
            '<div class="mb-3 row">
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
        $this->markFieldAsRendered($field['name']);
        // Entregar el campo renderizado.
        return new Markup($html, $this->charset);
    }

    /**
     * Renderiza solo la etiqueta de un campo.
     *
     * @param array $field La configuración del campo.
     * @return \Twig\Markup Código HTML para la etiqueta del campo.
     */
    public function function_form_label(array $field): Markup
    {
        // Obtener el nombre y la etiqueta del campo.
        $name = $field['name'];
        $label = $field['verbose_name'] ?? $field['label'] ?? $name;
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
     * Renderiza solo el widget (campo de entrada) de un campo.
     *
     * @param array $field La configuración del campo.
     * @return \Twig\Markup Código HTML para el widget del campo.
     */
    public function function_form_widget(array $field): Markup
    {
        // Determinar el método de renderizado del widget.
        $renderMethod = $this->resolveWidgetRenderer($field);
        // Generar el HTML del widget.
        $html = $this->$renderMethod($field);
        // Marcar el campo como renderizado.
        $this->markFieldAsRendered($field['name']);
        // Entregar el widget renderizado.
        return new Markup($html, $this->charset);
    }

    /**
     * Renderiza los errores asociados a un campo.
     *
     * @param array $field La configuración del campo.
     * @return \Twig\Markup Código HTML para los errores del campo.
     */
    public function function_form_errors(array $field): Markup
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
    public function function_form_help(array $field): Markup
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
     * @param array $form El array de configuración del formulario.
     * @return string Código HTML para los campos restantes.
     */
    public function function_form_rest(array $form = []): Markup
    {
        $fields = $this->meta['fields'] ?? [];
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
     * @param array $form El array de configuración del formulario.
     * @return string El atributo enctype para el formulario.
     */
    public function function_form_enctype(array $form): string
    {
        $enctype = $form['enctype'] ?? null;
        return $enctype;
    }

    /**
     * Verifica si el formulario ha sido enviado.
     *
     * @param array $form El array de configuración del formulario.
     * @return bool True si el formulario ha sido enviado, de lo contrario False.
     */
    public function function_form_is_submitted(array $form): bool
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
     * @param array $form El array de configuración del formulario.
     * @return bool True si el formulario es válido, de lo contrario False.
     */
    public function function_form_is_valid(array $form = []): bool
    {
        // Iterar sobre los campos y verificar si tienen errores.
        foreach ($this->meta['fields'] as $field) {
            // Si algún campo tiene errores, el formulario no es válido.
            if (!empty($field['errors'])) {
                return false;
            }
        }
        // Si no se encontraron errores, el formulario es válido.
        return true;
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
    protected function resolveWidgetRenderer(array $field): string
    {
        $widget = $this->resolveWidgetName($field);
        $method = 'render' . ucfirst(Str::camel($widget)) . 'Widget';
        if (!method_exists($this, $method)) {
            throw new TwigException(__(
                'El método de renderizado "%s()", de la extensión de formularios, para el campo "%s" con el widget "%s" no existe.',
                $method,
                $field['name'],
                $widget
            ));
        }
        return $method;
    }

    /**
     * Determina el widget para el campo.
     *
     * @param array $field La configuración del campo.
     * @return string El nombre del widget que usará el campo.
     */
    protected function resolveWidgetName(array $field): string
    {
        // Si el widget viene definido en la configuración del campo se usa.
        if (!empty($field['widget'])) {
            return $field['widget'];
        }
        // TODO: Definir el widget en base a reglas de los metadatos del campo.
        if (false) {
            return 'widget_xyz';
        }
        // Si no se logró determinar el widget se usa el por defecto.
        return 'default';
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
        $value = $field['initial_value']
            ?? $field['value']
            ?? $field['default']
            ?? null
        ;
        return session()->getOldInput($field['name'], $value);
    }

    /**
     * Renderiza el widget por defecto para un campo.
     *
     * @param array $field La configuración del campo.
     * @return string Código HTML para el widget del campo.
     */
    protected function renderDefaultWidget(array $field): string
    {
        $valid = empty($field['errors']) ? '' : 'is-invalid';
        // Atributos del campo.
        $attributes = [
            'type' => $field['input_type'] ?? 'text',
            'name' => $field['name'],
            'id' => $field['id'] ?? $field['name'],
            'class' => 'form-control ' . $valid . ' '
                . (($field['class'] ?? null) ?: '')
            ,
            'value' => $this->getFieldValue($field),
            'placeholder' => $field['placeholder'] ?? null,
            'required' => !empty($field['required']) ? 'required' : null,
            'readonly' => !empty($field['readonly']) ? 'readonly' : null,
            'minlength' => $field['min_length'] ?? null,
            'maxlength' => $field['max_length'] ?? null,
            'min' => $field['min_value'] ?? null,
            'max' => $field['max_value'] ?? null,
            'step' => $field['step'] ?? null,
            'pattern' => $field['regex'] ?? null,
        ];
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
        $value = $this->getFieldValue($field);
        if ($value) {
            $field['initial_value'] = substr($value, 0, 10);
        }
        return $this->renderDefaultWidget($field);
    }

    protected function renderDatetimeWidget(array $field): string
    {
        $value = $this->getFieldValue($field);
        if ($value) {
            $field['initial_value'] = substr($value, 0, 16);
        }
        return $this->renderDefaultWidget($field);
    }

}
