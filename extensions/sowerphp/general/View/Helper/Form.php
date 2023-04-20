<?php

/**
 * SowerPHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace sowerphp\general;

/**
 * Helper para la creación de formularios en HTML
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2019-08-06
 */
class View_Helper_Form
{

    private $_id; ///< Identificador para el formulario
    private $_style; ///< Formato del formulario que se renderizará (mantenedor u false)
    private $_cols_label; ///< Columnas de la grilla para la etiqueta

    /**
     * Método que inicia el código del formulario
     * @param style Estilo del formulario que se renderizará
     * @param cols_label Cantidad de columnas de la grilla para la etiqueta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-12-10
     */
    public function __construct($style = 'horizontal', $cols_label = 2)
    {
        $this->_style = $style;
        $this->_cols_label = $cols_label;
    }

    /**
     * Método para asignar el estilo del formulario una vez ya se creo el objeto
     * @param style Estilo del formulario que se renderizará
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-08-21
     */
    public function setStyle($style = false)
    {
        $this->_style = $style;
    }

    /**
     * Método para asignar la cantidad de columnas de la grilla para la etiqueta
     * @param cols_label Cantidad de columnas de la grilla para la etiqueta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-03-25
     */
    public function setColsLabel($cols_label = 2)
    {
        $this->_cols_label = $cols_label;
    }

    /**
     * Método que inicia el código del formulario
     * @param config Arreglo con la configuración para el formulario
     * @return String Código HTML de lo solicitado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-12-27
     */
    public function begin($config = [])
    {
        // transformar a arreglo en caso que no lo sea
        if (!is_array($config)) {
            $config = ['action'=>$config];
        }
        // asignar configuración
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
        $buffer .= '<form action="'.$config['action'].'" method="'.$config['method'].'" enctype="multipart/form-data"'.$config['onsubmit'].' id="'.$config['id'].'" '.$config['attr'].' class="'.$class.'" role="form">'."\n";
        // retornar
        return $buffer;
    }

    /**
     * Método que termina el código del formulario
     * @param config Arreglo con la configuración para el botón submit
     * @return String Código HTML de lo solicitado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-12-10
     */
    public function end($config = [])
    {
        // solo se procesa la configuración si no es falsa
        if ($config!==false) {
            // transformar a arreglo en caso que no lo sea
            if (!is_array($config))
                $config = ['value'=>$config];
            // asignar configuración
            $config['type'] = 'submit';
            $config = array_merge(
                [
                    'type' => 'submit',
                    'name' => 'submit',
                    'value' => 'Enviar',
                    'label' => '',
                ], $config
            );
            // generar fin del formulario
            return $this->input($config).'</form>'."\n";
        } else {
            return '</form>'."\n";
        }
    }

