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

/**
 * Representa un campo individual en un formulario.
 */
class View_Form_Field implements \ArrayAccess
{

    /**
     * Nombre del campo.
     *
     * @var string
     */
    public $name;

    /**
     * La etiqueta del campo.
     *
     * @var string
     */
    public $label;

    /**
     * Indica si el campo se puede o no editar.
     *
     * @var bool
     */
    public $editable = true;

    /**
     * El valor inicial del campo.
     *
     * @var mixed
     */
    public $initial;

    /**
     * El texto de ayuda para el campo.
     *
     * @var string
     */
    public $help_text;

    /**
     * Indica si el campo es obligatorio.
     *
     * @var bool
     */
    public $required;

    /**
     * El widget utilizado para renderizar el campo.
     *
     * @var string|array|View_Form_Widget
     */
    public $widget;

    /**
     * Mensajes de error personalizados para el campo.
     *
     * @var array
     */
    public $error_messages;

    /**
     * Lista de funciones de validación para el campo.
     *
     * @var array
     */
    public $validators;

    /**
     * Indica si el campo está deshabilitado.
     *
     * @var bool
     */
    public $disabled;

    /**
     * Lista de formatos de entrada permitidos (para campos de fecha y hora).
     *
     * @var array
     */
    public $input_formats;

    /**
     * Indica si el campo debe usar localización.
     *
     * @var bool
     */
    public $localize;

    /**
     * Indica si el campo debe mostrar su valor inicial en un campo oculto.
     *
     * @var bool
     */
    public $show_hidden_initial;

    /**
     * El valor mínimo permitido (para campos numéricos).
     *
     * @var mixed
     */
    public $min_value;

    /**
     * El valor máximo permitido (para campos numéricos).
     *
     * @var mixed
     */
    public $max_value;

    /**
     * La longitud mínima permitida (para campos de texto).
     *
     * @var int
     */
    public $min_length;

    /**
     * La longitud máxima permitida (para campos de texto).
     *
     * @var int
     */
    public $max_length;

    /**
     * Lista de opciones permitidas (para campos de selección).
     *
     * @var array
     */
    public $choices;

    /**
     * Constructor para inicializar un campo del formulario.
     *
     * @param array $options Opciones para configurar el campo.
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options);
        $this->widget = $this->createWidget($this->widget);
    }

    /**
     * Verifica si un índice existe en el campo.
     *
     * @param mixed $offset El índice a verificar.
     * @return bool Verdadero si el índice existe, falso de lo contrario.
     */
    public function offsetExists($offset): bool
    {
        return property_exists($this, $offset);
    }

    /**
     * Obtiene el valor de un índice del campo.
     *
     * @param mixed $offset El índice cuyo valor se desea obtener.
     * @return mixed El valor del índice si existe, nulo de lo contrario.
     */
    public function offsetGet($offset)
    {
        return $this->$offset ?? null;
    }

    /**
     * Asigna un valor a un índice del campo.
     *
     * @param mixed $offset El índice al cual se le quiere asignar un valor.
     * @param mixed $value El valor que se quiere asignar.
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->$offset = $value;
    }

    /**
     * Elimina un índice del campo y restablece su valor por defecto si existe.
     *
     * @param mixed $offset El índice que se quiere eliminar.
     * @return void
     */
    public function offsetUnset($offset): void
    {
        $reflection = new \ReflectionClass($this);
        $defaultProperties = $reflection->getDefaultProperties();
        $this->$offset = $defaultProperties[$offset] ?? null;
    }

    /**
     * Valida el valor del campo.
     *
     * @param mixed $value El valor a validar.
     * @return array Lista de errores de validación.
     */
    public function validate($value)
    {
        // Implementar lógica de validación.
    }

    protected function setOptions(array $options): void
    {
        // Normalizar opciones.
        $options = $this->normalizeOptions($options);
        // Asignar opciones.
        $this->name = $options['name'] ?? null;
        $this->label = $options['label'] ?? '';
        $this->editable = $options['editable'] ?? true;
        $this->initial = $options['initial'] ?? null;
        $this->help_text = $options['help_text'] ?? '';
        $this->required = $options['required'] ?? true;
        $this->widget = $options['widget'] ?? null;
        $this->error_messages = $options['error_messages'] ?? [];
        $this->validators = $options['validators'] ?? [];
        $this->disabled = $options['disabled'] ?? false;
        $this->input_formats = $options['input_formats'] ?? [];
        $this->localize = $options['localize'] ?? false;
        $this->show_hidden_initial = $options['show_hidden_initial'] ?? false;
        $this->min_value = $options['min_value'] ?? null;
        $this->max_value = $options['max_value'] ?? null;
        $this->min_length = $options['min_length'] ?? null;
        $this->max_length = $options['max_length'] ?? null;
        $this->choices = $options['choices'] ?? [];
    }

    /**
     * Asigna el widget que utilizará el campo del formulario para ser
     * renderizado.
     *
     * @param string|array $widget Nombre del widget o su configuración.
     * @return View_Form_Widget Instancia del widget asignado.
     */
    protected function createWidget($widget): View_Form_Widget
    {
        // Armar arreglo base de la configuración del widget.
        if (!is_array($widget)) {
            $widget = [
                'name' => $widget,
            ];
        }
        // Obtener valor del widget.
        $widget['name'] = $widget['name'] ?? 'default';
        $widget['value'] = $widget['value']
            ?? session()->getOldInput(
                $this->name,
                $widget['initial'] ?? $this->initial
            )
        ;
        $widget['attributes'] = $widget['attributes'] ?? [];
        // Crear el widget y retornar.
        return new View_Form_Widget(
            $widget['name'],
            $widget['value'],
            $widget['attributes']
        );
    }

