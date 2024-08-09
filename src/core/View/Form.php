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
 * Clase que representa un formulario.
 */
class View_Form
{

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
     * Un booleano que indica si el formulario ha sido enviado con datos (es
     * decir, si está "bound").
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
     * Un diccionario que contiene los valores iniciales de los campos del
     * formulario.
     *
     * @var array
     */
    public $initial = [];

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
     * Un prefijo de cadena que se añade a los nombres de los campos del
     * formulario para diferenciar formularios.
     *
     * @var string
     */
    public $prefix = '';

    /**
     * La clase utilizada para los errores del formulario (por defecto es
     * ErrorList).
     *
     * @var string
     */
    public $error_class = 'ErrorList';

    /**
     * Una cadena de formato utilizada para generar los atributos id de los
     * campos del formulario.
     *
     * @var string
     */
    public $auto_id = 'id_%s';

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
     * Constructor del formulario.
     *
     * @param array $data Datos enviados del formulario.
     * @param array $files Archivos enviados del formulario.
     * @param array $initial Valores iniciales de los campos del formulario.
     * @param string $prefix Prefijo para los nombres de los campos del formulario.
     * @param array $options Opciones adicionales para configurar el formulario.
     */
    public function __construct(
        array $data = [],
        array $files = [],
        array $initial = [],
        string $prefix = '',
        array $options = []
    )
    {
        // Inicializar propiedades con valores proporcionados.
        $this->data = $data;
        $this->files = $files;
        $this->initial = $initial;
        $this->prefix = $prefix;
    }

    /**
     * Verifica si el formulario es válido.
     *
     * @return bool
     */
    public function is_valid(): bool
    {
        // Implementar lógica de validación del formulario.
    }

    /**
     * Limpia y valida los datos del formulario.
     *
     * @return void
     */
    public function full_clean(): void
    {
        // Implementar lógica de limpieza y validación de datos.
    }

    /**
     * Método de limpieza adicional que puede ser sobrescrito para lógica de
     * validación personalizada.
     *
     * @return void
     */
    public function clean(): void
    {
        // Implementar lógica de limpieza personalizada
    }

    /**
     * Agrega un error a un campo específico del formulario.
     *
     * @param string $field Nombre del campo.
     * @param string $error Mensaje de error.
     * @return void
     */
    public function add_error(string $field, string $error): void
    {
        // Implementar lógica para agregar un error a un campo.
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
        // Implementar lógica para verificar si un campo tiene errores.
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
     * Devuelve true si el formulario necesita el atributo enctype="multipart/form-data".
     *
     * @return bool
     */
    public function is_multipart(): bool
    {
        // Implementar lógica para verificar si el formulario necesita
        // enctype="multipart/form-data".
    }

    /**
     * Devuelve una lista de los campos ocultos del formulario.
     *
     * @return array
     */
    public function hidden_fields(): array
    {
        // Implementar lógica para devolver una lista de los campos ocultos.
    }

    /**
     * Devuelve una lista de los campos visibles del formulario.
     *
     * @return array
     */
    public function visible_fields(): array
    {
        // Implementar lógica para devolver una lista de los campos visibles.
    }

}
