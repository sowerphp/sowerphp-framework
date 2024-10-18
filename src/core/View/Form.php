<?php

declare(strict_types=1);

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

use ArrayAccess;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use UnderflowException;

/**
 * Clase que representa un formulario.
 */
class View_Form implements ArrayAccess
{
    /**
     * Un diccionario con los datos enviados del formulario (disponible si
     * is_bound es true).
     *
     * @var array
     */
    public $data = [];

    /**
     * Un diccionario con los archivos enviados del formulario (disponible si
     * is_bound es true).
     *
     * @var array
     */
    public $files = [];

    /**
     * Una cadena de formato utilizada para generar los atributos id de los
     * campos del formulario.
     *
     * @var string
     */
    public $auto_id = '%sField';

    /**
     * Un prefijo de cadena que se añade a los nombres de los campos del
     * formulario para diferenciar formularios.
     *
     * @var string
     */
    public $prefix = '';

    /**
     * Un diccionario que contiene los valores iniciales de los campos del
     * formulario.
     *
     * @var array
     */
    public $initial = [];

    /**
     * La clase utilizada para los errores del formulario (por defecto es
     * 'invalid-feedback').
     *
     * @var string
     */
    public $error_class = 'invalid-feedback';

    /**
     * Una cadena que se añade al final de la etiqueta de cada campo.
     *
     * @var string
     */
    public $label_suffix = ':';

    /**
     * Un booleano que indica si es permitido enviar el formulario con todos
     * los campos vacíos.
     *
     * @var bool
     */
    public $empty_permitted = false;

    /**
     * Un booleano que indica si se debe usar el atributo required en los
     * campos del formulario al renderizar el HTML.
     *
     * @var bool
     */
    public $use_required_attribute = true;

    /**
     * Layout por defecto con el que se debe renderizar el formulario.
     *
     * Esta opción se utiliza si se solicita un renderizado global del
     * formulario. No se utilizará si se renderizan los campos individualmente.
     *
     * @var string|array|object|null
     */
    public $layout = null;

    /**
     * Un booleano que indica si el formulario ha sido enviado con datos (es
     * decir, si está "bound").
     *
     * Será `true` si al constructor se pasa $data o $files.
     *
     * @var bool
     */
    public $is_bound = false;

    /**
     * Un diccionario que contiene los datos validados del formulario,
     * disponible después de llamar a is_valid().
     *
     * @var array
     */
    public $cleaned_data = [];

    /**
     * Un diccionario que contiene todos los campos del formulario.
     *
     * @var array
     */
    public $fields = [];

    /**
     * Un diccionario que contiene los errores de validación para cada campo.
     *
     * @var array
     */
    public $errors = [];

    /**
     * Clave para almacenar los errores en la sesión.
     *
     * @var string
     */
    public $errors_key;

    /**
     * Atributos del formulario.
     *
     * Serán los que se utilizarán al renderizar el tag <form>.
     *
     * Este atributo incluye un listado de atributos mínimos que se incluirán
     * por defecto en el tag del formulario renderizado. Se pueden sobrescribir
     * al instanciar el formulario al renderizar el tag <form>.
     *
     * @var array
     */
    public $attributes = [
        'id' => 'defaultFormId',
        'action' => null,
        'target' => '_self',
        'method' => 'POST',
        'enctype' => 'application/x-www-form-urlencoded',
        'class' => 'form-horizontal needs-validation',
        'novalidate' => false,
        'autocomplete' => 'on',
        'accept-charset' => 'UTF-8',
        'role' => 'form',
    ];

    /**
     * Configuración del botón de envío del formulario.
     *
     * Contiene los índices:
     *   - `label`: Para el contenido del botón, que puede ser texto o HTML.
     *   - `attributes`: Para los atributos HTML del botón.
     *
     * @var array
     */
    public $submit_button = [
        'label' => 'Enviar',
        'attributes' => [
            'id' => 'id_submit_button',
            'type' => 'submit',
            'name' => 'submit_button',
            'value' => 'submit',
            'class' => 'btn btn-primary',
            'aria-label' => 'Enviar formulario',
        ],
    ];

