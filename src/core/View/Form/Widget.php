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

/**
 * Clase base para todos los widgets de formulario.
 */
class View_Form_Widget implements \ArrayAccess
{

    /**
     * El nombre del widget del formulario.
     *
     * @var string
     */
    protected $name;

    /**
     * El valor del widget del formulario.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Los atributos (u opciones) del widget del formulario.
     *
     * Normalmente serán atributos directos de un elemento de formmulario HTML.
     * Sin embargo, algunos widgets usan opciones más complejas para rederizar
     * los elementos (ej: un widget con múltiples elementos HTML).
     *
     * @var array
     */
    protected $attributes;

    /**
     * Constructor de un widget de formulario.
     *
     * @param string $name El nombre del widget del formulario.
     * @param mixed $value El valor del widget del formulario.
     * @param array $attributes Los atributos (u opciones) del widget del
     * formulario.
     */
    public function __construct(string $name, $value, array $attributes = [])
    {
        $this->name = $name;
        $this->value = $value;
        $this->attributes = $attributes;
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
        $reflection = new \ReflectionClass($this);
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
        $method = 'render' . ucfirst(Str::camel($this->name)) . 'Widget';
        if (!method_exists($this, $method)) {
            return $this->renderDefaultWidget($options);
        }
        return $this->$method($options);
    }

    /**
     * Renderiza el widget predeterminado input del campo del formulario.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderDefaultWidget(): string
    {
        $this->attributes['value'] = $this->attributes['value'] ?? $this->value;
        $this->attributes['required'] = $this->attributes['required'] ?? false;
        return sprintf(
            '<input %s />',
            html_attributes($this->attributes)
        );
    }

    /**
     * Renderiza el widget de tipo text.
     *
     *  El widget de tipo text es el widget por defecto.
     *
     * @return string El HTML con el campo text renderizado.
     */
    protected function renderTextWidget(): string
    {
        return $this->renderDefaultWidget();
    }

    /**
     * Renderiza el widget de tipo date.
     *
     * Renderiza un campo de entrada de fecha.
     *
     * @return string El HTML con el campo date renderizado.
     */
    protected function renderDateWidget(): string
    {
        if (!empty($this->value)) {
            $this->value = substr($this->value, 0, 10);
        }
        $this->attributes['type'] = 'date';
        return $this->renderDefaultWidget();
    }

    /**
     * Renderiza el widget de tipo DatetimeLocal.
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
        $this->attributes['type'] = 'datetime-local';
        return $this->renderDefaultWidget();
    }

    /**
     * Renderiza el widget de tipo Textarea.
     *
     * Renderiza un campo de texto de varias líneas.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderTextareaWidget(): string
    {
        return sprintf(
            '<textarea %s />%s</textarea>',
            html_attributes($this->attributes),
            $this->value
        );
    }

    /**
     * Renderiza el widget de tipo div.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderDivWidget(): string
    {
        return sprintf(
            '<div %s>%s</div>',
            html_attributes($this->attributes),
            $this->value
        );
    }

    /**
     * Renderiza el widget de tipo hidden.
     *
     * @return string El HTML con el campo Hidden renderizado.
     */
    protected function renderHiddenWidget(): string
    {
        $this->attributes['type'] = 'hidden';
        return $this->renderDefaultWidget();
    }

    /**
     * Renderiza el widget de tipo button.
     *
     * @return string El HTML con el campo del Button renderizado.
     */
    protected function renderButtonWidget(): string
    {
        // En caso de no tener tipo por defecto se asigna tipo 'button'.
        $this->attributes['type'] = $this->attributes['type'] ?? 'button';
        return sprintf(
            '<button %s>%s</button>',
            html_attributes($this->attributes),
            $this->value
        );
    }

    /**
     * Renderiza el widget de tipo password.
     *
     * Renderiza un campo de entrada de contraseña con un botón para mostrar/ocultar y copiar la contraseña.
     *
     * @return string El HTML con el campo Password renderizado.
     */
    protected function renderPasswordWidget(): string
    {
        $this->attributes['type'] = 'password';
        return sprintf(
            '<div class="input-group">%s%s%s</div>',
            $this->renderDefaultWidget(),
            '<a class="input-group-text" style="cursor:pointer;text-decoration:none" onclick="Form.showPassword(this)"><i class="fa-regular fa-eye fa-fw"></i></a>',
            '<a class="input-group-text" style="cursor:pointer;text-decoration:none" onclick="__.copy(this.parentNode.querySelector(\'input\').value, \'Copiado.\')"><i class="fa-regular fa-copy fa-fw"></i></a>'
        );
    }

