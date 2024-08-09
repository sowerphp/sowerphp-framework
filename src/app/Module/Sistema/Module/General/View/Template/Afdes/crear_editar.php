<div class="page-header"><h1><?=$accion?> AFD</h1></div>
<?php
$f = new \sowerphp\general\View_Helper_Form ();
echo $f->begin(array('onsubmit'=>'Form.check()'));
echo $f->input([
    'name' => 'codigo',
    'label' => 'Código',
    'value' => isset($Afd) ? $Afd->codigo : '',
    'attr' => 'maxlength="10"',
    'check' => 'notempty',
]);
echo $f->input([
    'name' => 'nombre',
    'label' => 'Nombre',
    'value' => isset($Afd) ? $Afd->nombre : '',
    'attr' => 'maxlength="50"',
    'check' => 'notempty',
]);
echo $f->input([
    'type' => 'js',
    'id' => 'estados',
    'label' => 'Estados',
    'titles' => ['Código', 'Estado'],
    'inputs' => [
        ['name'=>'estado_codigo'],
        ['name'=>'estado_nombre', 'attr'=>'maxlength="50"']
    ],
    'values' => isset($Afd) ? $Afd->getEstados('estado_') : [],
]);
echo $f->input([
    'type' => 'js',
    'id' => 'transiciones',
    'label' => 'Transiciones',
    'titles' => ['Desde', 'Valor', 'Hasta'],
    'inputs' => [
        ['name'=>'desde'],
        ['name'=>'valor', 'attr'=>'maxlength="5"'],
        ['name'=>'hasta']
    ],
    'values' => isset($Afd) ? $Afd->getTransicionesTabla() : [],
]);
echo $f->end('Guardar');
if (isset($Afd)) {
    echo '<div class="center" style="clear:both"><img src="',$_base,'/sistema/general/afdes/grafo/',$Afd->codigo,'" alt="Grafo ',$Afd->codigo,'" /></div>',"\n";
}
?>
<div style="float:right;margin-bottom:1em;font-size:0.8em">
    <a href="<?=$_base.$listarUrl?>">Volver al listado de registros</a>
</div>
