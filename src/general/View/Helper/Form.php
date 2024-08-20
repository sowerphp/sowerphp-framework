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

namespace sowerphp\general;

/**
 * Helper para la creación de formularios en HTML
 */
class View_Helper_Form
{

    private $_style; ///< Formato del formulario que se renderizará (mantenedor u false)
    private $_cols_label; ///< Columnas de la grilla para la etiqueta

    /**
     * Método que inicia el código del formulario
     * @param style Estilo del formulario que se renderizará
     * @param cols_label Cantidad de columnas de la grilla para la etiqueta
     */
    public function __construct($style = 'horizontal', $cols_label = 2)
    {
        $this->_style = $style;
        $this->_cols_label = $cols_label;
    }

    /**
     * Método para asignar el estilo del formulario una vez ya se creo el objeto
     * @param style Estilo del formulario que se renderizará
     */
    public function setStyle($style = false)
    {
        $this->_style = $style;
    }

    /**
     * Método para asignar la cantidad de columnas de la grilla para la etiqueta
     * @param cols_label Cantidad de columnas de la grilla para la etiqueta
     */
    public function setColsLabel($cols_label = 2)
    {
        $this->_cols_label = $cols_label;
    }

    /**
     * Método que inicia el código del formulario
     * @param config Arreglo con la configuración para el formulario
     * @return string Código HTML de lo solicitado
     */
    public function begin($config = [])
    {
        // transformar a arreglo en caso que no lo sea
        if (!is_array($config)) {
            $config = ['action'=>$config];
        }
        // Asignar configuración.
        $config = array_merge(
            [
                'id' => 'formulario',
                'action' => $_SERVER['REQUEST_URI'],
                'method'=> 'post',
                'onsubmit' => null,
                'focus' => null,
                'attr' => '',
            ], $config
        );
        // crear onsubmit
        if ($config['onsubmit']) {
            $config['onsubmit'] = ' onsubmit="return '.$config['onsubmit'].'"';
        }
        // crear buffer
        $buffer = '';
        // si hay focus se usa
        if ($config['focus']) {
            $buffer .= '<script> $(function() { $("#'.$config['focus'].'").focus(); }); </script>'."\n";
        }
        // agregar formulario
        $class = $this->_style ? 'form-'.$this->_style : '';
        $buffer .= "\n".'<form action="'.$config['action'].'" method="'.$config['method'].'" enctype="multipart/form-data"'.$config['onsubmit'].' id="'.$config['id'].'" '.$config['attr'].' class="needs-validation '.$class.'" role="form">'."\n";
        // retornar
        return $buffer;
    }

    /**
     * Método que termina el código del formulario
     * @param config Arreglo con la configuración para el botón submit
     * @return string Código HTML de lo solicitado
     */
    public function end($config = [])
    {
        // solo se procesa la configuración si no es falsa
        if ($config!==false) {
            // transformar a arreglo en caso que no lo sea
            if (!is_array($config)) {
                $config = ['value'=>$config];
            }
            // Asignar configuración.
            $config['type'] = 'submit';
            $config = array_merge([
                'type' => 'submit',
                'value' => 'Enviar',
            ], $config, [
                'label' => null,
                'name' => null,
            ]);
            // generar fin del formulario
            return $this->input($config).'</form>'."\n\n";
        } else {
            return '</form>'."\n\n";
        }
    }