    /**
     * Renderiza el widget de tipo bool.
     *
     * Renderiza un campo de selección de opciones booleanas, por defecto, 0: No y 1: Yes.
     *
     * @return string El HTML con el campo Bool renderizado.
     * @throws \Exception Si no se proporcionan exactamente 2 opciones.
     */
    protected function renderBoolWidget(): string
    {
        // Si no se proporciona $this->value, se utiliza por defecto ['No', 'Yes'].
        $options = $this->value ?? ['No', 'Yes'];
        // Asegura que $options sea un array con exactamente dos elementos.
        if (count($options) !== 2) {
            throw new \Exception("Expected exactly 2 options for the boolean widget.");
        }
        $html = '';
        foreach ($options as $key => $option) {
            // Verifica que $option sea un string.
            if (is_string($option)) {
                // Determina cuál opción debería estar seleccionada.
                $selected = ($this->attributes['value'] ?? '') === $key ? ' selected' : '';
                $html .= sprintf(
                    '<option value="%s"%s>%s</option>',
                    htmlspecialchars($key),
                    $selected,
                    htmlspecialchars($option)
                );
            }
        }
        return sprintf(
            '<select %s>%s</select>',
            html_attributes($this->attributes),
            $html
        );
    }

    /**
     * Renderiza el widget de tipo select.
     *
     * Renderiza un campo de selección de opciones.
     *
     * @return string El HTML con el campo Select renderizado.
     */
    protected function renderSelectWidget(): string
    {
        $html = '';
        $this->value = is_array($this->value) ? $this->value : [];
        if (!empty($this->value)) {
            foreach ($this->value as $option) {
                if (isset($option['value'], $option['label']) && is_string($option['value']) && is_string($option['label'])) {
                    // Verifica si la opción debe estar seleccionada, basada en la clave 'selected'
                    $selected = !empty($option['selected']) ? ' selected' : '';
                    $html .= sprintf(
                        '<option value="%s" %s>%s</option>',
                        htmlspecialchars($option['value']),
                        $selected,
                        htmlspecialchars($option['label'])
                    );
                }
            }
        }
        return sprintf(
            '<select %s>%s</select>',
            html_attributes($this->attributes),
            $html
        );
    }

    /**
     * Renderiza el widget de tipo file.
     *
     * Renderiza una tabla con campos de archivo.
     *
     * @return string El HTML con el campo File renderizado.
     */
    protected function renderFileWidget(): string
    {
        $this->attributes['type'] = 'file';
        $this->attributes['name'] = $this->attributes['name'] ?? $this->name;
        return $this->renderDefaultWidget();
    }

    /**
     * Renderiza el widget de tipo files.
     *
     * Renderiza una tabla con campos de archivo, con la posibilidad de añadir o eliminar filas.
     *
     * @return string El HTML renderizado del widget Files.
     *
     */
    protected function renderFilesWidget(): string
    {
        // Establece el nombre del widget.
        $this->name = 'table_js';
        // Usa 'label' como título si 'title' no está definido.
        $this->attributes['titles'] = (array)($this->attributes['title'] ?? $this->attributes['label']);
        // Configura los atributos del input de archivo, utilizando valores predeterminados si no están definidos.
        $this->attributes['inputs'] = [
            [
                'name' => 'file',
                'attributes' => [
                    'class' => $this->attributes['class'] ?? 'form-control',
                    'multiple' => $this->attributes['multiple'] ?? false,
                ],
            ],
        ];
        // Renderiza el widget con los atributos configurados.
        return $this->render();
    }

    protected function renderTableWidget(): string
    {
        $this->attributes['js'] = false;
        return $this->renderTableJsWidget();
    }

