<div class="page-header"><h1>Sistema &raquo; Logs &raquo; Buscar</h1></div>

<div class="modal fade" id="modal-log" tabindex="-1" role="dialog" aria-labelledby="modalLogLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="modalLogLabel"></h4>
      </div>
      <div class="modal-body">

      </div>
    </div>
  </div>
</div>

<link rel="stylesheet" type="text/css" href="<?=$_base?>/css/jquery.dataTables.css" />
<script type="text/javascript" src="<?=$_base?>/js/jquery.dataTables.js"></script>
<script type="text/javascript"> $(function(){dataTable("#eventos");});</script>

<?php
$f = new \sowerphp\general\View_Helper_Form();
echo $f->begin(['onsubmit'=>'Form.check()']);
echo $f->input(['name'=>'usuario', 'label'=>'Usuario', 'check'=>'notempty']);
echo $f->input(['type'=>'date', 'name'=>'desde', 'label'=>'Desde', 'check'=>'date']);
echo $f->input(['type'=>'date', 'name'=>'hasta', 'label'=>'Hasta', 'check'=>'date']);
echo $f->end('Buscar eventos del usuario');

if (isset($eventos)) {
    array_unshift($eventos, ['Timestamp', 'Prioridad', 'Usuario', 'Mensaje', 'Detalles']);
    $t = new \sowerphp\general\View_Helper_Table();
    $t->setId('eventos');
    $t->setExport(true);
    $t->setColsWidth([80, 50, 100, null, 20]);
    echo $t->generate($eventos);
}