    /**
     * Constructor del formulario.
     *
     * @param array $options Opciones adicionales para configurar el
     * formulario. Las opciones pueden incluir los otros argumentos del
     * constructor.
     * @param array|null $data Datos enviados del formulario (equivalente a
     * $request->input() en Illuminate).
     * @param array|null $files Archivos enviados del formulario (equivalente a
     * $request->file() en Illuminate).
     * @param string $auto_id Una cadena de formato utilizada para generar los
     * atributos id de los campos del formulario.
     * @param string|null $prefix Un prefijo de cadena que se añade a los
     * nombres de los campos del formulario para diferenciar formularios.
     * @param array|null $initial Valores iniciales de los campos del
     * formulario.
     * @param string $error_class La clase utilizada para los errores del
     * formulario (por defecto es 'invalid-feedback').
     * @param string|null $label_suffix Una cadena que se añade al final de la
     * etiqueta de cada campo.
     * @param bool $empty_permitted Un booleano que indica si es permitido
     * enviar el formulario con todos los campos vacíos.
     * @param array $field_order Orden en qué se deben renderizar los campos.
     * @param bool|null $use_required_attribute Un booleano que indica si se
     * debe usar el atributo required en los campos del formulario al
     * renderizar el HTML.
     * @param string|array|null $layout Layout que se debe usar para renderizar
     * los campos del formulario cuando se solicita un renderiza global.
     */
    public function __construct(
        array $options = [],
        array $data = [],
        array $files = [],
        string $auto_id = '%sField',
        string $prefix = '',
        array $initial = [],
        string $error_class = 'invalid-feedback',
        string $label_suffix = ':',
        bool $empty_permitted = false,
        array $field_order = [],
        bool $use_required_attribute = true,
        array $attributes = [],
        array $fields = [],
        array $submit_button = [],
        $layout = null
    )
    {
        // Inicializar propiedades con valores proporcionados.
        $formAttrs = [
            'data',
            'files',
            'auto_id',
            'prefix',
            'initial',
            'error_class',
            'label_suffix',
            'empty_permitted',
            'field_order',
            'use_required_attribute',
            'fields',
            'layout',
        ];
        foreach ($formAttrs as $attr) {
            $this->$attr =
                $options[$attr]
                ?? $options['form'][$attr]
                ?? $$attr
            ;
        }

        // Asignar los atributos considerando los valores por defecto del
        // formulario.
        $attributes =
            $options['attributes']
            ?? $options['form']['attributes']
            ?? $attributes
        ;
        $this->attributes = Utility_Array::mergeRecursiveDistinct(
            $this->attributes,
            $attributes
        );

        // Asignar el botón de submit considerando los valores por defecto para
        // el botón disponibles como atributo de esta clase.
        $submit_button =
            $options['submit_button']
            ?? $options['form']['submit_button']
            ?? $submit_button
        ;
        $this->submit_button = Utility_Array::mergeRecursiveDistinct(
            $this->submit_button,
            $submit_button
        );

        // Determinar si el formulario está vinculado.
        if (!empty($this->data) || !empty($this->files)) {
            $this->is_bound = true;
        }

        // Buscar errores de los campos del formulario.
        $this->errors_key = 'forms.' . $this->attributes['id'];
        $this->errors = session()->get('errors.' . $this->errors_key);
        if ($this->errors) {
            foreach ($this->errors as $field => $errors) {
                foreach ($errors as $code => $error) {
                    $this->add_error($field, $error, $code);
                }
            }
        }
    }

    /**
     * Entrega mágicamente un campo del formulario como si fuese un atributo
     * de la instancia del formulario.
     *
     * @param string $attribute Nombre del campo del formulario a obtener.
     * @return View_Form_Field
     */
    public function __get(string $attribute)
    {
        // Se solicitó un campo del formulario.
        if (isset($this->fields[$attribute])) {
            return $this->fields[$attribute];
        }

        // Lo que se solicitó no es un campo del formulario, entonces error.
        throw new UnderflowException(__(
            'Campo "%s" del formulario "%s" no existe.',
            $attribute,
            $this->attributes['id']
        ));
    }

    /**
     * Permite indicar mágicamente si un atributo solicitado del objeto puede
     * ser obtenido como un campo del formulario.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $attribute): bool
    {
        return isset($this->fields[$attribute]);
    }

    /**
     * Verifica si un índice existe en el formulario.
     *
     * @param mixed $offset El índice a verificar.
     * @return bool Verdadero si el índice existe, falso de lo contrario.
     */
    public function offsetExists($offset): bool
    {
        return property_exists($this, $offset);
    }

