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

/**
 * Representa un campo individual en un formulario.
 */
class View_Form_Field
{

    /**
     * @var string La etiqueta del campo.
     */
    public $label;

    /**
     * @var mixed El valor inicial del campo.
     */
    public $initial;

    /**
     * @var string El texto de ayuda para el campo.
     */
    public $help_text;

    /**
     * @var bool Indica si el campo es obligatorio.
     */
    public $required;

    /**
     * @var View_Form_Widget El widget utilizado para renderizar el campo.
     */
    public $widget;

    /**
     * @var array Mensajes de error personalizados para el campo.
     */
    public $error_messages;

    /**
     * @var array Lista de funciones de validación para el campo.
     */
    public $validators;

    /**
     * @var bool Indica si el campo está deshabilitado.
     */
    public $disabled;

    /**
     * @var array Lista de formatos de entrada permitidos (para campos de fecha y hora).
     */
    public $input_formats;

    /**
     * @var bool Indica si el campo debe usar localización.
     */
    public $localize;

    /**
     * @var bool Indica si el campo debe mostrar su valor inicial en un campo oculto.
     */
    public $show_hidden_initial;

    /**
     * @var mixed El valor mínimo permitido (para campos numéricos).
     */
    public $min_value;

    /**
     * @var mixed El valor máximo permitido (para campos numéricos).
     */
    public $max_value;

    /**
     * @var int La longitud mínima permitida (para campos de texto).
     */
    public $min_length;

    /**
     * @var int La longitud máxima permitida (para campos de texto).
     */
    public $max_length;

    /**
     * @var array Lista de opciones permitidas (para campos de selección).
     */
    public $choices;

    /**
     * Constructor para inicializar un campo del formulario.
     *
     * @param array $options Opciones para configurar el campo.
     */
    public function __construct(array $options = [])
    {
        $this->label = $options['label'] ?? '';
        $this->initial = $options['initial'] ?? null;
        $this->help_text = $options['help_text'] ?? '';
        $this->required = $options['required'] ?? false;
        $this->widget = $options['widget'] ?? new View_Form_Widget();
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
     * Valida el valor del campo.
     *
     * @param mixed $value El valor a validar.
     * @return array Lista de errores de validación.
     */
    public function validate($value)
    {
        // Implementar lógica de validación.
    }

}