    /**
     * Normaliza las opciones provenientes desde diferentes formatos al
     * estándar requerido por la clase Field para poder generar el campo del
     * formulario y el widget correctamente.
     *
     * @param array $options Opciones del campo sin normalizar.
     * @return array Opciones del campo normalizadas.
     */
    protected function normalizeOptions(array $options): array
    {
        $widget = $this->normalizeWidget($options);
        $normalized = [
            'name' => $options['name'] ?? $name ?? null,
            'label' => $options['verbose_name']
                ?? $options['label']
                ?? $options['name']
                ?? null
            ,
            'editable' => $options['editable'] ?? null,
            'initial' => $widget['value'],
            'help_text' => $options['help_text'] ?? null,
            'required' => $options['required'] ?? null,
            'widget' => $widget,
            'error_messages' => $options['errors'] ?? null,
            'validators' => $options['validation']
                ?? $this->generateValidationRules($options)
            ,
            'disabled' => $options['disabled'] ?? null,
            'input_formats' => $options['input_formats'] ?? null,
            'localize' => $options['localize'] ?? null,
            'show_hidden_initial' => $options['show_hidden_initial'] ?? null,
            'min_value' => $options['min_value'] ?? null,
            'max_value' => $options['max_value'] ?? null,
            'min_length' => $options['min_length'] ?? null,
            'max_length' => $options['max_length'] ?? null,
            'choices' => $options['choices'] ?? null,
        ];
        if ($normalized['name'] === null) {
            throw new \Exception(__(
                'Falta especificar el "name" del campo del formulario.'
            ));
        }
        return $normalized;
    }

    /**
     * Determina la configuración del widget del campo del formulario a partir
     * de las opciones del campo (que pueden no estar normalizadas).
     *
     * @param array $options Opciones del campo.
     * @return array Arreglo con la configuración del widget asociado al campo.
     */
    protected function normalizeWidget(array $options): array
    {
        // Determinar configuración base del widget.
        $widget = $options['widget'] ?? [];
        if (!is_array($widget)) {
            $widget = ['name' => $widget];
        }
        $widgetName = $widget['name']
            ?? $options['input_type']
            ?? 'default'
        ;
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
                $value = $options[$alias] ?? null;
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
        $attributes['name'] = $options['name'] ?? null;
        $attributes['disabled'] = $widget['disabled'] ?? null;
        $attributes['readonly'] = $widget['readonly'] ?? null;
        $attributes['required'] = $widget['required'] ?? null;
        $attributes['id'] = $attributes['id']
            ?? ($attributes['name'] ?? null) . 'Field'
        ;
        // Si el atributo es requerido se agrega una marca 'aria'.
        if ($attributes['required']) {
            $attributes['aria-required'] = 'true';
        }
        // Agregar atributos que son de elementos like 'input'.
        $widgetTypes = [
            // Tipos de widgets propios del framework.
            'default',
            'datetime',
            // Valores de "type" oficiales de un tag "input" de HTML 5.
            'button',
            'checkbox',
            'color',
            'date',
            'datetime-local',
            'email',
            'file',
            'hidden',
            'image',
            'month',
            'number',
            'password',
            'radio',
            'range',
            'reset',
            'search',
            'submit',
            'tel',
            'text',
            'time',
            'url',
            'week'
        ];
        if (in_array($widgetName, $widgetTypes)) {
            $attributes['type'] = $widget['type'] ?? 'text';
            $attributes['value'] = null; // Se pasa como un valor aparte en el widget.
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
        if (in_array($widgetName, ['select'])) {
            $attributes['multiple'] = $widget['multiple'] ?? null;
            $attributes['size'] = $widget['size'] ?? null;
            $attributes['class'] = 'form-control ' . ($widget['class'] ?? '');
        }
        // Agregar atributos que son de elementos like 'textarea'.
        if (in_array($widgetName, ['textarea'])) {
            $attributes['rows'] = $widget['rows'] ?? 5;
            $attributes['cols'] = $widget['cols'] ?? 10;
            $attributes['wrap'] = $widget['wrap'] ?? null;
            $attributes['maxlength'] = $widget['maxlength'] ?? null;
            $attributes['minlength'] = $widget['minlength'] ?? null;
            $attributes['placeholder'] = $widget['placeholder'] ?? null;
            $attributes['class'] = 'form-control ' . ($widget['class'] ?? '');
        }
        // Ajustar class del elemento.
        $valid = empty($options['errors']) ? '' : ' is-invalid';
        $attributes['class'] = trim(($attributes['class'] ?? '') . $valid);
        // Determinar valor del widget.
        $widgetValue = $options['initial_value']
            ?? $options['value']
            ?? $options['default']
            ?? null
        ;
        if (isset($options['name'])) {
            $widgetValue = session()->getOldInput($options['name'], $widgetValue);
        }
        // Agregar el nombre del widget como atributo data-*
        $attributes['data-widget-name'] = $widgetName;
        // Entregar los atributos determinados.
        return [
            'name' => $widgetName,
            'value' => $widgetValue,
            'attributes' => array_merge($attributes, $widget['attributes'] ?? []),
        ];
    }

    /**
     * Genera las reglas de validación del campo del formulario.
     *
     * @param array $options Opciones del campo.
     * @return array Arreglo con el listado de reglas de validación determinadas.
     */
    protected function generateValidationRules(array $options): array
    {
        return app('validator')->generateValidationRules($options);
    }

}