    /**
     * Obtiene el valor de un índice del formulario.
     *
     * @param mixed $offset El índice cuyo valor se desea obtener.
     * @return mixed El valor del índice si existe, nulo de lo contrario.
     */
    public function offsetGet($offset)
    {
        if (method_exists($this, $offset)) {
            return $this->$offset();
        }

        return $this->$offset ?? null;
    }

    /**
     * Asigna un valor a un índice del formulario.
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
     * Elimina un índice del formulario y restablece su valor por defecto si
     * existe.
     *
     * @param mixed $offset El índice que se quiere eliminar.
     * @return void
     */
    public function offsetUnset($offset): void
    {
        $reflection = new ReflectionClass($this);
        $defaultProperties = $reflection->getDefaultProperties();
        $this->$offset = $defaultProperties[$offset] ?? null;
    }

    /**
     * Determina si el formulario es válido.
     *
     * Proceso para determinar si es válido:
     *
     *   - Verifica si el formulario está vinculado.
     *   - Realiza una limpieza y validación completa.
     *
     * @return bool Devuelve true si no hay errores, false en caso contrario.
     */
    public function is_valid(): bool
    {
        // Si el formulario no está vinculado, se considera inválido.
        if (!$this->is_bound) {
            return false;
        }

        // Realiza la limpieza completa de los datos del formulario.
        $this->full_clean();

        // El formulario es válido si no hubo errores durante la limpieza y
        // validación.
        return empty($this->errors);
    }

    /**
     * Limpia y valida los datos del formulario.
     *
     * @return void
     */
    protected function full_clean(): void
    {
        // Asegurar que tendremos datos y errores limpios.
        $this->cleaned_data = [];
        $this->errors = [];

        // Si el formulario no está vinculado se retorna (nada que validar).
        if (!$this->is_bound) {
            return;
        }

        // Ejecutar limpieza y validación.
        $this->clean_fields();
        $this->clean_form();
        $this->post_clean();
    }

