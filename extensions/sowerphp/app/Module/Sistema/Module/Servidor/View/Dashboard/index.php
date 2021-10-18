<div class="page-header"><h1>Servidor <small><?=$hostname?></small></h1></div>

<?php
echo View_Helper_Dashboard::cards([
    [
        'icon' => 'far fa-file',
        'quantity' => $p_cpu.'%',
        'title' => 'Uso promedio CPU',
        'link' => 'processes/top/cpu',
        'link_title' => 'Ver uso de CPU',
    ],
    [
        'icon' => 'fas fa-sign-out-alt',
        'quantity' => $p_memory.'%',
        'title' => 'Uso de memoria',
        'link' => 'processes/top/memory',
        'link_title' => 'Ver uso de memoria',
    ],
    [
        'icon' => 'fas fa-sign-in-alt',
        'quantity' => $partition_app['usage'].'%',
        'title' => 'Uso de '.$partition_app['mount'],
        'link' => 'disk/usage',
        'link_title' => 'Ver sistemas de archivos',
    ],
    [
        'icon' => 'fas fa-exchange-alt',
        'quantity' => round($network_average['rx']/1000,1).' / '.round($network_average['tx']/1000,1),
        'title' => 'RX / TX red en MB',
        'link' => 'network/info',
        'link_title' => 'Información de la red',
    ],
]);
?>

<div class="row">
    <!-- PANEL IZQUIERDA -->
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header">CPU</div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item">CPUs: <?=$cpu_count?></li>
                    <li class="list-group-item">Tipo: <?=$cpu_type[0]?></li>
                </ul>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header">Memoria</div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item">Total: <?=$memory_total?> [MiB]</li>
                    <li class="list-group-item">Usada: <?=$memory_free?> [MiB]</li>
                    <li class="list-group-item">Libre: <?=$memory_free?> [MiB]</li>
                    <li class="list-group-item">Compartida: <?=$memory_shared?> [MiB]</li>
                    <li class="list-group-item">Buff/cache: <?=$memory_buff_cache?> [MiB]</li>
                    <li class="list-group-item">Disponible: <?=$memory_available?> [MiB]</li>
                </ul>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header">Sistemas de archivos</div>
            <div class="card-body">
<?php foreach ($disks as $disk) : ?>
                <span><?=$disk['mount']?></span> <span class="float-right"><?=$disk['size']?></span>
                <div class="progress mb-3">
                    <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="<?=$disk['usage']?>" aria-valuemin="0" aria-valuemax="100" style="width: <?=$disk['usage']?>%;">
                        <?=$disk['usage']?>%
                    </div>
                </div>
<?php endforeach; ?>
            </div>
        </div>
    </div>
    <!-- FIN PANEL IZQUIERDA -->
    <!-- PANEL CENTRO -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="far fa-chart-bar fa-fw"></i> Tasas de transferencia de la red (RX y TX)
            </div>
            <div class="card-body">
                <div id="grafico-network_average"></div>
            </div>
        </div>
    </div>
    <!-- FIN PANEL CENTRO -->
    <!-- PANEL DERECHA -->
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header">Sistema operativo</div>
            <div class="card-body text-center"><?=$uname?></div>
        </div>
        <div class="card mb-4">
            <div class="card-header">Tiempo en línea</div>
            <div class="card-body text-center"><?=$uptime?></div>
        </div>
        <div class="card mb-4">
            <div class="card-header">Fecha y hora del sistema</div>
            <div class="card-body text-center"><?=$date?></div>
        </div>
<?php if (!empty($memcached)) : ?>
<?php foreach($memcached as $server => $info) : ?>
        <div class="card mb-4">
            <div class="card-header">Memcached</div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item">Servidor: <?=$server?></li>
                    <li class="list-group-item">PID: <?=$info['pid']?></li>
                    <li class="list-group-item">Uptime: <?=$info['uptime']?> [s]</li>
                    <li class="list-group-item">Versión: <?=$info['version']?></li>
                    <li class="list-group-item">Conexiones: <?=$info['curr_connections']?> de <?=$info['max_connections']?></li>
                    <li class="list-group-item">Sets / Gets: <?=$info['cmd_set']?> / <?=$info['cmd_get']?></li>
                    <li class="list-group-item">Hits: <?=round(($info['get_hits']/$info['cmd_get'])*100, 2)?>%</li>
                    <li class="list-group-item">Bytes escritos: <?=round($info['bytes_written']/1024/1024,1)?> [MiB]</li>
                    <li class="list-group-item">Bytes leídos: <?=round($info['bytes_read']/1024/1024,1)?> [MiB]</li>
                    <li class="list-group-item">Items actuales: <?=$info['curr_items']?></li>
                    <li class="list-group-item">Items totales: <?=$info['total_items']?></li>
                    <li class="list-group-item">Expirados sin uso: <?=$info['expired_unfetched']?></li>
                </ul>
            </div>
        </div>
<?php endforeach; ?>
<?php endif; ?>
        <a class="btn btn-success btn-lg btn-block" href="php/info" role="button">
            <span class="fab fa-php"> Información de PHP
        </a>
    </div>
    <!-- FIN PANEL DERECHA -->
</div>

<script>
$(function() {
    var networkGraph;
    $.getJSON(
        '<?=$_base?>/api/sistema/servidor/network/average',
        function(results) {
            networkGraph = Morris.Line({
                element: 'grafico-network_average',
                data: results,
                xkey: 'time',
                ykeys: ['rx', 'tx'],
                labels: ['RX', 'TX'],
                postUnits: ' KB',
                xLabels: 'Fecha y hora',
                resize: true,
                xLabelAngle: 45
            });
            setInterval(function() { updateNetworkGraph(networkGraph); }, 5000);
        }
    );
});
function updateNetworkGraph(graph) {
    $.getJSON(
        '<?=$_base?>/api/sistema/servidor/network/average',
        function(results) {
            graph.setData(results);
        }
    );
}
</script>