    /**
     * Renderiza el widget de tipo table.
     *
     * Renderiza una tabla con los campos de un formulario.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderTableJsWidget(): string
    {
        // Inicializar atributos con valores por defecto si no están disponibles.
        $this->attributes['titles'] = $this->attributes['titles'] ?? [];
        $this->attributes['table'] = $this->attributes['table'] ?? [];
        $this->attributes['inputs'] = $this->attributes['inputs'] ?? [];
        $this->attributes['width'] = $this->attributes['width'] ?? '100%';
        $this->attributes['accesskey'] = $this->attributes['accesskey'] ?? '+';
        $this->attributes['callback'] = $this->attributes['callback'] ?? 'undefined';
        $this->attributes['cols_width'] = $this->attributes['cols_width'] ?? [];
        $this->attributes['values'] = $this->attributes['values'] ?? null;
        $this->attributes['js'] = $this->attributes['js'] ?? true;
        // Determinar ancho de columnas si no fue indicado.
        if (empty($this->attributes['cols_width'])) {
            $this->attributes['cols_width'] = array_fill(0, count($this->attributes['titles']), '100px');
        }
        // Determinar estilos de columnas, ocultar si es tipo 'hidden'.
        $cols_style = array_map(function ($input) {
            return (!empty($input['attributes']['type']) && $input['attributes']['type'] === 'hidden')
                ? ' style="display:none"'
                : '';
        }, $this->attributes['inputs']);
        // Botón de borrado de la fila.
        $deleteButton = $this->attributes['js']
        ? '<td><a class="' . $this->attributes['id'] . '_eliminar btn btn-danger btn-sm" href="" onclick="Form.delJS(this); return false" title="Eliminar fila"><i class="fa-solid fa-times fa-fw"></i></a></td>'
        : '';
        // Generar inputs
        $inputs = '<tr>';
        // Guardar el estado original de name y attributes.
        $originalName = $this->name;
        $originalAttributes = $this->attributes;
        foreach ($this->attributes['inputs'] as $col_i => $input) {
            // Configurar los atributos y el nombre para el input actual.
            $this->name = $input['name'];
            $this->attributes = $input['attributes'] ?? [];
            // Renderizar el input.
            $renderedInput = $this->render($input);
            // Restaurar el estado original.
            $this->name = $originalName;
            $this->attributes = $originalAttributes;
            // Añadir la celda con el input renderizado.
            $inputs .= '<td' . ($cols_style[$col_i] ?? '') . '>' . rtrim($renderedInput) . '</td>';
        }
        if (!empty($this->attributes['js'])) {
            $inputs .= $deleteButton;
        }
        $inputs .= '</tr>';
        // Determinar valores iniciales.
        $values = '';
        if (!empty($this->attributes['values'])) {
            foreach ($this->attributes['values'] as $value) {
                $values .= '<tr>';
                foreach ($this->attributes['inputs'] as $col_i => $input) {
                    $inputValue = $value[$col_i] ?? '';
                    $input['attributes']['value'] = is_array($inputValue) ? $inputValue['value'] : $inputValue;
                    // Guardar el estado original de name y attributes.
                    $this->name = $input['name'];
                    $this->attributes = $input['attributes'] ?? [];
                    // Renderizar el input.
                    $renderedInput = $this->render($input);
                    // Restaurar el estado original.
                    $this->name = $originalName;
                    $this->attributes = $originalAttributes;
                    // Añadir la celda con el input renderizado.
                    $values .= '<td' . ($cols_style[$col_i] ?? '') . '>' . rtrim($renderedInput) . '</td>';
                }
                if (!empty($this->attributes['js'])) {
                    $values .= $deleteButton;
                }
                $values .= '</tr>';
            }
        } elseif (isset($this->attributes['inputs'][0]['name']) && isset($_POST[$this->attributes['inputs'][0]['name']])) {
            $rows_count = count($_POST[$this->attributes['inputs'][0]['name']]);
            for ($i = 0; $i < $rows_count; $i++) {
                $values .= '<tr>';
                foreach ($this->attributes['inputs'] as $col_i => $input) {
                    $input['attributes']['value'] = $_POST[$input['name']][$i] ?? '';
                    // Configurar los atributos y el nombre para el input actual.
                    $this->name = $input['name'];
                    $this->attributes = $input['attributes'] ?? [];
                    // Renderizar el input.
                    $renderedInput = $this->render($input);
                    // Restaurar el estado original.
                    $this->name = $originalName;
                    $this->attributes = $originalAttributes;
                    // Añadir la celda con el input renderizado.
                    $values .= '<td' . ($cols_style[$col_i] ?? '') . '>' . rtrim($renderedInput) . '</td>';
                }
                if ($this->attributes['js']) {
                    $values .= $deleteButton;
                }
                $values .= '</tr>';
            }
        } else {
            $values = $inputs;
        }
        // Genera tabla.
        $buffer = '';
        if (!empty($this->attributes['js'])) {
            $buffer .= '<script> window["inputsJS_' . $this->attributes['id'] . '"] = ' . json_encode($inputs) . ';</script>';
        }
        $buffer .= '<table class="table table-striped" id="' . $this->attributes['id'] . '" style="width:' . htmlspecialchars($this->attributes['width']) . '">';
        $buffer .= '<thead><tr>';
        foreach ($this->attributes['titles'] as $title) {
            $buffer .= '<th>' . htmlspecialchars($title) . '</th>';
        }
        if ($this->attributes['js']) {
            $buffer .= '<th style="width:1px;"><a href="javascript:Form.addJS(\'' . htmlspecialchars($this->attributes['id']) . '\', undefined, ' . htmlspecialchars($this->attributes['callback']) . ')" title="Agregar fila [' . htmlspecialchars($this->attributes['accesskey']) . ']" accesskey="' . htmlspecialchars($this->attributes['accesskey']) . '" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus fa-fw"></i></a></th>';
        }
        $buffer .= '</tr></thead>';
        $buffer .= '<tbody>' . $values . '</tbody>';
        $buffer .= '</table>';
        return $buffer;
    }

    /**
     * Renderiza el widget de checkbox.
     *
     * Renderiza un campo de checkbox.
     *
     * @return string El HTML con el campo Checkbox renderizado.
     */
    protected function renderCheckboxWidget(): string
    {
        // Establecer el tipo de input como checkbox.
        $this->attributes['type'] = 'checkbox';
        // Asegurarse de que el atributo 'name' esté presente.
        $this->attributes['name'] = $this->attributes['name'] ?? $this->name;
        // Establecer el valor si no está ya establecido en los atributos.
        $this->attributes['value'] = $this->attributes['value'] ?? $this->value;
        return sprintf(
            '<input %s/> %s',
            html_attributes($this->attributes),
            htmlspecialchars($this->attributes['value']),
        );
    }

