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

// namespace del modelo
namespace sowerphp\app\Sistema\Servidor;

/**
 * Utilidad para obtener los datos a un servidor GNU/Linux
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2019-07-24
 */
class Utility_Servidor_Linux extends Utility_Servidor_Base_Datasource
{
}

class Utility_Servidor_Linux_Config extends Utility_Servidor_Base_Datasource
{

    public function hostname()
    {
        return gethostname();
    }

    public function uname()
    {
        return shell_exec('uname -a');
    }

    public function uptime()
    {
        return shell_exec('uptime -p');
    }

    public function date()
    {
        return shell_exec('date');
    }

}

class Utility_Servidor_Linux_Hardware extends Utility_Servidor_Base_Datasource
{
}

class Utility_Servidor_Linux_Hardware_Cpu extends Utility_Servidor_Base_Datasource
{

    public function count()
    {
        $iostat = json_decode(shell_exec('iostat -o JSON'), true);
        return (int)$iostat['sysstat']['hosts'][0]['number-of-cpus'];
    }

    public function type()
    {
        return explode("\n", shell_exec('grep "model name" /proc/cpuinfo | awk -F \':\' \'{print $2}\''));
    }

    public function average()
    {
        $iostat = json_decode(shell_exec('iostat -o JSON'), true);
        return (float)$iostat['sysstat']['hosts'][0]['statistics'][0]['avg-cpu']['user'];
    }

}

class Utility_Servidor_Linux_Hardware_Memory extends Utility_Servidor_Base_Datasource
{

    public function total()
    {
        return (int)shell_exec('free -m | grep ^Mem | awk \'{print $2}\'');
    }

    public function used()
    {
        return (int)shell_exec('free -m | grep ^Mem | awk \'{print $3}\'');
    }

    public function free()
    {
        return (int)shell_exec('free -m | grep ^Mem | awk \'{print $4}\'');
    }

    public function shared()
    {
        return (int)shell_exec('free -m | grep ^Mem | awk \'{print $5}\'');
    }

    public function buff_cache()
    {
        return (int)shell_exec('free -m | grep ^Mem | awk \'{print $6}\'');
    }

    public function available()
    {
        return (int)shell_exec('free -m | grep ^Mem | awk \'{print $7}\'');
    }

    public function usage()
    {
        return round(($this->used()/$this->total())*100, 1);
    }

}

class Utility_Servidor_Linux_Hardware_Disk extends Utility_Servidor_Base_Datasource
{

    public function usage($query = null)
    {
        if ($query) {
            $parts = explode("\n", trim(shell_exec('df -hT '.escapeshellarg($query).' | tail -n +2')));
        } else {
            $parts = explode("\n", trim(shell_exec('df -hT | tail -n +2')));
        }
        $df = [];
        foreach ($parts as $part) {
            $part = preg_replace('!\s+!', ' ', $part);
            list($filesystem, $type, $size, $used, $available, $usage, $mount) = explode(' ', $part);
            $usage = rtrim($usage, '%');
            $df[$mount] = compact('filesystem', 'type', 'size', 'used', 'available', 'usage', 'mount');
        }
        return $query ? array_pop($df) : $df;
    }

}

class Utility_Servidor_Linux_Network extends Utility_Servidor_Base_Datasource
{

    public function interfaces()
    {
        return shell_exec('cat /etc/network/interfaces');
    }

    public function dns()
    {
        return shell_exec('cat /etc/resolv.conf');
    }

    public function average($count = 3)
    {
        $samples = explode("\n", trim(shell_exec('ifstat -z -n -t -T 0.1 '.escapeshellarg((int)$count).' | tail -n +3')));
        $usage_total_in = 0;
        $usage_total_out = 0;
        foreach ($samples as $sample) {
            $sample = preg_replace('!\s+!', ' ', $sample);
            $aux = explode(' ', $sample);
            $n_aux = count($aux);
            $usage_total_in += (float)$aux[$n_aux-2];
            $usage_total_out += (float)$aux[$n_aux-1];
        }
        return ['time'=>date('Y-m-d H:i:s'), 'rx'=>round($usage_total_in/$count), 'tx'=>round($usage_total_in/$count)];
    }

}

class Utility_Servidor_Linux_Processes extends Utility_Servidor_Base_Datasource
{

    public function top_cpu()
    {
        return $this->top('CPU');
    }

    public function top_memory()
    {
        return $this->top('MEM');
    }

    private function top($field)
    {
        $processes = explode("\n", trim(explode('COMMAND', shell_exec('top -b -n 1 -o +%'.$field))[1]));
        $top = [];
        $i = 0;
        foreach ($processes as $process) {
            $process = trim(preg_replace('!\s+!', ' ', $process));
            list($pid, $user, $pr, $ni, $virt, $res, $shr, $s, $cpu, $mem, $time, $command) = explode(' ', $process, 12);
            $top[$pid] = compact('pid', 'user', 'pr', 'ni', 'virt', 'res', 'shr', 's', 'cpu', 'mem', 'time', 'command');
        }
        return $top;
    }

}

class Utility_Servidor_Linux_Services extends Utility_Servidor_Base_Datasource
{
}

class Utility_Servidor_Linux_Services_Memcached extends Utility_Servidor_Base_Datasource
{

    private $Cache;

    public function __construct($host = null, $port = null, $prefix = false)
    {
        $this->Cache = new \sowerphp\core\Cache($host, $port, $prefix);
    }

    public function __call($method, $args)
    {
        return $this->Cache->{'get'.ucfirst($method)}(...$args);
    }

}