    /**
     * Método para crear una nuevo campo para que un usuario ingrese
     * datos a través del formulario, ya sea un tag: input, select, etc
     * @param config Arreglo con la configuración para el elemento
     * @return string Código HTML de lo solicitado
     */
    public function input($config)
    {
        // transformar a arreglo en caso que no lo sea
        if (!is_array($config)) {
            $config = array('name'=>$config, 'label'=>$config);
        }
        // Asignar configuración.
        $config = array_merge(
            array(
                'type' => 'text',
                'default' => null,
                'value' => null,
                'autoValue' => false,
                'class' => '',
                'attr' => '',
                'check' => null,
                'help' => '',
                'popover' => '',
                'notempty' => false,
                'style' => $this->_style,
                'placeholder' => '',
                'sanitize' => true,
            ), $config
        );
        if (!isset($config['default']) && isset($config['value'])) {
            $config['default'] = $config['value'];
        }
        if (!isset($config['name']) && isset($config['id'])) {
            $config['name'] = $config['id'];
        }
        // si no se indicó un valor y existe uno por POST se usa
        if (!isset($config['value'][0]) && isset($config['name']) && isset($_POST[$config['name']])) {
            $config['value'] = $_POST[$config['name']];
        }
        // si label no existe se usa el nombre de la variable
        if (!isset($config['label'])) {
            $config['label'] = isset($config['placeholder'][0]) ? $config['placeholder'] : $config['name'];
        }
        // si se paso check se usa
        if ($config['check']) {
            // si no es arreglo se convierte
            if (!is_array($config['check'])) {
                $config['check'] = explode(' ',$config['check']);
            }
            // hacer implode, agregar check y meter al class
            $config['class'] = $config['class'].' check '.implode(' ', $config['check']);
            if (in_array('notempty', $config['check'])) {
                $config['notempty'] = true;
            }
        }
        // asignar class
        if (!in_array($config['type'], ['submit', 'checkbox', 'checkboxes', 'file', 'div'])) {
            $config['class'] = (!empty($config['class']) ? $config['class'] : '').' form-control';
        }
        // asignar id si no se asignó
        if (!isset($config['id']) && !empty($config['name']) && substr($config['name'], -2)!='[]') {
            $config['id'] = $config['name'].'Field';
        }
        // determinar popover
        if ($config['popover']!='') {
            $config['popover'] = ' data-bs-toggle="popover" data-bs-trigger="focus" title="'.$config['label'].'" data-bs-placement="top" data-bs-content="'.$config['popover'].'" onmouseover="$(this).popover(\'show\')" onmouseout="$(this).popover(\'hide\')"';
        }
        // limpiar valor del campo
        if ($config['type']!='div' && $config['sanitize'] && isset($config['value'][0]) && !is_array($config['value'])) {
            $config['value'] = trim(strip_tags($config['value']));
            if (!in_array($config['type'], ['submit', 'button'])) {
                $config['value'] = htmlentities($config['value']);
            }
        }
        // generar campo, formatear y entregar
        return $this->_format($this->{'_input_'.$config['type']}($config), $config);
    }