    /**
     * Método que aplica o no un diseño al campo
     * @param field Campo que se desea formatear
     * @param config Arreglo con la configuración para el elemento
     * @return String Código HTML de lo solicitado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-08-06
     */
    private function _formatear($field, $config)
    {
        // si es campo oculto no se aplica ningún estilo
        if ($config['type'] == 'hidden') {
            $buffer = '    '.$field."\n";
        }
        // si se debe aplicar estilo horizontal
        else if ($config['style']=='horizontal') {
            if ($config['help']!='') {
                $config['help'] = ' <p class="form-text text-muted"'.(isset($config['id'])?' id="'.$config['id'].'Help"':'').'>'.$config['help'].'</p>';
            }
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
            if ($config['type']!='checkbox') {
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
            $buffer .= '</div>'."\n";
        }
        // si se debe alinear
        else if (isset($config['align'])) {
            $width = !empty($config['width']) ? (';width:'.$config['width']) : '';
            $buffer = '<div style="text-align:'.$config['align'].$width.'">'.$field.'</div>'."\n";
        }
        // si se debe colocar un label
        else if (!empty($config['id']) and !empty($config['label'])) {
            $width = !empty($config['width']) ? (' style="width:'.$config['width'].'"') : '';
            $buffer = '<div'.$width.'><label class="visually-hidden" for="'.$config['id'].'">'.$config['label'].'</label>'.$field.'</div>'."\n";
        }
        // si no se debe aplicar ningún formato solo agregar el campo dentro de un div y el EOL
        else {
            $width = !empty($config['width']) ? (' style="width:'.$config['width'].'"') : '';
            $buffer = '<div'.$width.'>'.$field.'</div>'."\n";
        }
        // retornar código formateado
        return $buffer;
    }

    /**
     * Método para crear una nuevo campo para que un usuario ingrese
     * datos a través del formulario, ya sea un tag: input, select, etc
     * @param config Arreglo con la configuración para el elemento
     * @return String Código HTML de lo solicitado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2016-02-10
     */
    public function input($config)
    {
        // transformar a arreglo en caso que no lo sea
        if (!is_array($config)) {
            $config = array('name'=>$config, 'label'=>$config);
        }
        // asignar configuración
        $config = array_merge(
            array(
                'type'=>'text',
                'value'=>'',
                'autoValue'=>false,
                'class' => '',
                'attr' => '',
                'check' => null,
                'help' => '',
                'popover' => '',
                'notempty' =>false,
                'style'=>$this->_style,
                'placeholder' => '',
                'sanitize' => true,
            ), $config
        );
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
            if (!is_array($config['check'])) $config['check'] = explode(' ',$config['check']);
            // hacer implode, agregar check y meter al class
            $config['class'] = $config['class'].' check '.implode(' ', $config['check']);
            if (in_array('notempty', $config['check'])) {
                $config['notempty'] = true;
            }
        }
        // asignar class
        if (!in_array($config['type'], ['submit', 'checkbox', 'file', 'div'])) {
            $config['class'] = (!empty($config['class']) ? $config['class'] : '').' form-control';
        }
        // asignar id si no se asignó
        if (!isset($config['id']) and !empty($config['name']) and substr($config['name'], -2)!='[]') {
            $config['id'] = $config['name'].'Field';
        }
        // determinar popover
        if ($config['popover']!='') {
            $config['popover'] = ' data-bs-toggle="popover" data-bs-trigger="focus" title="'.$config['label'].'" data-bs-placement="top" data-bs-content="'.$config['popover'].'" onmouseover="$(this).popover(\'show\')" onmouseout="$(this).popover(\'hide\')"';
        }
        // limpiar valor del campo
        if ($config['type']!='div' and $config['sanitize'] and isset($config['value'][0]) and !is_array($config['value'])) {
            $config['value'] = trim(strip_tags($config['value']));
            if (!in_array($config['type'], ['submit', 'button'])) {
                $config['value'] = htmlentities($config['value']);
            }
        }
        // generar campo, formatear y entregar
        return $this->_formatear($this->{'_'.$config['type']}($config), $config);
    }

    private function _submit($config)
    {
        return $this->_button($config);
    }

    private function _button($config)
    {
        $id = isset($config['id']) ? ' id="'.$config['id'].'"' : '';
        return '<button type="'.$config['type'].'" name="'.$config['name'].'"'.$id.' class="'.$config['class'].' btn btn-primary" '.$config['attr'].'>'.$config['value'].'</button>';
    }

    private function _hidden ($config)
    {
        $id = isset($config['id']) ? ' id="'.$config['id'].'"' : '';
        return '<input type="hidden" name="'.$config['name'].'" value="'.$config['value'].'"'.$id.' />';
    }

    private function _text ($config)
    {
        $id = isset($config['id']) ? ' id="'.$config['id'].'"' : '';
        $growup = !empty($config['growup']) ? 'ondblclick="Form.growup(this)"' : '';
        $autocomplete = (isset($config['autocomplete']) and $config['autocomplete']===false) ? 'autocomplete="off"' : '';
        return '<input type="text" name="'.$config['name'].'" value="'.$config['value'].'"'.$id.' class="'.$config['class'].'" placeholder="'.$config['placeholder'].'" '.$config['attr'].$config['popover'].' '.$growup.' '.$autocomplete.' />';
    }

    private function _password($config)
    {
        $id = isset($config['id']) ? ' id="'.$config['id'].'"' : '';
        $autocomplete = empty($config['autocomplete']) ? 'autocomplete="off"' : '';
        return '<input type="password" name="'.$config['name'].'"'.$id.' class="'.$config['class'].'" '.$config['attr'].$config['popover'].' '.$autocomplete.' />';
    }

    private function _textpass($config)
    {
        $id = isset($config['id']) ? ' id="'.$config['id'].'"' : '';
        $mousechange = 'onmouseover="this.type=\'text\'" onmouseout="this.type=\'password\'"';
        $script = isset($config['id']) ? '<script>$(\'#'.$config['id'].'\').attr(\'type\', \'password\');</script>' : '';
        return '<input type="text" name="'.$config['name'].'" value="'.$config['value'].'"'.$id.' class="'.$config['class'].'" placeholder="'.$config['placeholder'].'" '.$config['attr'].$config['popover'].' autocomplete="off" '.$mousechange.' />'.$script;
    }

    private function _textarea ($config)
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

    private function _checkbox ($config)
    {
        // determinar si está o no chequeado
        if (!isset($config['checked']) and isset($_POST[$config['name']])) {
            $config['checked'] = true;
        }
        $id = isset($config['id']) ? ' id="'.$config['id'].'"' : '';
        $checked = isset($config['checked']) && $config['checked'] ? ' checked="checked"' : '';
        return '<input type="checkbox" name="'.$config['name'].'" value="'.$config['value'].'"'.$id.$checked.' class="'.$config['class'].'" '.$config['attr'].' />';
    }

