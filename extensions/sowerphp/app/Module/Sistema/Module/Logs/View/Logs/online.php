<div class="page-header"><h1>Sistema &raquo; Logs &raquo; Visor en línea</h1></div>

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
<script type="text/javascript">
$().ready(function() {
    var table = dataTable("#eventos");
    table.api().ajax.url(_base + "/api/sistema/logs/logs/crud").load();
    setInterval( function () { table.api().ajax.reload(null, false); }, 3000);
});
$("#modal-log").on("show.bs.modal", function (event) {
    var log_id = $(event.relatedTarget).data("log_id");
    var modal = $(this);
    request = $.ajax({
        url: _base + "/api/sistema/logs/logs/crud/" + log_id,
        method: "GET",
        dataType: "json"
    });
    request.done(function(jqXHR, textStatus) {
        var mensaje = '';
        mensaje += '<strong>Identificador</strong><br/><pre>'+jqXHR["identificador"]+'</pre>';
        mensaje += '<strong>Solicitud</strong><br/><pre>'+jqXHR["solicitud"]+'</pre>';
        mensaje += '<strong>Mensaje</strong><br/><pre>'+jqXHR["mensaje"]+'</pre>';
        modal.find('.modal-body').empty();
        modal.find('.modal-body').append(mensaje);
        modal.find(".modal-title").text("Log " + jqXHR["origen"] + "." + jqXHR["gravedad"] + " ID: " + log_id);
    });
});
</script>

<?php
$eventos = [['Timestamp', 'Prioridad', 'Usuario', 'Mensaje', 'Detalles']];
$t = new \sowerphp\general\View_Helper_Table();
$t->setId('eventos');
$t->setColsWidth([80, 50, 100, null, 20]);
echo $t->generate($eventos);
