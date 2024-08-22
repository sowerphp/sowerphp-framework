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

use \Illuminate\Support\Str;

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
        return sprintf(
            '<input %s />',
            html_attributes($this->attributes)
        );
    }

    /**
     * Renderiza el widget de tipo text.
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
        // En caso de no tener tipo por defecto se asigna tipo button.
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
     * @return string El HTML con el campo Bool renderizado.
     */
    protected function renderBoolWidget(): string
    {
        // Si no se proporciona $this->value, se utiliza por defecto ['No', 'Yes']
        $options = isset($this->value) && is_array($this->value) ? $this->value : ['No', 'Yes'];
        $html = '';
        // Asegura que $options sea un array con exactamente dos elementos
        if (count($options) === 2) {
            foreach ($options as $key => $option) {
                // Verifica que $option sea un string
                if (is_string($option)) {
                    // Determina cuál opción debería estar seleccionada
                    $selected = !empty($this->attributes['value']) && $this->attributes['value'] === $option ? ' selected' : '';
                    $html .= sprintf(
                        '<option value="%s"%s>%s</option>',
                        $key,
                        $selected,
                        htmlspecialchars($option)
                    );
                }
            }
        } else {
            throw new \Exception("Expected exactly 2 options for the boolean widget.");
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
     * @return string El HTML con el campo File renderizado.
     */
    protected function renderFileWidget(): string
    {
        $this->attributes['type'] = 'file';
        return $this->renderDefaultWidget();
    }

    /**
     * Renderiza el widget de tipo files.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderFilesWidget(): string
    {

        return '';
    }

    /**
     * Renderiza el widget de tipo table.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderTableWidget(): string
    {
        return '';
    }

    /**
     * Renderiza el widget de js table.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderJsWidget(): string
    {
        return '';
    }

    /**
     * Renderiza el widget de check table.
     *
     * @return string El HTML con el campo Checkbox renderizado.
     */
    protected function renderCheckboxWidget(): string
    {
        $this->attributes['type'] = 'checkbox';
        $this->attributes['value'] = $this->attributes['value'] ?? $this->value;
        return sprintf(
            '<input %s/> %s',
            html_attributes($this->attributes),
            htmlspecialchars($this->attributes['value']),
        );
    }

    /**
     * Renderiza el widget de checkboxes table.
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
                        '<input type="checkbox" id="%s" name="%s[]" value="%s" %s %s> <label>%s</label><br>',
                        $id,
                        htmlspecialchars($this->name),
                        htmlspecialchars($option['value']),
                        html_attributes($this->attributes),
                        $checked,
                        htmlspecialchars($option['label'])
                    );
                }
            }
        }
        return sprintf('<div>%s</div>', $html);
    }

    /**
     * Renderiza el widget de tablecheck table.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderTableCheckWidget(): string
    {
        return '';
    }

    /**
     * Renderiza el widget de radio.
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
     * Renderiza el widget de tableradios.
     *
     * @return string El HTML renderizado del widget.
     */
    protected function renderTableRadiosWidget(): string
    {
        return '';
    }

}
