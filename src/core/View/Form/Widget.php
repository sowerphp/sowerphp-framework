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

use ArrayAccess;
use DomainException;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * Clase base para todos los widgets de formulario.
 */
class View_Form_Widget implements ArrayAccess
{
    /**
     * El nombre del widget del formulario.
     *
     * @var string
     */
    public $name;

    /**
     * El valor del widget del formulario.
     *
     * @var mixed
     */
    public $value;

    /**
     * Los atributos (u opciones) del widget del formulario.
     *
     * Normalmente serán atributos directos de un elemento de formmulario HTML.
     * Sin embargo, algunos widgets usan opciones más complejas para rederizar
     * los elementos (ej: un widget con múltiples elementos HTML).
     *
     * @var array
     */
    public $attributes;

    /**
     * Opciones adicionales para el widget.
     *
     * Estas opciones no son atributos válidos para el HTML. Sin embargo, pueden
     * contener opciones (datos) que son necesarias para renderizar el HTML. Por
     * ejemplo, los índices que pueden existir en este arreglo, sin ser un
     * listado taxativo, son:
     *
     *   - `choices`: Lista de opciones permitidas para campos de selección o
     *     similares.
     *
     * @var array
     */
    protected $options;

    /**
     * Constructor de un widget de formulario.
     *
     * @param string $name El nombre del widget del formulario.
     * @param mixed $value El valor del widget del formulario.
     * @param array $attributes Los atributos HTML del widget del formulario.
     * @param array $options Opciones adicionales del widget (no son atributos).
     */
    public function __construct(
        string $name,
        $value,
        array $attributes = [],
        array $options = []
    ) {
        $this->name = $name;
        $this->value = $value;
        $this->attributes = $attributes;
        $this->options = $options;
    }

    /**
     * Verifica si un índice existe en el widget.
     *
     * @param mixed $offset El índice a verificar.
     * @return bool Verdadero si el índice existe, falso de lo contrario.
     */
    public function offsetExists($offset): bool
    {
        return property_exists($this, $offset);
    }

    /**
     * Obtiene el valor de un índice del widget.
     *
     * @param mixed $offset El índice cuyo valor se desea obtener.
     * @return mixed El valor del índice si existe, nulo de lo contrario.
     */
    public function offsetGet($offset)
    {
        return $this->$offset ?? null;
    }

    /**
     * Asigna un valor a un índice del widget.
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
     * Elimina un índice del widget y restablece su valor por defecto si existe.
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
     * Renderiza el widget del campo del formulario generando su HTML.
     *
     * @return string El HTML renderizado del widget.
     */
    public function render(array $options = []): string
    {
        // Unir las opciones pasadas al renderizar con las que previamente
        // estaban asignadas mediante el constructor.
        $this->options = array_merge($this->options, $options);

        // Asignar los atributos del widget según lo que se haya pasado en las
        // opciones. Esto permite reutilizar un widget modificando sus valores
        // al momento de renderizarlo.
        $this->name = $this->options['name'] ?? $this->name;
        $this->value = $this->options['value'] ?? $this->value;
        $this->attributes = array_merge(
            $this->attributes,
            $this->options['attributes'] ?? [],
            $this->options['attr'] ?? []
        );

        // Definir el método que renderizará el widget.
        $method = 'render' . ucfirst(Str::camel($this->name)) . 'Widget';

        // Si el método de renderizado no existe se renderiza con el widget de
        // entrada por defecto.
        if (!method_exists($this, $method)) {
            return $this->renderDefaultWidget();
        }

        // Renderizar el widget con un método específico.
        return $this->$method();
    }