    /**
     * Método que aplica o no un diseño al campo
     * @param field Campo que se desea formatear
     * @param config Arreglo con la configuración para el elemento
     * @return string Código HTML de lo solicitado
     */
    private function _format($field, $config)
    {
        if ($config['help'] != '') {
            $config['help'] = ' <div class="form-text text-muted"'.(isset($config['id'])?' id="'.$config['id'].'Help"':'').'>'.$config['help'].'</div>';
        }
        // si es campo oculto no se aplica ningún estilo
        if ($config['type'] == 'hidden') {
            $buffer = '    '.$field."\n";
        }
        // si se debe aplicar estilo horizontal
        else if ($config['style']=='horizontal') {
            $buffer = '    <div class="mb-3 row'.($config['notempty']?' required':'').'">'."\n";
            if (!empty($config['label'])) {
                $required = $config['notempty'] ? '<span style="color:red"><strong>*</strong></span> ' : '';
                $buffer .= '        <label'.(isset($config['id'])?(' for="'.$config['id'].'"'):'').' class="col-sm-'.$this->_cols_label.' col-form-label text-end">'.$required.$config['label'].'</label>'."\n";
            }
            if (!in_array($config['type'], ['submit'])) {
                $buffer .= '        <div class="col-sm-'.(12-$this->_cols_label).'">'.$field.$config['help'].'</div>'."\n";
            } else {
                $buffer .= '        <div class="mt-2 offset-sm-'.$this->_cols_label.' col-sm-'.(12-$this->_cols_label).'">'.$field.$config['help'].'</div>'."\n";
            }
            $buffer .= '    </div>'."\n";
        }
        // si se debe aplicar estilo inline
        else if ($config['style']=='inline') {
            $buffer = '<div>';
            if ($config['type'] != 'checkbox') {
                $buffer .= '<label class="visually-hidden"'.(isset($config['id'])?' for="'.$config['id'].'"':'').'>'.$config['label'].'</label>'."\n";
            }
            if (isset($config['addon-icon'])) {
                $buffer .= '<div class="input-group-addon"><i class="fa fa-'.$config['addon-icon'].'" aria-hidden="true"></i></div>'."\n";
            } else if (isset($config['addon-text'])) {
                $buffer .= '<div class="input-group-addon">'.$config['addon-text'].'</div>'."\n";
            }
            $buffer .= $field;
            if ($config['type']=='checkbox') {
                $buffer .= ' <label '.(isset($config['id'])?' for="'.$config['id'].'"':'').' style="fw-:normal"'.$config['popover'].'>'.$config['label'].'</label>'."\n";
            }
            $buffer .= $config['help'];
            $buffer .= '</div>'."\n";
        }
        // si se debe alinear
        else if (isset($config['align'])) {
            $width = !empty($config['width']) ? (';width:'.$config['width']) : '';
            $buffer = '<div style="text-align:'.$config['align'].$width.'">'.$field.$config['help'].'</div>'."\n";
        }
        // si se debe colocar un label
        else if (!empty($config['id']) && !empty($config['label'])) {
            $width = !empty($config['width']) ? (' style="width:'.$config['width'].'"') : '';
            $buffer = '<div'.$width.'><label class="visually-hidden" for="'.$config['id'].'">'.$config['label'].'</label>'.$field.$config['help'].'</div>'."\n";
        }
        // si no se debe aplicar ningún formato solo agregar el campo dentro de un div y el EOL
        else {
            $width = !empty($config['width']) ? (' style="width:'.$config['width'].'"') : '';
            $buffer = '<div'.$width.'>'.$field.$config['help'].'</div>'."\n";
        }
        // retornar código formateado
        return $buffer;
    }

    private function _input_div($config)
    {
        return '<div'.(!empty($config['attr'])?(' '.$config['attr']):'').(!empty($config['id'])?(' id="'.$config['id'].'"'):'').' class="form-control-plaintext '.$config['class'].'">'.$config['value'].'</div>';
    }

    private function _input_hidden($config)
    {
        $id = isset($config['id']) ? ' id="'.$config['id'].'"' : '';
        return '<input type="hidden" name="'.$config['name'].'" value="'.$config['value'].'"'.$id.' />';
    }

    private function _input_submit($config)
    {
        return $this->_input_button($config);
    }

    private function _input_button($config)
    {
        $id = isset($config['id']) ? ' id="'.$config['id'].'"' : '';
        return '<button type="'.$config['type'].'" name="'.$config['name'].'"'.$id.' class="'.$config['class'].' btn btn-primary" '.$config['attr'].'>'.$config['value'].'</button>';
    }

    private function _input_text($config)
    {
        $id = isset($config['id']) ? ' id="'.$config['id'].'"' : '';
        $growup = !empty($config['growup']) ? 'ondblclick="Form.growup(this)"' : '';
        $autocomplete = (isset($config['autocomplete']) && $config['autocomplete']===false) ? 'autocomplete="off"' : '';
        return '<input type="text" name="'.$config['name'].'" value="'.$config['value'].'"'.$id.' class="'.$config['class'].'" placeholder="'.$config['placeholder'].'" '.$config['attr'].$config['popover'].' '.$growup.' '.$autocomplete.' />';
    }

