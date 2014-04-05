<h1><?=$accion?> {class}</h1>

<?php

// columnas que se utilizarán en el formulario que se desplegará
$columns = array(
    {columns}
);

// crear formulario
$form = new \sowerphp\general\View_Helper_Form ();
echo $form->begin(array('onsubmit'=>'Form.check()'));

// opciones para select en caso que sea un campo boolean
$optionsBoolean = array(
    array('', 'Seleccione una opción'),
    array('t', 'Si'),
    array('f', 'No')
);

// agregar campos del formulario
foreach ($columns as $column => &$name) {
    // se genera campo de input solo si no es una columna automática
    if (is_array($name) || !$columnsInfo[$column]['auto']) {
        // configuración base para campo
        $input = array(
            'name'  => $column,
            'label' => $name,
            'help'  => $columnsInfo[$column]['comment'],
            'check' => (!$columnsInfo[$column]['null']?'notempty':'')
        );
        // si es un archivo
        if (is_array($name) && $name['type']=='file') {
            $input['type'] = 'file';
            echo $form->input($input);
        }
        // si es de tipo text se muestra un textarea
        else if ($columnsInfo[$column]['type']=='text') {
            $input['type'] = 'textarea';
            if (isset(${class})) $input['value'] = ${class}->{$column};
            echo $form->input($input);
        }
        // si es de tipo boolean se muestra lista desplegable
        else if ($columnsInfo[$column]['type']=='boolean') {
            $input['type'] = 'select';
            $input['options'] = $optionsBoolean;
            if (isset(${class})) $input['selected'] = ${class}->{$column};
            echo $form->input($input);
        }
        // si es de tipo date se muestra calendario
        else if ($columnsInfo[$column]['type']=='date') {
            $input['type'] = 'date';
            if (isset(${class})) $input['value'] = ${class}->{$column};
            echo $form->input($input);
        }
        // si es llave foránea
        else if ($columnsInfo[$column]['fk']) {
            $class = 'Model_'.\sowerphp\core\Utility_Inflector::camelize(
                $columnsInfo[$column]['fk']['table']
            );
            $classs = $fkNamespace[$class].'\Model_'.\sowerphp\core\Utility_Inflector::camelize(
                \sowerphp\core\Utility_Inflector::pluralize($columnsInfo[$column]['fk']['table'])
            );
            $options = (new $classs())->getList();
            array_unshift($options, array('', 'Seleccione una opción'));
            $input['type'] = 'select';
            $input['options'] = $options;
            if (isset(${class})) $input['selected'] = ${class}->{$column};
            echo $form->input($input);
        }
        // si el nombre de la columna es contrasenia o clave o password o pass
        else if (in_array($column, array('contrasenia', 'clave', 'password', 'pass'))) {
            $input['type'] = 'password';
            echo $form->input($input);
        }
        // si es cualquier otro tipo de datos
        else {
            if (isset(${class})) $input['value'] = ${class}->{$column};
            echo $form->input($input);
        }
    }
}

// terminar formulario
echo $form->end('Guardar');