    /**
     * Renderiza el widget de tipo `div`.
     *
     * Este tipo de widget se utiliza cuando se desea mostrar un valor en el
     * formulario pero que no es utilizado como entrada para datos a través
     * del formulario.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderDivWidget(): string
    {
        return sprintf(
            '<div %s>%s</div>',
            $this->renderAttributes(),
            $this->value
        );
    }

    /**
     * Renderiza el widget por defecto que se debe utilizar.
     *
     * Es un alias para renderizar el widget de tipo `input`.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderDefaultWidget(): string
    {
        return $this->renderInputWidget();
    }

    /**
     * Renderiza el widget de tipo `input`.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderInputWidget(): string
    {
        $this->attributes['name'] = $this->attributes['name'] ?? $this->name;
        $this->attributes['value'] = $this->attributes['value'] ?? $this->value;
        $this->attributes['required'] = $this->attributes['required'] ?? false;

        return sprintf(
            '<input %s />',
            $this->renderAttributes()
        );
    }

    /**
     * Renderiza el widget predeterminado para los campos de un formulario.
     *
     * Se utiliza el tipo `text` como widget por defecto.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderTextWidget(): string
    {
        $this->attributes['type'] = 'text';

        return $this->renderInputWidget();
    }

    /**
     * Renderiza el widget de tipo `hidden`.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderHiddenWidget(): string
    {
        return $this->renderInputWidget();
    }

    /**
     * Renderiza el widget de tipo `date`.
     *
     * Renderiza un campo de entrada de fecha.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderDateWidget(): string
    {
        if (!empty($this->value)) {
            $this->value = substr($this->value, 0, 10);
        }

        return $this->renderInputWidget();
    }

    /**
     * Renderiza el widget de tipo `datetime`.
     *
     * Renderiza un campo de entrada de fecha y hora.
     *
     * Este es un alias del método renderDatetimeLocalWidget().
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderDatetimeWidget(): string
    {
        $this->attributes['type'] = 'datetime-local';

        return $this->renderDatetimeLocalWidget();
    }

    /**
     * Renderiza el widget de tipo `datetime-local`.
     *
     * Renderiza un campo de entrada de fecha y hora.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderDatetimeLocalWidget(): string
    {
        if (!empty($this->value)) {
            $this->value = substr($this->value, 0, 16);
        }

        return $this->renderInputWidget();
    }

    /**
     * Renderiza el widget de tipo `textarea`.
     *
     * Renderiza un campo de texto de varias líneas.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderTextareaWidget(): string
    {
        return sprintf(
            '<textarea %s />%s</textarea>',
            $this->renderAttributes(),
            $this->value
        );
    }

    /**
     * Renderiza el widget de tipo `button`.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderButtonWidget(): string
    {
        return sprintf(
            '<button %s>%s</button>',
            $this->renderAttributes(),
            $this->value
        );
    }

    /**
     * Renderiza el widget de tipo `password`.
     *
     * Renderiza un campo de entrada de contraseña con botones que permiten:
     *
     *   - Mostrar y ocultar la contraseña.
     *   - Copiar la contraseña.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderPasswordWidget(): string
    {
        return sprintf(
            '<div class="input-group">%s%s%s</div>',
            $this->renderInputWidget(),
            '<a class="input-group-text" style="cursor:pointer;text-decoration:none" onclick="Form.showPassword(this)"><i class="fa-regular fa-eye fa-fw"></i></a>',
            '<a class="input-group-text" style="cursor:pointer;text-decoration:none" onclick="__.copy(this.parentNode.querySelector(\'input\').value, \'Copiado.\')"><i class="fa-regular fa-copy fa-fw"></i></a>'
        );
    }

    /**
     * Renderiza el widget de tipo `bool`.
     *
     * Renderiza un campo de selección de opciones booleanas. Por defecto se
     * utilizan los valores:
     *
     *   - `0` para No.
     *   - `1` para Si.
     *
     * @return string El HTML renderizado del widget.
     * @throws DomainException Si no se proporcionan exactamente 2 opciones.
     */
    protected function renderBoolWidget(): string
    {
        // Obtener los tag "options" (están en "choices").
        $this->options['choices'] = $this->options['choices'] ?? [
            0 => __('No'),
            1 => __('Si'),
        ];

        // Asegura que "choices" sea un `array` con exactamente dos elementos.
        if (count($this->options['choices']) !== 2) {
            throw new DomainException(__(
                'Un campo booleano debe tener exactamente 2 opciones.'
            ));
        }

        // Renderizar como campo `select`.
        return $this->renderSelectWidget();
    }