    private function _input_password($config)
    {
        // crear botón para mostrar si es necesario
        if (!isset($config['showPassword'])) {
            $config['showPassword'] = true;
        }
        $button_show = $config['showPassword'] ? '<a class="input-group-text" style="cursor:pointer;text-decoration:none" onclick="Form.showPassword(this)"><i class="fa-regular fa-eye fa-fw"></i></a>' : '';
        // crear botón para copiar si es necesario
        if (!isset($config['copyPassword'])) {
            $config['copyPassword'] = true;
        }
        $button_copy = $config['copyPassword'] ? '<a class="input-group-text" style="cursor:pointer;text-decoration:none" onclick="__.copy(this.parentNode.querySelector(\'input\').value, \'Valor del campo &quot;'.$config['label'].'\&quot; copiado.\')"><i class="fa-regular fa-copy fa-fw"></i></a>' : '';
        // entregar password siempre en un input-group
        return '<div class="input-group"><input type="password" name="'.$config['name'].'" value="'.$config['value'].'" class="'.$config['class'].'" placeholder="'.$config['placeholder'].'" '.$config['attr'].' '.$config['popover'].' '.(!empty($config['id']) ? 'id="'.$config['id'].'"' : '').' '.(empty($config['autocomplete']) ? 'autocomplete="off"' : '').' />'.$button_show.$button_copy.'</div>';
    }

    private function _input_date($config)
    {
        $datepicker_config = !empty($config['datepicker'])
            ? str_replace('"', '\'', json_encode($config['datepicker']))
            : '{}'
        ;
        $buffer = '';
        $attr = '';
        if (isset($config['id'])) {
            $attr .= ' id="'.$config['id'].'"';
        }
        $attr .= ' data-datepicker-config="'.$datepicker_config.'"';
        $buffer .= '<input type="text" name="'.$config['name'].'" value="'.$config['value'].'"'.$attr.' class="'.$config['class'].'" placeholder="'.$config['placeholder'].'" '.$config['attr'].$config['popover'].' autocomplete="off" />';
        return $buffer;
    }

    private function _input_textarea($config)
    {
        $config = array_merge(
            array(
                'rows'=>5,
                'cols'=>10
            ), $config
        );
        $id = isset($config['id']) ? ' id="'.$config['id'].'"' : '';
        $growup = !empty($config['growup']) ? 'ondblclick="Form.growup(this)"' : '';
        return '<textarea name="'.$config['name'].'" rows="'.$config['rows'].'" cols="'.$config['cols'].'"'.$id.' class="'.$config['class'].'" placeholder="'.$config['placeholder'].'" '.$config['attr'].$config['popover'].' '.$growup.'>'.$config['value'].'</textarea>';
    }

    private function _input_boolean($config)
    {
        if (empty($config['options'])) {
            $config['options'] = ['No', 'Si'];
        }
        return $this->_input_select($config);
    }