    /**
     * Renderiza el widget de checkboxes.
     *
     * Renderiza campos con multiples checkbox.
     *
     * @return string El HTML con el campo Checkboxes múltiples renderizado.
     */
    protected function renderCheckboxesWidget(): string
    {
        $html = '';
        $this->value = is_array($this->value) ? $this->value : [];
        if (!empty($this->value)) {
            foreach ($this->value as $index => $option) {
                if (isset($option['value'], $option['label']) && is_string($option['value']) && is_string($option['label'])) {
                    $checked = !empty($option['checked']) ? ' checked' : '';
                    $id = htmlspecialchars($option['id'] ?? $this->name . '_' . $index);
                    $html .= sprintf(
                        '<input type="checkbox" id="%s" name="%s[]" value="%s" %s %s> <label for=%s>%s</label><br>',
                        $id,
                        htmlspecialchars($this->name),
                        htmlspecialchars($option['value']),
                        html_attributes($this->attributes),
                        $checked,
                        $id,
                        htmlspecialchars($option['label'])
                    );
                }
            }
        }
        return sprintf('<div>%s</div>', $html);
    }

    /**
     * Renderiza el widget de TableCheck.
     *
     * Renderiza una tabla de checkbox.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderTableCheckWidget(): string
    {
        // Si la tabla no tiene datos, devuelve un '-'.
        if (empty($this->attributes['table'])) {
            return '-';
        }
        // Configuración por defecto.
        $this->attributes['id'] = $this->attributes['id'] ?? $this->attributes['name'];
        $this->attributes['titles'] = $this->attributes['titles'] ?? [];
        $this->attributes['width'] = $this->attributes['width'] ?? '100%';
        $this->attributes['mastercheck'] = $this->attributes['mastercheck'] ?? false;
        $this->attributes['checked'] = $this->attributes['checked'] ?? ($_POST[$this->attributes['name']] ?? []);
        $this->attributes['display-key'] = $this->attributes['display-key'] ?? true;
        // Determinar la clave primaria.
        if (!isset($this->attributes['key'])) {
            $this->attributes['key'] = array_keys($this->attributes['table'][0])[0];
        }
        if (!is_array($this->attributes['key'])) {
            $this->attributes['key'] = [$this->attributes['key']];
        }
        // Iniciar el buffer para la tabla.
        $buffer = sprintf(
            '<table id="%s" class="table table-striped" style="width:%s">',
            htmlspecialchars($this->attributes['id']),
            htmlspecialchars($this->attributes['width'])
        );
        // Generar el encabezado de la tabla.
        $buffer .= '<thead><tr>';
        foreach ($this->attributes['titles'] as $title) {
            $buffer .= sprintf('<th>%s</th>', htmlspecialchars($title));
        }
        // Checkbox maestro en el encabezado.
        $checked = $this->attributes['mastercheck'] ? ' checked="checked"' : '';
        $buffer .= sprintf(
            '<th><input type="checkbox"%s onclick="Form.checkboxesSet(\'%s\', this.checked)" /></th>',
            $checked,
            htmlspecialchars($this->attributes['name'])
        );
        $buffer .= '</tr></thead><tbody>';
        // Número de claves primarias.
        $n_keys = count($this->attributes['key']);
        // Generar filas de la tabla.
        foreach ($this->attributes['table'] as $row) {
            $key = [];
            foreach ($this->attributes['key'] as $k) {
                $key[] = $row[$k];
            }
            $key = implode(';', $key);
            // Agregar fila.
            $buffer .= '<tr>';
            $count = 0;
            foreach ($row as $col) {
                if ($this->attributes['display-key'] || $count >= $n_keys) {
                    $buffer .= sprintf('<td>%s</td>', htmlspecialchars($col));
                }
                $count++;
            }
            // Checkbox en cada fila.
            $checked = (in_array($key, $this->attributes['checked']) || $this->attributes['mastercheck']) ? ' checked="checked"' : '';
            $buffer .= sprintf(
                '<td style="width:1px"><input type="checkbox" name="%s[]" value="%s"%s /></td>',
                htmlspecialchars($this->attributes['name']),
                htmlspecialchars($key),
                $checked
            );
            $buffer .= '</tr>';
        }
        // Cerrar la tabla.
        $buffer .= '</tbody></table>';
        return $buffer;
    }

    /**
     * Renderiza el widget de radio.
     *
     * Renderiza un campo de selección de radio.
     *
     * @return string El HTML con el campo Radio renderizado.
     */
    protected function renderRadioWidget(): string
    {
        {
            $html = '';
            $this->value = is_array($this->value) ? $this->value : [];
            if (!empty($this->value)) {
                foreach ($this->value as $index => $option) {
                    if (isset($option['value'], $option['label']) && is_string($option['value']) && is_string($option['label'])) {
                        $checked = !empty($option['checked']) ? ' checked' : '';
                        $id = htmlspecialchars($option['id'] ?? $this->name . '_' . $index);
                        $html .= sprintf(
                            '<input type="radio" id="%s" name="%s" value="%s"%s> <label>%s</label><br>',
                            $id,
                            htmlspecialchars($this->name),
                            htmlspecialchars($option['value']),
                            $checked,
                            htmlspecialchars($option['label'])
                        );
                    }
                }
            }
            return sprintf('<div>%s</div>', $html);
        }
    }