    /**
     * Renderiza el widget de tipo `select`.
     *
     * Renderiza un campo de selección de opciones.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderSelectWidget(): string
    {
        // Obtener los tag "options" (están en "choices").
        $choices = $this->normalizeChoices($this->options['choices'] ?? []);

        // Renderizar las opciones del campo `select`.
        $html = '';
        foreach ($choices as $choice) {
            // Determinar si la opción debería estar seleccionada.
            $selected = ($this->value ?? '') == $choice['value']
                ? ' selected'
                : ''
            ;

            // Renderizar, y agregar al HTML, el tag `option`.
            $html .= sprintf(
                '<option value="%s"%s>%s</option>',
                $this->sanitize((string) $choice['value']),
                $selected,
                $this->sanitize((string) $choice['label'])
            );
        }

        // Entregar el campo renderizado.
        return sprintf(
            '<select %s>%s</select>',
            $this->renderAttributes(),
            $html
        );
    }

    /**
     * Renderiza el widget de tipo `file`.
     *
     * Renderiza una tabla con campos de archivo.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderFileWidget(): string
    {
        return $this->renderInputWidget();
    }

    /**
     * Renderiza el widget de tipo `files`.
     *
     * Renderiza una tabla con campos de archivo, con la posibilidad de añadir o
     * eliminar filas para múltiples archivos (uno por fila).
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderFilesWidget(): string
    {
        // Definir el título de la columna de los archivos en la tabla.
        $titles = [
            $this->attributes['title']
                ?? $this->options['field']['label']
                ?? __('Archivos')
        ];

        // Definir el campo que se usará en la tabla.
        $fields = [
            [
                'widget' => 'file',
                'name' => $this->options['field']['name'],
            ],
        ];

        // Agregar "titles" y "fields" a las opciones que se pasarán al widget
        // que generará la tabla dinámica.
        $this->options['titles'] = $titles;
        $this->options['fields'] = $fields;
        $this->options['dynamic'] = true;

        // Renderiza el widget con las opciones configuradas.
        return $this->renderTableWidget();
    }

    /**
     * Renderiza el widget de tipo `table`.
     *
     * Por defecto se crea una tabla con filas fijas, no permite agregar nuevas
     * filas. O sea, es una tabla estática, sin usar JS para agregar filas.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderTableWidget(): string
    {
        // Normalizar opciones de la tabla.
        $options = $this->normalizeTableOptions($this->options);

        // Renderizar la fila de los widgets sin valores por defecto.
        $widgetsRow = $this->renderTableRowWidgets(
            $options['widgets'],
            [], // Sin valores por defecto.
            $options['dynamic']
        );

        // Buffer para la tabla que se generará.
        $buffer = '';

        // Agregar definición en javascript de los widgets renderizados de la
        // tabla para ser insertados posteriormente de manera dinámica si así
        // fue solicitado.
        if ($options['dynamic']) {
            $buffer .= sprintf(
                '<script> window["inputsJS_%s"] = %s;</script>',
                $this->attributes['id'],
                json_encode($widgetsRow)
            );
        }

        // Abrir tag `table`.
        $buffer .= sprintf(
            '<table class="table table-striped" id="%s" style="width:%s">',
            $this->attributes['id'],
            $options['width']
        );

        // Agregar tag `thead` con los títulos de las columnas.
        $buffer .= '<thead><tr>';
        foreach ($options['titles'] as $title) {
            $buffer .= sprintf(
                '<th>%s</th>',
                $title
            );
        }

        // Agregar la columna para agregar una nueva fila si es tabla dinámica.
        if ($options['dynamic']) {
            $buffer .= sprintf(
                '<th style="width:1px;"><a href="javascript:Form.addJS(\'%s\', undefined, %s)" title="Agregar fila [%s]" accesskey="%s" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus fa-fw"></i></a></th>',
                $this->attributes['id'],
                $options['callback'],
                $options['accesskey'],
                $options['accesskey']
            );
        }
        $buffer .= '</tr></thead>';

        // Abrir tag `tbody`.
        $buffer .= '<tbody>';

        // Si no hay valores pasados a la tabla se agrega una fila vacía con los
        // widgets.
        if (empty($options['values'])) {
            $buffer .= $widgetsRow;
        }

        // Hay valores pasados, se agrega una fila por cada arreglo de valores.
        else {
            // Cada elemento en `$options['values']` representa una fila de la
            // tabla que se está renderizando.
            foreach ($options['values'] as $values) {
                $buffer .= $this->renderTableRowWidgets(
                    $options['widgets'],
                    $values, // Valores de la fila de widgets que se renderiza.
                    $options['dynamic']
                );
            }
        }

        // Cerrar tags `tbody` y `table`.
        $buffer .= '</tbody></table>';

        // Entregar el buffer con el HTML renderizado con la tabla.
        return $buffer;
    }

    /**
     * Renderiza el widget de tipo table.
     *
     * Renderiza una tabla con los campos de un formulario.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderTableDynamicWidget(): string
    {
        $this->options['dynamic'] = true;

        return $this->renderTableWidget();
    }

    /**
     * Renderiza el widget de checkbox.
     *
     * Renderiza un campo de checkbox.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderCheckboxWidget(): string
    {
        $this->value = $this->value ?? 1;

        return $this->renderInputWidget();
    }

    /**
     * Renderiza el widget de checkboxes.
     *
     * Renderiza campos con multiples checkbox.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderCheckboxesWidget(): string
    {
        // Obtener los tag "checkbox" (están en "choices").
        $choices = $this->normalizeChoices($this->options['choices'] ?? []);
        $this->value = $this->value ?? [];

        // Asignar clase CSS por defecto.
        if (empty($this->attributes['class'])) {
            $this->attributes['class'] = 'form-check-input';
        }

        // Renderizar checkboxes.
        $html = '';
        foreach ($choices as $index => $choice) {
            // Determinar si el checkbox debería estar seleccionada.
            $checked = in_array($choice['value'], $this->value)
                ? ' checked'
                : ''
            ;

            // Determinar ID del checkbox que se está renderizando.
            $choice['id'] = $choice['id'] ?? ($this->name . '_' . $index);

            // Renderizar el checkbox.
            $html .= sprintf(
                '<input type="checkbox" id="%s" name="%s[]" value="%s" %s %s> <label for=%s>%s</label><br>',
                $choice['id'],
                $this->sanitize($this->name),
                $this->sanitize($choice['value']),
                $this->renderAttributes(),
                $checked,
                $choice['id'],
                $this->sanitize($choice['label'])
            );
        }

        // Entregar los inputs con los checkboxes renderizados.
        return sprintf('<div>%s</div>', $html);
    }

    /**
     * Renderiza el widget de tipo `table_checkboxes`.
     *
     * Renderiza una tabla donde cada fila tiene un widget de tipo `checkbox`
     * para "seleccionar" la fila.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderTableCheckboxesWidget(): string
    {
        // Si no hay filas que renderizar se entrega un "-".
        if (empty($this->options['rows'])) {
            return '-';
        }

        // Crear los widgets. Se crean N widgets `div` y un `checkbox` para cada
        // fila de la tabla.
        $this->options['widgets'] = [];
        foreach ($this->options['titles'] as $index => $title) {
            $this->options['widgets'][] = new self('div', null);
        }
        $this->options['widgets'][] =  new self(
            'checkbox',
            null,
            [
                'type' => 'checkbox',
                'class' => 'form-check-input',
            ]
        );

        // Agregar el checkbox global a los títulos.
        $globalCheckboxWidget = new self(
            'checkbox',
            $this->value === true,
            [
                'type' => 'checkbox',
                'onclick' => sprintf(
                    'Form.checkboxesSet(\'%s\', this.checked)',
                    $this->attributes['name']
                ),
                'class' => 'form-check-input',
            ]
        );
        $this->options['titles'][] = $globalCheckboxWidget->render();

        // Determinar los valores que se asignarán a la tabla.
        $this->options['values'] = [];
        foreach ($this->options['rows'] as $row) {
            $value = [];
            foreach ($row as $col) {
                $value[] = $col;
            }
            $value[] = $row['id'] ?? $row[0];
            $this->options['values'][] = $value;
        }

        // Renderizar la tabla con las opciones correspondientes.
        return $this->renderTableWidget();
    }

    /**
     * Renderiza el widget de tipo `radio`.
     *
     * Renderiza un campo de radio.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderRadioWidget(): string
    {
        return $this->renderInputWidget();
    }

    /**
     * Renderiza el widget de `radios`.
     *
     * Renderiza un campo de selección de radio.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderRadiosWidget(): string
    {
        // Obtener los tag "radio" (están en "choices").
        $choices = $this->normalizeChoices($this->options['choices'] ?? []);

        // Asignar clase CSS por defecto.
        if (empty($this->attributes['class'])) {
            $this->attributes['class'] = 'form-check-input';
        }

        // Renderizar radios.
        $html = '';
        foreach ($choices as $index => $choice) {
            // Determinar si el radio debería estar seleccionada.
            $checked = $choice['value'] == $this->value
                ? ' checked'
                : ''
            ;

            // Determinar ID del radio que se está renderizando.
            $choice['id'] = $choice['id'] ?? ($this->name . '_' . $index);

            // Renderizar el radio.
            $html .= sprintf(
                '<input type="radio" id="%s" name="%s[]" value="%s" %s %s> <label for=%s>%s</label><br>',
                $choice['id'],
                $this->sanitize($this->name),
                $this->sanitize($choice['value']),
                $this->renderAttributes(),
                $checked,
                $choice['id'],
                $this->sanitize($choice['label'])
            );
        }

        // Entregar los inputs con los radios renderizados.
        return sprintf('<div>%s</div>', $html);
    }

    /**
     * Renderiza el widget de TableRadios.
     *
     * Renderiza una tabla con campos de radio.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderTableRadiosWidget(): string
    {
        // Si no hay filas que renderizar se entrega un "-".
        if (empty($this->options['rows'])) {
            return '-';
        }
        $n_cols = count($this->options['rows'][0]);

        // Crear los widgets. Se crean N widgets `div` y un `checkbox` para cada
        // fila de la tabla.
        $this->options['widgets'] = [];
        for ($i = 0; $i < $n_cols; $i++) {
            $this->options['widgets'][] = new self('div', null);
        }

        // Agregar un radio por cada opción que exista.
        $n_radios = count($this->options['titles']) - $n_cols;
        for ($i = 0; $i < $n_radios; $i++) {
            $this->options['widgets'][] =  new self(
                'radio',
                null,
                [
                    'type' => 'radio',
                    'class' => 'form-check-input',
                ]
            );
        }

        // Determinar los valores que se asignarán a la tabla.
        $this->options['values'] = [];
        foreach ($this->options['rows'] as $row) {
            $value = [];
            foreach ($row as $col) {
                $value[] = $col;
            }
            $value[] = $row['id'] ?? $row[0];
            $this->options['values'][] = $value;
        }

        // Renderizar la tabla con las opciones correspondientes.
        return $this->renderTableWidget();
    }

    /**
     * Sanitiza el parámetro pasado y lo entrega como string.
     *
     * @param mixed $string
     * @return string
     */
    protected function sanitize($string): string
    {
        return htmlspecialchars((string) $string);
    }