    private function _input_select($config)
    {
        $form_select_wrapper = config('app.ui.form.select.wrapper', 'select2');
        $config = array_merge([
            'wrapper' => $form_select_wrapper,
            'auto_options' => true,
            'options' => [],
        ], $config);
        // configuración para los wrappers
        $wrapper_config = '';
        if ($config['wrapper'] == 'select2') {
            $config['select2'] = array_merge(
                ['theme' => 'bootstrap-5', 'width' => '100%'],
                isset($config['select2']) ? $config['select2'] : []
            );
            if (empty($config['select2']['placeholder']) && !empty($config['placeholder'])) {
                $config['select2']['placeholder'] = $config['placeholder'];
            }
            if (!empty($config['select2']['dropdownParent'])) {
                $dropdownParent = $config['select2']['dropdownParent'];
                $config['select2']['dropdownParent'] = '###dropdownParent###';
            }
            $wrapper_config = str_replace('"', '\'', json_encode($config['select2']));
            if (!empty($dropdownParent)) {
                $wrapper_config = str_replace('\'###dropdownParent###\'', '$(\''.$dropdownParent.'\')', $wrapper_config);
            }
        }
        // generar campo select
        if (isset($config['multiple'])) {
            $multiple = ' multiple="multiple" size="'.$config['multiple'].'"';
            $config['name'] .= '[]';
        } else {
            $multiple = '';
        }
        $buffer = '';
        $attr = '';
        if (!empty($config['id'])) {
            $attr .= ' id="'.$config['id'].'"';
            if (!empty($config['wrapper'])) {
                $buffer .= '<script>$(function() { $("#'.$config['id'].'").'.$config['wrapper'].'('.$wrapper_config.'); }); </script>';
            }
        } else {
            if (!empty($config['wrapper'])) {
                $attr .= ' data-wrapper-method="'.$config['wrapper'].'" data-wrapper-config="'.$wrapper_config.'"';
            }
        }
        if (!empty($config['onblur'])) {
            if ($config['wrapper'] == 'select2') {
                if (!empty($config['id'])) {
                    $buffer .= '<script>$(function() { $("#'.$config['id'].'").on("select2:close", function(){ '.$config['onblur'].'; }); });</script>';
                } else {
                    $attr .= ' data-wrapper-onblur="'.str_replace('"', '\"', $config['onblur']).'"';
                }
            } else {
                $attr .= ' onblur="'.$config['onblur'].'"';
            }
        }
        if (!is_array($config['value'])) {
            $config['value'] = ($config['value'] || (string)$config['value']=='0') ? [$config['value']] : [];
        }
        if (!empty($config['value'])) {
            $any_option_selected = true;
            $config['value'] = array_map('strval', $config['value']);
            $keys_not_in_options = $config['value'];
        } else {
            $selected = '';
        }
        $buffer .= '<select name="'.$config['name'].'"'.$attr.' class="'.$config['class'].'"'.$multiple.' '.$config['attr'].'>';
        foreach ($config['options'] as $key => $value) {
            // los options no están agrupados
            if (empty($config['groups'])) {
                if (is_array($value)) {
                    $key = array_shift($value);
                    $value = array_shift($value);
                }
                if (!empty($any_option_selected)) {
                    $selected = (in_array((string)$key, $config['value'], true)?' selected="selected"':'');
                    if (!empty($selected)) {
                        $keys_not_in_options = array_filter($keys_not_in_options, function ($k) use ($key) {
                            return (string)$k != (string)$key;
                        });
                    }
                }
                $buffer .= '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';
            }
            // los options están agrupados usando optgroup
            else {
                $buffer .= '<optgroup label="'.$key.'">';
                foreach ($value as $key2 => $value2) {
                    if (is_array($value2)) {
                        $key2 = array_shift($value2);
                        $value2 = array_shift($value2);
                    }
                    if (!empty($any_option_selected)) {
                        $selected = (in_array((string)$key2, $config['value'], true)?' selected="selected"':'');
                        if (!empty($selected)) {
                            $keys_not_in_options = array_filter($keys_not_in_options, function ($k) use ($key2) {
                                return (string)$k != (string)$key2;
                            });
                        }
                    }
                    $buffer .= '<option value="'.$key2.'"'.$selected.'>'.$value2.'</option>';
                }
                $buffer .= '</optgroup>';
            }
        }
        // si al final tenemos elementos en los valores del select
        // significa que venía alguno que no estaba en las opciones
        // se agrega como option para poder ser mostrado en el select
        // no se agregan llaves vacias o numéricos menores a 0
        if ($config['auto_options'] && !empty($keys_not_in_options)) {
            $keys_not_in_options = array_filter($keys_not_in_options, function ($k) {
                if (empty($k) || (is_numeric($k) && $k < 0)) {
                    return false;
                }
                return true;
            });
            if (!empty($keys_not_in_options)) {
                $values = [
                    'null' => 'Sin valor',
                    '!null' => 'Con valor',
                ];
                $buffer .= '<optgroup label="Valores temporales">';
                foreach($keys_not_in_options as $k) {
                    $buffer .= '<option value="'.$k.'" selected="selected">'.(isset($values[$k])?$values[$k]:$k).'</option>';
                }
                $buffer .= '</optgroup>';
            }
        }
        $buffer .= '</select>';
        return $buffer;
    }