    /**
     * @todo No se está utilizando checked
     * @warning icono para ayuda queda abajo (por los <br/>)
     */
    private function _checkboxes ($config)
    {
        $buffer = '';
        foreach ($config['options'] as $key => &$value) {
            if (is_array($value)) {
                $key = array_shift($value);
                $value = array_shift($value);
            }
            $buffer .= '<input type="checkbox" name="'.$config['name'].'[]" value="'.$key.'" class="'.$config['class'].'" '.$config['attr'].'/> '.$value.'<br />';
        }
        return $buffer;
    }

    private function _date ($config)
    {
        $config['datepicker'] = array_merge(
            (array)\sowerphp\core\Configure::read('datepicker'),
            isset($config['datepicker']) ? $config['datepicker'] : []
        );
        $datepicker_config = str_replace('"', '\'', json_encode($config['datepicker']));
        $buffer = '';
        if (isset($config['id'])) {
            $attr = ' id="'.$config['id'].'"';
            $buffer .= '<script>$(function() { $("#'.$config['id'].'").datepicker('.$datepicker_config.'); }); </script>';
        } else {
            $attr = ' onmouseover="$(this).datepicker('.$datepicker_config.')"';
        }
        $buffer .= '<input type="text" name="'.$config['name'].'" value="'.$config['value'].'"'.$attr.' class="'.$config['class'].'" placeholder="'.$config['placeholder'].'" '.$config['attr'].$config['popover'].' autocomplete="off" />';
        return $buffer;
    }

    private function _file ($config)
    {
        $id = isset($config['id']) ? ' id="'.$config['id'].'"' : '';
        return '<input type="file" name="'.$config['name'].'"'.$id.' class="form-control" '.$config['attr'].'/>';
    }

    private function _files($config)
    {
        return $this->_js([
            'id' => $config['id'],
            'label' => $config['label'],
            'titles' => [$config['title']],
            'inputs' => [
                ['type'=>'file', 'name'=>$config['name']],
            ]
        ]);
    }