    /**
     * Entrega los atributos del widget como un string para incluirlo en el
     * HTML del widget renderizado.
     *
     * @return string
     */
    protected function renderAttributes(): string
    {
        return html_attributes($this->attributes);
    }

    /**
     * Renderiza la fila de los widgets en un widget de tipo `table` o que se
     * crea utilizando una tabla.
     *
     * @param array $widgets Instancias de los widgets a renderizar.
     * @param array $values Lista con los valores para los widgets de la fila.
     * @param bool $deleteButton `true` si se debe renderizar el botón eliminar.
     * @return string
     */
    protected function renderTableRowWidgets(
        array $widgets,
        array $values,
        bool $deleteButton
    ): string {
        // Iniciar la fila de widgets.
        $widgetsRow = '<tr>';

        // Renderizar cada columna con su widget.
        foreach($widgets as $index => $widget) {
            $widget = clone $widget;
            $widgetsRow .= sprintf(
                '<td %s>%s</td>',
                html_attributes($options['cols_attributes'][$index] ?? []),
                $widget->render([
                    'value' => $values[$index] ?? null,
                ])
            );
        }

        // Botón de borrado de la fila (si es tabla dinámica).
        if ($deleteButton) {
            $widgetsRow .= sprintf(
                '<td><a class="%s_eliminar btn btn-danger btn-sm" href="" onclick="Form.delJS(this); return false" title="Eliminar fila"><i class="fa-solid fa-times fa-fw"></i></a></td>',
                $this->attributes['id']
            );
        }

        // Terminar la fila de widgets.
        $widgetsRow .= '</tr>';

        // Entregar la fila renderizada de los widgets.
        return $widgetsRow;
    }