    /**
     * Limpia y valida cada campo individualmente utilizando los métodos de
     * limpieza específicos de cada campo (clean_<fieldname>()).
     *
     * @return void
     */
    protected function clean_fields(): void
    {
        // Obtener datos que se pasaron.
        $data = array_merge($this->data, $this->files);

        // Limpiar datos.
        // TODO: mejorar la sanitización utilizando reglas de sanitización
        // personalizadas por cada campo (similar a las validaciones).
        $data = app('sanitizer')->sanitize($data, [
            'remove_non_printable',
            'strip_tags',
            'spaces',
            'trim',
        ]);

        // Obtener reglas de validación.
        $rules = [];
        $customAttributes = [];
        foreach ($this->fields as $field) {
            $rules[$field->name] = $field->validators;
            $customAttributes[$field->name] = $field->label;
        }

        // Validar datos.
        try {
            $this->cleaned_data = app('validator')->validate(
                $data,
                $rules,
                [],
                $customAttributes
            );
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $errors) {
                foreach ($errors as $code => $error) {
                    $this->add_error($field, $error, $code);
                }
            }
        }
    }

    /**
     * Llama al método clean() del formulario para realizar validaciones
     * adicionales que involucren múltiples campos.
     *
     * @return void
     */
    protected function clean_form(): void
    {
        try {
            $this->clean();
        } catch (ValidationException $e) {
            $this->add_error(null, $e->getMessage());
        }
    }

    /**
     * Método de limpieza adicional que puede ser sobrescrito para lógica de
     * validación personalizada.
     *
     * @return void
     */
    protected function clean(): void
    {
        // Implementar lógica de limpieza personalizada en cada clase heredada.
    }

    /**
     * En formularios de modelos, este método se encarga de validar la
     * instancia del modelo contra las restricciones de la base de datos
     * (como restricciones de unicidad).
     *
     * Puede ser usado para cualquier validación adicional que necesite
     * realizarse después de que todos los campos individuales han sido
     * limpiados y validados.
     *
     * Este método puede estar vacío si no hay validaciones adicionales.
     * Si hay validaciones adicionales, se pueden implementar aquí.
     *
     * @return void
     */
    protected function post_clean(): void
    {
        // TODO: ver dónde y cómo usar. Por ejemplo, la unicidad ya se valida
        // antes mediante las reglas de validación estándares.
    }

    /**
     * Agrega un error a un campo específico del formulario.
     *
     * @param null|string $field Nombre del campo.
     * @param string $error Mensaje de error.
     * @param int|string $code Código de error.
     * @return void
     */
    protected function add_error(
        ?string $field,
        string $error,
        $code = null
    ): void {
        if ($field === null) {
            $this->errors[] = $error;
        } else {
            if ($code === null) {
                $this->errors[$field][] = $error;
                $this->fields[$field]->error_messages[] = $error;
            } else {
                $this->errors[$field][$code] = $error;
                $this->fields[$field]->error_messages[$code] = $error;
            }
        }
    }

    /**
     * Verifica si un campo tiene errores.
     *
     * @param string $field Nombre del campo.
     * @param string|null $code Código de error.
     * @return bool
     */
    public function has_error(string $field, string $code = null): bool
    {
        if ($code === null) {
            return !empty($this->fields[$field]->error_messages);
        }

        return !empty($this->fields[$field]->error_messages[$code]);
    }

    /**
     * Devuelve los errores que no están asociados a ningún campo específico.
     *
     * @return array
     */
    public function non_field_errors(): array
    {
        // Implementar lógica para devolver los errores no asociados a campos
        // específicos.
    }

    /**
     * Añade el prefijo a un nombre de campo.
     *
     * @param string $field_name Nombre del campo.
     * @return string
     */
    public function add_prefix(string $field_name): string
    {
        // Implementar lógica para añadir prefijo a un nombre de campo.
    }

    /**
     * Devuelve los datos del campo que han sido enviados.
     *
     * @param mixed $data Datos enviados.
     * @param mixed $initial Valor inicial del campo.
     * @return mixed
     */
    public function bound_data($data, $initial)
    {
        // Implementar lógica para devolver los datos enviados del campo.
    }

    /**
     * Devuelve `true` si el formulario necesita el atributo
     * `enctype="multipart/form-data"`.
     *
     * @return bool
     */
    public function is_multipart(): bool
    {
        foreach ($this->fields as $field) {
            $type = $field->widget->attributes['type'] ?? null;

            if ($type == 'file') {
                return true;
            }
        }

        return false;
    }

    /**
     * Devuelve una lista de los campos ocultos del formulario.
     *
     * @return array
     */
    public function hidden_fields(): array
    {
        $fields = [];

        foreach ($this->fields as $name => $field) {
            $type = $field->widget->attributes['type'] ?? null;
            if ($type === 'hidden') {
                $fields[$name] = $field;
            }
        }

        return $fields;
    }

    /**
     * Devuelve una lista de los campos visibles del formulario.
     *
     * @return array
     */
    public function visible_fields(): array
    {
        $fields = [];

        foreach ($this->fields as $name => $field) {
            $type = $field->widget->attributes['type'] ?? null;
            if ($type !== 'hidden') {
                $fields[$name] = $field;
            }
        }

        return $fields;
    }

    /**
     * Entrega el Token CSRF para ser usado en el formulario.
     *
     * @return string
     */
    public function csrf_token(): string
    {
        return session()->csrf_token();
    }

    /**
     * Método genérico que crea un formulario a partir de los datos que son
     * entregados por el método estático buildForm().
     *
     * Este método permite crear fácilmente formularios a partir de un arreglo
     * de opciones entrado por el método buildForm() que debe ser implementado
     * en cada uno de las clases de formularios que hereden de esta clase y que
     * deseen usar la factory con el método estático create().
     *
     * @return self
     */
    public static function create(array $options = []): self
    {
        $merge = $options['merge'] ?? true;
        if ($merge) {
            $options = array_merge($options, static::buildForm($options));
        } else {
            $options = static::buildForm($options);
        }
        if (isset($options['fields'])) {
            foreach ((array)$options['fields'] as $name => $field) {
                if (is_array($field)) {
                    $options['fields'][$name] = new View_Form_Field(
                        array_merge([
                            'name' => $name,
                        ], $field)
                    );
                }
            }
        }
        $form = new self($options);

        return $form;
    }
}
