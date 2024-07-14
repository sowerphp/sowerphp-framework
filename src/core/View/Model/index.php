<script>
$(document).ready(function() {
    new DataTable('#records', {
        ajax: 'http://localhost:8000/libredte-enterprise/api/sistema/usuarios/grupos/index?format=datatables',
        columns: [
            { data: 'id' },
            { data: 'grupo' },
            { data: 'activo' }
        ]
    });
});
</script>

<table id="records" class="display" style="width:100%">
    <thead>
        <tr>
            <th>ID</th>
            <th>Grupo</th>
            <th>Activo</th>
        </tr>
    </thead>
    <tfoot>
        <tr>
            <th>ID</th>
            <th>Grupo</th>
            <th>Activo</th>
        </tr>
    </tfoot>
</table>