    /**
     * Normaliza un arreglo de choices.
     *
     * Entrega un arreglo de arreglos asociativos. Donde cada choice tiene un
     * índice `value` y un índice `label`.
     *
     * @param array $choices
     * @return array
     */
    protected function normalizeChoices(array $choices): array
    {
        // Arreglo para las choices normalizadas.
        $normalized = [];

        // Recorrer cada choice y normalizar.
        foreach ($choices as $key => $choice) {
            // Si $choice no es un arreglo se pasó en $key el "value" y en
            // $choice el label.
            if (!is_array($choice)) {
                $choice = [
                    'value' => $key,
                    'label' => $choice,
                ];
            }

            // Normalizar el arreglo dejando los datos desde sus posibles
            // orígenes.
            $choice['value'] = $choice['value'] ?? array_values($choice)[0];
            $choice['label'] = $choice['label'] ?? array_values($choice)[1];
            $choice['id'] = $choice['id'] ?? array_values($choice)[2] ?? null;

            // Agregar $choice normalizada.
            $normalized[] = $choice;
        }

        // Entregar choices normalizadas.
        return $normalized;
    }

    /**
     * Normaliza las opciones para renderizar los widget que generan tablas.
     *
     * @param array $options
     * @return array
     */
    protected function normalizeTableOptions(array $options): array
    {
        // Definir opciones por defecto si no fueron asignadas.
        $options = array_merge(
            [
                // Definición principal de títulos y widgets para las filas.
                'titles' => [],
                'widgets' => [],

                // Definición secundaria, que permite generar los títulos y los
                // widgets para dar más flexibilidad en la creación de los
                // formularios.
                'fields' => [],
                'values' => [],

                // Estilos para la tabla, ancho tabla, ancho columnas y estilos.
                'width' => '100%', // Máximo ancho de la tabla.
                'cols_width' => [], // Mismo ancho de las columnas.
                'cols_attributes' => [], // Atributos para los tag `td`.

                // Opciones cuando se permiten agregar filas dinámicamente.
                'dynamic' => false, // Con `true` usará JS para filas dinámicas.
                'accesskey' => '+', // Si hay más de una tabla se debe definir.
                'callback' => 'undefined', // Ejecutar función al agregar fila.
            ],
            $options
        );

        // Crear los widgets si lo que se pasó fueron fields y valores.
        if (empty($options['widgets']) && !empty($options['fields'])) {
            foreach ($options['fields'] as $index => $fieldOptions) {
                // Crear el campo del formulario.
                $field = new View_Form_Field($fieldOptions);

                // Asignar el widget del formulario.
                $options['widgets'][] = $field['widget'];
            }
        }

        // Determinar ancho de columnas si no fue especificado en las opciones.
        if (empty($options['cols_width'])) {
            $options['cols_width'] = array_fill(
                0,
                count($options['titles']),
                '100px'
            );
        }

        // Revisar los estilos de las columnas, donde los widgets ocultos no se
        // muestran en la tabla dichas columnas.
        foreach ($options['widgets'] as $index => $widget) {
            // Si el widget es un campo oculto, la columna se oculta.
            if ($widget['type'] === 'hidden') {
                $options['cols_attributes'][$index]['class'] = 'd-none';
            }
        }

        // Entregar opciones normalizadas.
        return $options;
    }
}