    private function _select($config)
    {
        $form_select_wrapper = \sowerphp\core\Configure::read('form.select.wrapper');
        if ($form_select_wrapper === null) {
            $form_select_wrapper = 'select2';
        }
        $config = array_merge(['wrapper'=>$form_select_wrapper], $config);
        // configuración para los wrappers
        $wrapper_config = '';
        if ($config['wrapper']=='select2') {
            $config['select2'] = array_merge(
                (array)\sowerphp\core\Configure::read('select2'),
                isset($config['select2']) ? $config['select2'] : []
            );
            if (empty($config['select2']['placeholder']) and !empty($config['placeholder'])) {
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
        $onmouseover = '';
        if (!empty($config['id'])) {
            $attr .= ' id="'.$config['id'].'"';
            if (!empty($config['wrapper'])) {
                $buffer .= '<script>$(function() { $("#'.$config['id'].'").'.$config['wrapper'].'('.$wrapper_config.'); }); </script>';
            }
        } else {
            if (!empty($config['wrapper'])) {
                $onmouseover .= ' $(this).'.$config['wrapper'].'('.$wrapper_config.');';
            }
        }
        if (!empty($config['onblur'])) {
            if ($config['wrapper']=='select2') {
                if (!empty($config['id'])) {
                    $buffer .= '<script>$(function() { $("#'.$config['id'].'").on("select2:close", function(){ '.$config['onblur'].'; }); });</script>';
                } else {
                    $onmouseover .= ' $(this).on(\'select2:close\', function(){ '.$config['onblur'].'; });';
                }
            } else {
                $attr .= ' onblur="'.$config['onblur'].'"';
            }
        }
        if (!empty($onmouseover)) {
            $attr .= ' onmouseover="'.$onmouseover.'"';
        }
        if (!is_array($config['value'])) {
            $config['value'] = ($config['value'] or (string)$config['value']=='0') ? [$config['value']] : [];
        }
        $config['value'] = array_map('strval', $config['value']);
        $buffer .= '<select name="'.$config['name'].'"'.$attr.' class="'.$config['class'].'"'.$multiple.' '.$config['attr'].'>';
        foreach ($config['options'] as $key => $value) {
            // los options no están agrupados
            if (empty($config['groups'])) {
                if (is_array($value)) {
                    $key = array_shift($value);
                    $value = array_shift($value);
                }
                $selected = (in_array((string)$key, $config['value'], true)?' selected="selected"':'');
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
                    $selected = (in_array((string)$key2, $config['value'], true)?' selected="selected"':'');
                    $buffer .= '<option value="'.$key2.'"'.$selected.'>'.$value2.'</option>';
                }
                $buffer .= '</optgroup>';
            }
        }
        $buffer .= '</select>';
        return $buffer;
    }

    private function _radios ($config)
    {
        // si el valor por defecto se pasó en value se copia donde corresponde
        if (isset($config['value'][0])) {
            $config['checked'] = $config['value'];
        }
        $buffer = '';
        foreach ($config['options'] as $key => &$value) {
            if (is_array($value)) {
                $key = array_shift($value);
                $value = array_shift($value);
            }
            $checked = isset($config['checked']) && $config['checked']==$key ? 'checked="checked"' : '';
            $buffer .= ' <input type="radio" name="'.$config['name'].'" value="'.$key.'" '.$checked.'> '.$value.' ';
        }
        return $buffer;
    }

    private function _js ($config, $js = true)
    {
        // configuración por defecto
        $config = array_merge(['titles'=>[], 'width'=>'100%', 'accesskey'=>'+', 'callback'=>'undefined'], $config);
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
        $delete = '<td><a class="'.$config['id'].'_eliminar" href="" onclick="Form.delJS(this); return false" title="Eliminar"><i class="fas fa-times fa-fw mt-2" aria-hidden="true"></i></a></td>';
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
            if (isset($_POST[$config['inputs'][0]['name']])) {
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
            else $values = $inputs;
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
            $buffer .= '<script> window["inputsJS_'.$config['id'].'"] = \''.str_replace('\'', '\\\'', $inputs).'\'; </script>'."\n";
        }
        $buffer .= '<table class="table table-striped" id="'.$config['id'].'" style="width:'.$config['width'].'">';
        $buffer .= '<thead><tr>';
        foreach ($config['titles'] as $title) {
            $buffer .= '<th>'.$title.'</th>';
        }
        if ($js) {
            $buffer .= '<th style="width:1px;"><a href="javascript:Form.addJS(\''.$config['id'].'\', undefined, '.$config['callback'].')" title="Agregar ['.$config['accesskey'].']" accesskey="'.$config['accesskey'].'"><i class="fa fa-plus fa-fw" aria-hidden="true"></i></a></th>';
        }
        $buffer .= '</tr></thead>';
        $buffer .= '<tbody>'.$values.'</tbody>';
        $buffer .= '</table>';
        return $buffer;
    }

    private function _tablecheck ($config)
    {
        if(!isset($config['table'][0]))
            return '-';
        // configuración por defecto
        $config = array_merge([
            'id'=>$config['name'],
            'titles'=>array(),
            'width'=>'100%',
            'mastercheck'=>false,
            'checked'=>(isset($_POST[$config['name']])?$_POST[$config['name']]:[]),
            'display-key'=>true,
        ], $config);
        if (!isset($config['key']))
            $config['key'] = array_keys($config['table'][0])[0];
        if (!is_array($config['key']))
            $config['key'] = array($config['key']);
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
            $key = array();
            foreach ($config['key'] as $k) {
                $key[] = $row[$k];
            }
            $key = implode (';', $key);
            // agregar fila
            $buffer .= '<tr>';
            $count = 0;
            foreach ($row as &$col) {
                if ($config['display-key'] or $count>=$n_keys)
                    $buffer .= '<td>'.$col.'</td>';
                $count++;
            }
            $checked = (in_array($key, $config['checked']) or $config['mastercheck']) ? ' checked="checked"' : '' ;
            $buffer .= '<td><input type="checkbox" name="'.$config['name'].'[]" value="'.$key.'"'.$checked.' /></td>';
            $buffer .= '</tr>';
        }
        $buffer .= '</tbody></table>';
        return $buffer;
    }

    private function _tableradios ($config)
    {
        // configuración por defecto
        $config = array_merge(array('id'=>$config['name'], 'titles'=>array(), 'width'=>'100%'), $config);
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
                if (isset($_POST[$config['name'].'_'.$key]) && $_POST[$config['name'].'_'.$key]==$value)
                    $checked = 'checked="checked" ';
                else $checked = '';
                $buffer .= '<td><input type="radio" name="'.$config['name'].'_'.$key.'" value="'.$value.'" '.$checked.'/></td>';
            }
            $buffer .= '</tr>';
        }
        $buffer .= '</tbody></table>';
        return $buffer;
    }

    private function _div ($config)
    {
        return '<div'.(!empty($config['attr'])?(' '.$config['attr']):'').(!empty($config['id'])?(' id="'.$config['id'].'"'):'').' class="'.$config['class'].'">'.$config['value'].'</div>';
    }

    private function _table($config)
    {
        return $this->_js($config, false);
    }

    private function _boolean($config)
    {
        if (empty($config['options'])) {
            $config['options'] = ['No', 'Si'];
        }
        return $this->_select($config);
    }

}