    private function _input_file($config)
    {
        $id = isset($config['id']) ? ' id="'.$config['id'].'"' : '';
        return '<input type="file" name="'.$config['name'].'"'.$id.' class="form-control" '.$config['attr'].'/>';
    }

    private function _input_files($config)
    {
        if (empty($config['title'])) {
            $config['title'] = $config['label'];
        }
        return $this->_input_js([
            'id' => $config['id'],
            'label' => $config['label'],
            'titles' => [$config['title']],
            'inputs' => [
                ['type'=>'file', 'name'=>$config['name']],
            ]
        ]);
    }

    private function _input_table($config)
    {
        return $this->_input_js($config, false);
    }

    private function _input_js($config, $js = true)
    {
        // configuración por defecto
        $config = array_merge([
            'titles' => [],
            'inputs' => [],
            'width' => '100%',
            'accesskey' => '+',
            'callback' => 'undefined'
        ], $config);
        // respaldar formato
        $formato = $this->_style;
        $this->_style = null;
        // determinar ancho de columnas si no fue indicado
        // se busca en el arreglo de títulos por si viene en el título como arreglo
        if (empty($config['cols_width'])) {
            $config['cols_width'] = [];
            $titles = [];
            foreach ($config['titles'] as $title_ori) {
                if (is_array($title_ori)) {
                    list($title, $width) = $title_ori;
                    if (is_numeric($width)) {
                        $width = ((string)$width).'px';
                    }
                    $config['cols_width'][] = $width;
                    $titles[] = $title;
                } else {
                    $config['cols_width'][] = null;
                    $titles[] = $title_ori;
                }
            }
            $config['titles'] = $titles;
        }
        // determinar estilos de columnas
        $cols_style = [];
        $col_i = 0;
        foreach ($config['inputs'] as $input) {
            $style = [];
            if (isset($input['type']) && $input['type']=='hidden') {
                $style[] = 'display:none';
            }
            $cols_style[] = !empty($style) ? (' style="'.implode(';',$style).'"') : '';
            $col_i++;
        }
        // botón de borrado de la fila
        $delete = '<td><a class="'.$config['id'].'_eliminar btn btn-danger btn-sm" href="" onclick="Form.delJS(this); return false" title="Eliminar fila"><i class="fa-solid fa-times fa-fw"></i></a></td>';
        // determinar inputs
        $inputs = '<tr>';
        $col_i = 0;
        foreach ($config['inputs'] as $input) {
            $input['name'] = $input['name'].'[]';
            if (!empty($config['cols_width'][$col_i])) {
                $input['width'] = $config['cols_width'][$col_i];
            }
            $inputs .= '<td'.(isset($cols_style[$col_i])?$cols_style[$col_i]:'').'>'.rtrim($this->input($input)).'</td>';
            $col_i++;
        }
        if ($js) {
            $inputs .= $delete;
        }
        $inputs .= '</tr>';
        // si no se indicaron valores se tratan de determinar
        if (!isset($config['values'])) {
            if (isset($config['inputs'][0]) && isset($config['inputs'][0]['name']) && isset($_POST[$config['inputs'][0]['name']])) {
                $values = '';
                $filas = count($_POST[$config['inputs'][0]['name']]);
                for ($i=0; $i<$filas; $i++) {
                    $values .= '<tr>';
                    $col_i = 0;
                    foreach ($config['inputs'] as $input) {
                        $input['value'] = isset($_POST[$input['name']]) ? $_POST[$input['name']][$i] : '';
                        $input['name'] = $input['name'].'[]';
                        if (!empty($config['cols_width'][$col_i])) {
                            $input['width'] = $config['cols_width'][$col_i];
                        }
                        $values .= '<td'.(isset($cols_style[$col_i])?$cols_style[$col_i]:'').'>'.rtrim($this->input($input)).'</td>';
                        $col_i++;
                    }
                    if ($js) {
                        $values .= $delete;
                    }
                    $values .= '</tr>';
                }
            }
            // si no hay valores por post se crea una fila con los campos vacíos
            else {
                $values = $inputs;
            }
        }
        // en caso que se cree el formulario con valores por defecto ya asignados
        else {
            $values = '';
            foreach ($config['values'] as $value) {
                $values .= '<tr>';
                $col_i = 0;
                foreach ($config['inputs'] as $input) {
                    if (!isset($value[$input['name']])) {
                        $value[$input['name']] = '';
                    }
                    if (!is_array($value[$input['name']])) {
                        $value[$input['name']] = ['value'=>$value[$input['name']]];
                    }
                    $input = array_merge($input, $value[$input['name']]);
                    if (isset($input['type']) && $input['type']=='checkbox') {
                        $input['checked'] = $input['value'];
                        unset($input['value']);
                    }
                    $input['name'] = $input['name'].'[]';
                    if (!empty($config['cols_width'][$col_i])) {
                        $input['width'] = $config['cols_width'][$col_i];
                    }
                    $values .= '<td'.(isset($cols_style[$col_i])?$cols_style[$col_i]:'').'>'.rtrim($this->input($input)).'</td>';
                    $col_i++;
                }
                if ($js) {
                    $values .= $delete;
                }
                $values .= '</tr>';
            }
        }
        // restaurar formato
        $this->_style = $formato;
        // generar tabla
        $buffer = '';
        if ($js) {
            $buffer .= '<script> window["inputsJS_'.$config['id'].'"] = \''.str_replace('\'', '\\\'', $inputs).'\'; </script>';
        }
        $buffer .= '<table class="table table-striped" id="'.$config['id'].'" style="width:'.$config['width'].'">';
        $buffer .= '<thead><tr>';
        foreach ($config['titles'] as $title) {
            $buffer .= '<th>'.$title.'</th>';
        }
        if ($js) {
            $buffer .= '<th style="width:1px;"><a href="javascript:Form.addJS(\''.$config['id'].'\', undefined, '.$config['callback'].')" title="Agregar fila ['.$config['accesskey'].']" accesskey="'.$config['accesskey'].'" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus fa-fw"></i></a></th>';
        }
        $buffer .= '</tr></thead>';
        $buffer .= '<tbody>'.$values.'</tbody>';
        $buffer .= '</table>';
        return $buffer;
    }

