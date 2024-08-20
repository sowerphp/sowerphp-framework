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
     * Sin embargo, algunos widgets usan opciones más complehas para rederizar
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

    protected function renderDefaultWidget(): string
    {
        $this->attributes['value'] = $this->attributes['value'] ?? $this->value;
        return sprintf(
            '<input %s />',
            html_attributes($this->attributes)
        );
    }

    protected function renderDateWidget(): string
    {
        if (!empty($this->value)) {
            $this->value = substr($this->value, 0, 10);
        }
        $this->attributes['type'] = 'date';
        return $this->renderDefaultWidget();
    }

    protected function renderDatetimeLocalWidget(): string
    {
        if (!empty($this->value)) {
            $this->value = substr($this->value, 0, 16);
        }
        $this->attributes['type'] = 'datetime-local';
        return $this->renderDefaultWidget();
    }

    protected function renderTextareaWidget(): string
    {
        return sprintf(
            '<textarea %s />%s</textarea>',
            html_attributes($this->attributes),
            $this->value
        );
    }

    protected function renderDivWidget(): string
    {
        return '';
    }

    protected function renderHiddenWidget(): string
    {
        return '';
    }

    protected function renderButtonWidget(): string
    {
        return '';
    }

    protected function renderTextWidget(): string
    {
        return $this->renderDefaultWidget();
    }

    protected function renderPasswordWidget(): string
    {
        return '';
    }

    protected function renderBoolWidget(): string
    {
        return '';
    }

    protected function renderSelectWidget(): string
    {
        return '';
    }

    protected function renderFileWidget(): string
    {
        return '';
    }

    protected function renderFilesWidget(): string
    {
        return '';
    }

    protected function renderTableWidget(): string
    {
        return '';
    }

    protected function renderJsWidget(): string
    {
        return '';
    }

    protected function renderCheckboxWidget(): string
    {
        return '';
    }

    protected function renderCheckBoxesWidget(): string
    {
        return '';
    }

    protected function renderTableCheckWidget(): string
    {
        return '';
    }

    protected function renderRadiosWidget(): string
    {
        return '';
    }

    protected function renderTableRadiosWidget(): string
    {
        return '';
    }

}
