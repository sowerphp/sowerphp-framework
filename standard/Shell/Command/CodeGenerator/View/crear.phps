<h1>Crear {class}</h1>

<?php

// columnas que se utilizarán en la tabla que se desplegará
$columns = array(
	{columns}
);

// crear formulario
$form = new FormHelper();
echo $form->begin(array('onsubmit'=>'Form.check()'));

// agregar campos del formulario
$optionsBoolean = array(array('', 'Seleccione una opción'), array('t', 'Si'), array('f', 'No'));
foreach($columns as $column => &$name) {
	// si no es una columna automática
	if(is_array($name) || !$columnsInfo[$column]['auto']) {
		// si es un archivo
		if(is_array($name) && $name['type']=='file') {
			echo $form->input(array('type'=>'file', 'name'=>$column, 'label'=>$name['name'], 'help' => $columnsInfo[$column.'_data']['comment'], 'check' => (!$columnsInfo[$column.'_data']['null']?'notempty':'')));
		}
		// si es de tipo text se muestra un textarea
		else if($columnsInfo[$column]['type']=='text') {
			echo $form->input(array('type'=>'textarea', 'name'=>$column, 'label'=> $name, 'help' => $columnsInfo[$column]['comment'], 'check' => (!$columnsInfo[$column]['null']?'notempty':'')));
		}
		// si es de tipo boolean se muestra lista desplegable
		else if($columnsInfo[$column]['type']=='boolean') {
			echo $form->input(array('type'=>'select', 'name'=>$column, 'label'=> $name, 'options' => $optionsBoolean, 'help' => $columnsInfo[$column]['comment'], 'check' => (!$columnsInfo[$column]['null']?'notempty':'')));
		}
		// si es de tipo date se muestra calendario
		else if($columnsInfo[$column]['type']=='date') {
			echo $form->input(array('type'=>'date', 'name'=>$column, 'label'=> $name, 'help' => $columnsInfo[$column]['comment'], 'check' => (!$columnsInfo[$column]['null']?'notempty':'')));
		}
		// si es llave foránea
		else if($columnsInfo[$column]['fk']) {
			$class = Inflector::camelize($columnsInfo[$column]['fk']['table']);
			$classs = Inflector::camelize(Inflector::pluralize($columnsInfo[$column]['fk']['table']));
			App::uses($class, $fkModule[$class].'Model');
			$objs = new $classs();
			$options = $objs->getList();
			array_unshift($options, array('', 'Seleccione una opción'));
			echo $form->input(array('type'=>'select', 'name'=>$column, 'label'=> $name, 'options' => $options, 'help' => $columnsInfo[$column]['comment'], 'check' => (!$columnsInfo[$column]['null']?'notempty':'')));
		}
		// si el nombre de la columna es contrasenia o clave o password o pass
		else if(in_array($column, array('contrasenia', 'clave', 'password', 'pass'))) {
			echo $form->input(array('type'=>'password', 'name'=>$column, 'label'=> $name, 'help' => $columnsInfo[$column]['comment'], 'check' => (!$columnsInfo[$column]['null']?'notempty':'')));
		}
		// si es cualquier otro tipo de datos
		else {
			echo $form->input(array('name'=>$column, 'label'=>$name, 'help' => $columnsInfo[$column]['comment'], 'check' => (!$columnsInfo[$column]['null']?'notempty':'')));
		}
	}
}

// terminar formulario
echo $form->end('Guardar');