    /**
     * Renderiza el widget de TableRadios.
     *
     * Renderiza una tabla con campos de radio.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderTableRadioWidget(): string
    {
        // Configuración por defecto.
        $this->attributes['id'] = $this->attributes['id'] ?? $this->attributes['name'];
        $this->attributes['options'] = $this->attributes['options'] ?? [];
        $this->attributes['titles'] = $this->attributes['titles'] ?? [];
        $this->attributes['table'] = $this->attributes['table'] ?? [];
        $this->attributes['width'] = $this->attributes['width'] ?? '100%';
        // Iniciar el buffer para la tabla.
        $buffer = sprintf(
            '<table id="%s" class="table table-striped" style="width:%s">',
            htmlspecialchars($this->attributes['id']),
            htmlspecialchars($this->attributes['width'])
        );
        // Generar el encabezado de la tabla.
        $buffer .= '<thead><tr>';
        foreach ($this->attributes['titles'] as $title) {
            $buffer .= sprintf('<th>%s</th>', htmlspecialchars($title));
        }
        $buffer .= '</tr></thead><tbody>';
        // Obtener las claves de las opciones.
        foreach ($this->attributes['table'] as $key => $row) {
            // Saltar la fila si faltan datos esenciales.
            if (!isset($row['name'], $row['description'])) {
                continue;
            }
            $buffer .= '<tr>';
            $buffer .= sprintf('<td>%s</td>', htmlspecialchars($row['name']));
            $buffer .= sprintf('<td>%s</td>', htmlspecialchars($row['description']));
            foreach ($this->attributes['options'] as $value => $label) {
                $inputName = sprintf('%s_%s', $row['name'], $key);
                $isChecked = isset($_POST[$inputName]) && $_POST[$inputName] == $value;
                $checked = $isChecked ? 'checked="checked"' : '';
                $buffer .= sprintf(
                    '<td><input type="radio" name="%s" value="%s" %s /></td>',
                    htmlspecialchars($inputName),
                    htmlspecialchars($value),
                    $checked,
                    htmlspecialchars($label)
                );
            }
            $buffer .= '</tr>';
        }
        // Cerrar la tabla.
        $buffer .= '</tbody></table>';
        return $buffer;
    }

}