    private function _input_checkbox($config)
    {
        if (!isset($config['checked']) && isset($_POST[$config['name']])) {
            $config['checked'] = true;
        }
        $id = isset($config['id']) ? ' id="'.$config['id'].'"' : '';
        $checked = isset($config['checked']) && $config['checked'] ? ' checked="checked"' : '';
        return '<input type="checkbox" name="'.$config['name'].'" value="'.$config['value'].'"'.$id.$checked.' class="'.$config['class'].'" '.$config['attr'].' />';
    }

    /**
     * @todo No se está utilizando checked
     */
    private function _input_checkboxes($config)
    {
        $buffer = '';
        $config = array_merge([
            'options' => [],
        ], $config);
        $buffer_checkboxes = [];
        foreach ($config['options'] as $key => &$value) {
            if (is_array($value)) {
                $key = array_shift($value);
                $value = array_shift($value);
            }
            $buffer_checkboxes[] = '<input type="checkbox" name="'.$config['name'].'[]" value="'.$key.'" class="'.$config['class'].'" '.$config['attr'].'/> '.$value;
        }
        return implode('<br/>', $buffer_checkboxes);
    }

    private function _input_tablecheck($config)
    {
        if(!isset($config['table'][0])) {
            return '-';
        }
        // configuración por defecto
        $config = array_merge([
            'id' => $config['name'],
            'titles' => [],
            'width' => '100%',
            'mastercheck' => false,
            'checked' => (isset($_POST[$config['name']])?$_POST[$config['name']]:[]),
            'display-key' => true,
        ], $config);
        if (!isset($config['key'])) {
            $config['key'] = array_keys($config['table'][0])[0];
        }
        if (!is_array($config['key'])) {
            $config['key'] = array($config['key']);
        }
        $buffer = '<table id="'.$config['id'].'" class="table table-striped" style="width:'.$config['width'].'">';
        $buffer .= '<thead><tr>';
        foreach ($config['titles'] as &$title) {
            $buffer .= '<th>'.$title.'</th>';
        }
        $checked = $config['mastercheck'] ? ' checked="checked"' : '';
        $buffer .= '<th><input type="checkbox"'.$checked.' onclick="Form.checkboxesSet(\''.$config['name'].'\', this.checked)"/></th>';
        $buffer .= '</tr></thead><tbody>';
        $n_keys = count($config['key']);
        foreach ($config['table'] as &$row) {
            // determinar la llave
            $key = [];
            foreach ($config['key'] as $k) {
                $key[] = $row[$k];
            }
            $key = implode (';', $key);
            // agregar fila
            $buffer .= '<tr>';
            $count = 0;
            foreach ($row as &$col) {
                if ($config['display-key'] || $count>=$n_keys) {
                    $buffer .= '<td>'.$col.'</td>';
                }
                $count++;
            }
            $checked = (in_array($key, $config['checked']) || $config['mastercheck']) ? ' checked="checked"' : '' ;
            $buffer .= '<td style="width:1px"><input type="checkbox" name="'.$config['name'].'[]" value="'.$key.'"'.$checked.' /></td>';
            $buffer .= '</tr>';
        }
        $buffer .= '</tbody></table>';
        return $buffer;
    }

