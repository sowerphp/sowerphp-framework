<div class="page-header">
    <h1>Listado de <?=strtolower($metadata['model.verbose_name_plural'])?></h1>
</div>
<p><?=$metadata['model.db_table_comment']?></p>

<?php

// Preparar títulos de columnas (con link para ordenar por dicho campo).
$titles = [];
$colsWidth = [];
foreach ($columns as $column => $info) {
    $titles[] = $info['name'].' '.
        '<div class="float-end"><a href="'.$urlController.'/listar/'.$page.'/'.$column.'/A'.$searchUrl.'" title="Ordenar ascendentemente por '.$info['name'].'"><i class="fas fa-sort-alpha-down"></i></a>'.
        ' <a href="'.$urlController.'/listar/'.$page.'/'.$column.'/D'.$searchUrl.'" title="Ordenar descendentemente por '.$info['name'].'"><i class="fas fa-sort-alpha-up"></i></a></div>'
    ;
    $colsWidth[] = null;
}
$titles[] = 'Acciones';
$colsWidth[] = $actionsColsWidth;

// crear arreglo para la tabla y agregar títulos de columnas
$data = [$titles];

// agregar fila para búsqueda mediante formulario
$row = [];
$form = new \sowerphp\general\View_Helper_Form(false);
$optionsBoolean = array(array('', 'Todos'), array('1', 'Si'), array('0', 'No'));
$types_check = ['integer'=>'integer', 'real'=>'real'];
foreach ($columns as $column => &$info) {
    // si es un archivo
    if ($info['type']=='file') {
        $row[] = '';
    }
    // si es de tipo boolean se muestra lista desplegable
    else if ($info['type']=='boolean' || $info['type']=='tinyint') {
        $row[] = $form->input(array('type'=>'select', 'name'=>$column, 'options' => $optionsBoolean, 'value' => (isset($search[$column])?$search[$column]:'')));
    }
    // Si es una relación.
    else if ($info['relation']) {
        $objs = (new $info['relation']())->getPluralInstance();
        $options = $objs->getList();
        array_unshift($options, ['', 'Todos']);
        $row[] = $form->input([
            'type' => 'select',
            'name' => $column,
            'options' => $options,
            'value' => $search[$column] ?? '',
        ]);
    }
    // si es un tipo de dato de fecha o fecha con hora se muestra un input para fecha
    else if (in_array($info['type'], ['date', 'timestamp', 'timestamp without time zone'])) {
        $row[] = $form->input(array('type'=>'date', 'name'=>$column, 'value'=>(isset($search[$column])?$search[$column]:'')));
    }
    // si es cualquier otro tipo de datos
    else {
        $row[] = $form->input([
            'name' => $column,
            'value' => (isset($search[$column])?$search[$column]:''),
            'check' => !empty($types_check[$info['type']]) ? $types_check[$info['type']] : null,
        ]);
    }
}
$row[] = '<button type="submit" class="btn btn-primary" onclick="return Form.check()"><i class="fas fa-search fa-fw"></i></button>';
$data[] = $row;

// crear filas de la tabla
foreach ($Objs as &$obj) {
    $row = [];
    foreach ($columns as $column => &$info) {
        // si es un archivo
        if ($info['type']=='file') {
            if ($obj->{$column.'_size'})
                $row[] = '<a href="'.$urlController.'/d/'.$column.'/'.urlencode($obj->id).'" class="btn btn-primary"><i class="fas fa-download fa-fw"></i></a>';
            else
                $row[] = '';
        }
        // si es boolean se usa Si o No según corresponda
        else if ($info['type']=='boolean' || $info['type']=='tinyint') {
            $row[] = $obj->{$column}=='t' || $obj->{$column}=='1'
                ? '<div class="text-center"><i class="fa-solid fa-check-circle fa-fw text-success"></i></div>'
                : '<div class="text-center"><i class="fa-solid fa-times-circle fa-fw text-danger"></i></div>'
            ;
        }
        // Si es una relación.
        else if ($info['relation']) {
            $row[] = $obj->{$column}();
        }
        // si es una fecha
        else if ($info['type'] == 'date') {
            $row[] = \sowerphp\general\Utility_Date::format($obj->{$column});
        }
        // si es una fecha con hora (sin zona horaria)
        else if ($info['type'] == 'timestamp without time zone') {
            $row[] = \sowerphp\general\Utility_Date::format($obj->{$column}, 'd/m/Y H:i:s');
        }
        // si es una fecha con hora (con zona horaria)
        else if ($info['type'] == 'timestamp') {
            $row[] = \sowerphp\general\Utility_Date::format($obj->{$column}, 'd/m/Y H:i:sO');
        }
        // si es un número entero
        else if ($info['type'] == 'integer') {
            $row[] = (int)$obj->{$column};
        }
        // si es un número decimal
        else if (in_array($info['type'], ['real', 'float'])) {
            $row[] = (float)$obj->{$column};
        }
        // si es cualquier otro tipo de datos
        else {
            $row[] = $obj->{$column};
        }
    }
    $pkValues = $obj->getPrimaryKeyValues();
    $pkURL = implode('/', array_map('urlencode', $pkValues));
    $actions = '';
    if (!empty($extraActions)) {
        foreach ($extraActions as $a => $i) {
            $actions .= '<a href="'.$urlController.'/'.$a.'/'.$pkURL.$listarFilterUrl.'" title="'.(isset($i['desc'])?$i['desc']:'').'" class="btn btn-primary mb-2"><i class="'.$i['icon'].' fa-fw"></i></a> ';
        }
    }
    $actions .= '<a href="'.$urlController.'/editar/'.$pkURL.$listarFilterUrl.'" title="Editar" class="btn btn-primary mb-2"><i class="fa fa-edit fa-fw"></i></a>';
    if ($deleteRecord) {
        $actions .= ' <a href="'.$urlController.'/eliminar/'.$pkURL.$listarFilterUrl.'" title="Eliminar" onclick="return eliminar(this, \''.$metadata['model.label'].'\', \''.implode(', ', $pkValues).'\')" class="btn btn-danger mb-2"><i class="fas fa-times fa-fw"></i></a>';
    }
    $row[] = $actions;
    $data[] = $row;
}

// Renderizar la tabla con el mantenedor de los registros.
$maintainer = new \sowerphp\app\View_Helper_Maintainer([
    'link' => $urlController,
    'linkEnd' => $linkEnd,
    'listarFilterUrl' => $listarFilterUrl
]);
$maintainer->setId($metadata['model.label_lower']);
$maintainer->setColsWidth($colsWidth);
echo $maintainer->listar($data, $pages, $page);