    private function _input_radios($config)
    {
        $config = array_merge([
            'options' => [],
        ], $config);
        // si el valor por defecto se pasó en value se copia donde corresponde
        if (isset($config['value'][0])) {
            $config['checked'] = $config['value'];
        }
        $buffer_radios = [];
        foreach ($config['options'] as $key => &$value) {
            if (is_array($value)) {
                $key = array_shift($value);
                $value = array_shift($value);
            }
            $checked = isset($config['checked']) && $config['checked']==$key ? 'checked="checked"' : '';
            $buffer_radios[ ]= '<input type="radio" name="'.$config['name'].'" value="'.$key.'" '.$checked.'> '.$value;
        }
        return implode('<br/>', $buffer_radios);
    }

    private function _input_tableradios($config)
    {
        // configuración por defecto
        $config = array_merge([
            'id' => $config['name'],
            'options' => [],
            'titles' => [],
            'table' => [],
            'width' => '100%'
        ], $config);
        $buffer = '<table id="'.$config['id'].'" class="table table-striped" style="width:'.$config['width'].'">';
        $buffer .= '<thead><tr>';
        foreach ($config['titles'] as &$title) {
            $buffer .= '<th>'.$title.'</th>';
        }
        foreach ($config['options'] as &$option) {
            $buffer .= '<th><div><span>'.$option.'</span></div></th>';
        }
        $buffer .= '</tr></thead><tbody>';
        $options = array_keys($config['options']);
        foreach ($config['table'] as &$row) {
            $key = array_shift($row);
            // agregar fila
            $buffer .= '<tr>';
            foreach ($row as &$col) {
                $buffer .= '<td>'.$col.'</td>';
            }
            foreach ($options as &$value) {
                if (isset($_POST[$config['name'].'_'.$key]) && $_POST[$config['name'].'_'.$key]==$value) {
                    $checked = 'checked="checked" ';
                } else {
                    $checked = '';
                }
                $buffer .= '<td><input type="radio" name="'.$config['name'].'_'.$key.'" value="'.$value.'" '.$checked.'/></td>';
            }
            $buffer .= '</tr>';
        }
        $buffer .= '</tbody></table>';
        return $buffer;
    }

}
