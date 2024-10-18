<?php

/**
 * SowerPHP: Simple and Open Web Ecosystem Reimagined for PHP.
 * Copyright (C) SowerPHP <https://www.sowerphp.org>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero
 * de GNU publicada por la Fundación para el Software Libre, ya sea la
 * versión 3 de la Licencia, o (a su elección) cualquier versión
 * posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU
 * para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General
 * Affero de GNU junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace sowerphp\general;

/**
 * Clase para trabajar con fechas.
 */
class Utility_Date
{

    public static $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    public static $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    /**
     * Método que valida si la fecha es o no válida según el formato.
     * @param date Fecha que se quiere validar.
     * @param format Formato que se quiere validar.
     * @return bool `true` si la fecha está ok.
     * @link https://stackoverflow.com/a/13194398/3333009
     */
    public static function check($date, $format = 'Y-m-d')
    {
        $dt = \DateTime::createFromFormat($format, $date);
        return $dt !== false && !array_sum($dt->getLastErrors());
    }

    /**
     * Método que suma días hábiles a una fecha.
     * @param fecha Desde donde empezar.
     * @param dias Días que se deben sumar a la fecha.
     * @param feriados Días que no se deberán considerar al sumar.
     * @return string Fecha con los días hábiles sumados.
     */
    public static function addWorkingDays(string $fecha, int $dias, array $feriados = [])
    {
        // mover fecha los días solicitados
        $start = $end = strtotime($fecha);
        $dia = date('N', $start);
        if ($dias == 0) {
            if ($dia == 6) {
                $end = $start + 2 * 86400;
            } else if ($dia == 7) {
                $end = $start + 86400;
            }
        } else {
            $total = $dia + $dias;
            $fds = (int)($total / 5) * 2;
            if ($total % 5 == 0) {
                $fds -= 2;
            }
            $end = $start + ($dias+$fds)*86400;
        }
        $nuevaFecha = date('Y-m-d', $end);
        // ver si hay feriados, por cada feriado encontrado mover un día hábil
        // la fecha, hacer esto hasta que no hayan más días feriados en el rango
        // que se movió la fecha
        while (($dias=self::countDaysMatch($fecha, $nuevaFecha, $feriados, true)) != 0) {
            $fecha = date('Y-m-d', strtotime($nuevaFecha) + 86400);
            $nuevaFecha = self::addWorkingDays($nuevaFecha, $dias);
        }
        // retornar fecha
        return $nuevaFecha;
    }

    /**
     * Método que resta días hábiles a una fecha.
     * @param fecha Desde donde empezar.
     * @param dias Días que se deben restar a la fecha.
     * @param feriados Días que no se deberán considerar al restar.
     * @return string Fecha con los días hábiles restados.
     */
    public static function subtractWorkingDays(string $fecha, int $dias, array $feriados = [])
    {
        // mover fecha los días solicitados
        $start = $end = strtotime($fecha);
        $dia = date('N', $start);
        if ($dias == 0) {
            if ($dia == 6) {
                $end = $start - 86400;
            } else if ($dia == 7) {
                $end = $start - 2 * 86400;
            }
        } else {
            $total = $dia - $dias;
            $fds = $total > 0 ? (int)(abs($total)/5) * 2 : (int)(abs($total)/5) * 2 + 2;
            $end = $start - ($dias + $fds) * 86400;
        }
        $nuevaFecha = date('Y-m-d', $end);
        // ver si hay feriados, por cada feriado encontrado mover un día hábil
        // la fecha, hacer esto hasta que no hayan más días feriados en el rango
        // que se movió la fecha
        while (($dias=self::countDaysMatch($nuevaFecha, $fecha, $feriados, true))!=0) {
            $fecha = date('Y-m-d', strtotime($nuevaFecha)-86400);
            $nuevaFecha = self::subtractWorkingDays($nuevaFecha, $dias);
        }
        // retornar fecha
        return $nuevaFecha;
    }

    /**
     * Método que obtiene el número de día hábil dentro de un mes que
     * corresponde el día de la fecha que se está pasando.
     * @param fecha Fecha que se quiere saber que día hábil del mes correspone.
     * @param feriados Arreglo con los feriados del mes (si no se pasa solo se omitirán fin de semanas).
     * @return int|bool Número de día hábil del mes que corresponde la fecha pasada o =false si no es día hábil.
     */
    public static function whatWorkingDay($fecha, $feriados = [])
    {
        list($anio, $mes, $dia) = explode('-', $fecha);
        $desde = $anio.'-'.$mes.'-01';
        for($i=0; $i<$dia; $i++) {
            $f = self::addWorkingDays($desde, $i, $feriados);
            if ($f == $fecha) {
                return $i+1;
            }
        }
        return false;
    }

    /**
     * Método que obtiene la fecha de un día hábil X en un mes.
     * @param anio Año del día hábil que se busca.
     * @param mes Mes del día hábil que se busca.
     * @param dia_habil Número de día hábil dentro del mes y año que se busca.
     * @param feriados Arreglo con los feriados del mes (si no se pasa solo se omitirán fin de semanas).
     * @return string Fecha del día hábil.
     */
    public static function getWorkingDay($anio, $mes, $dia_habil, $feriados = [])
    {
        $fecha = self::addWorkingDays($anio.'-'.$mes.'-01', 0, $feriados); // obtiene primer día hábil
        $fecha = self::addWorkingDays($fecha, $dia_habil-1, $feriados);
        list($anio2, $mes2, $dia2) = explode('-', $fecha);
        return ($anio2 == $anio && $mes2 == $mes) ? $fecha : false;
    }

    /**
     * Método que indica si una fecha es el último día laboral del mes.
     * @param fecha Fecha que se quiere saber si es el último día laboral del mes.
     * @param feriados Arreglo con los feriados del mes (si no se pasa solo se omitirán fin de semanas).
     * @return bool `true` si es el último día laboral del mes.
     */
    public static function isLastWorkingDay($fecha, $feriados = [])
    {
        if (!self::whatWorkingDay($fecha, $feriados)) {
            return false;
        }
        $fecha2 = self::addWorkingDays($fecha, 1, $feriados);
        list($anio, $mes, $dia) = explode('-', $fecha);
        list($anio2, $mes2, $dia2) = explode('-', $fecha2);
        return ($anio2 == $anio && $mes2 == $mes) ? false : true;
    }

    /**
     * Método que cuenta cuantos de los días de la variable 'days' existen en el
     * rango desde 'from' hasta 'to'.
     * @param from Desde cuando revisar.
     * @param to Hasta cuando revisar.
     * @param days Días que se están buscando en el rango.
     * @param excludeWeekend =true se omitirán días que sean sábado o domingo.
     * @return int Cantidad de días que se encontraron en el rango.
     */
    public static function countDaysMatch($from, $to, $days, $excludeWeekend = false)
    {
        $count = 0;
        $date = strtotime($from);
        $end = strtotime($to);
        while($date <= $end) {
            $dayOfTheWeek = date('N', $date);
            if ($excludeWeekend && ($dayOfTheWeek==6 || $dayOfTheWeek==7)) {
                $date += 86400;
                continue;
            }
            if (in_array(date('Y-m-d', $date), $days)) {
                $count++;
            }
            $date += 86400;
        }
        return $count;
    }

    /**
     * Función para mostrar una fecha con hora con un formato "agradable".
     * @param timestamp Fecha en formto (de función date): Y-m-d H:i:s.
     * @param mostrarHora Si se desea (true) o no (false) mostrar la hora.
     * @param letrasFormato Si van en mayúscula ('u'), mínuscula ('l') o normal ('').
     * @param mostrarDia Si se incluye (=true) o no el día.
     */
    public static function timestamp2string($timestamp, $mostrarHora = true, $letrasFormato = '', $mostrarDia = true)
    {
        $puntoPos = strpos($timestamp, '.');
        if ($puntoPos) {
            $timestamp = substr($timestamp, 0, $puntoPos);
        }
        $unixtime = strtotime($timestamp);
        if ($mostrarDia) {
            $fecha = date('\D\I\A j \d\e \M\E\S \d\e\l Y', $unixtime);
        } else {
            $fecha = date('j \d\e \M\E\S \d\e\l Y', $unixtime);
        }
        if ($mostrarHora && strpos($timestamp, ':')) {
            $fecha .= ' a las '.date ('H:i', $unixtime);
        }
        $dia = self::$dias[date('w', $unixtime)];
        $mes = self::$meses[date('n', $unixtime)-1];
        if ($letrasFormato == 'l') {
            $dia = strtolower ($dia);
            $mes = strtolower ($mes);
        } else if ($letrasFormato == 'u') {
            $dia = strtoupper ($dia);
            $mes = strtoupper ($mes);
        }
        return str_replace(array('DIA', 'MES'), array($dia, $mes), $fecha);
    }

    /**
     * Método para transformar un string a una fecha.
     * @param fecha String a transformar (20100523 o 201005).
     * @param invertir =true si la fecha a normalizar parte con día o mes.
     * @return string Fecha transformada (2010-05-23 o 2010-05).
     */
    public static function normalize($fecha, $invertir = false)
    {
        if ($invertir) {
            if (strlen($fecha) == 6) {
                return $fecha[2].$fecha[3].$fecha[4].$fecha[5].'-'.$fecha[0].$fecha[1];
            } else if (strlen($fecha) == 8) {
                return $fecha[4].$fecha[5].$fecha[6].$fecha[7].'-'.$fecha[2].$fecha[3].'-'.$fecha[0].$fecha[1];
            }
        } else {
            if (strlen($fecha) == 6) {
                return $fecha[0].$fecha[1].$fecha[2].$fecha[3].'-'.$fecha[4].$fecha[5];
            } else if (strlen($fecha) == 8) {
                return $fecha[0].$fecha[1].$fecha[2].$fecha[3].'-'.$fecha[4].$fecha[5].'-'.$fecha[6].$fecha[7];
            }
        }
        return $fecha;
    }

    /**
     * Método que calcula los años que han pasado a partir de una fecha.
     * @param fecha Desde cuando calcular los años.
     * @return int Años que han pasado desde la fecha indicada.
     * @link http://es.wikibooks.org/wiki/Programaci%C3%B3n_en_PHP/Ejemplos/Calcular_edad
     */
    public static function age($fecha)
    {
        list($Y, $m, $d) = explode('-', $fecha);
        return date('md') < $m.$d ? date('Y') - $Y - 1 : date('Y') - $Y;
    }

    /**
     * Método que calcula cuanto tiempo ha pasado desde cierta fecha y hora y lo
     * entrega en un string que representa dicho tiempo.
     * @param datetime Fecha y hora en cualquier formato soportado por clase \DateTime.
     * @param full Si se debe mostrar todo el string o solo una parte.
     * @return string String con el tiempo que ha pasado para la fecha.
     * @link http://stackoverflow.com/a/18602474
     */
    public static function ago($datetime, $full = false)
    {
        $now = new \DateTime();
        $ago = new \DateTime($datetime);
        $diff = $now->diff($ago);
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
        $string = array(
            'y' => 'año',
            'm' => 'mes',
            'w' => 'semana',
            'd' => 'día',
            'h' => 'hora',
            'i' => 'minuto',
            's' => 'segundo',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? ($k=='m'?'es':'s') : '');
            } else {
                unset($string[$k]);
            }
        }
        if (!$full) {
            $string = array_slice($string, 0, count($string) >= 2 ? 2 : 1);
        }
        return $string ? 'hace '.implode(', ', $string) : 'recién';
    }

    /**
     * Método que calcula cuanto tiempo ha pasado desde cierta fecha y hora y lo
     * entrega como la cantidad de días.
     * @param from Fecha desde cuando contar.
     * @param to Fecha hasta cuando contar (si es null será la fecha actual).
     * @return int Días que han pasado entre las fechas.
     */
    public static function count($from, $to = null)
    {
        $now = !$to ? new \DateTime() : new \DateTime($to);
        $ago = new \DateTime($from);
        $diff = $now->diff($ago);
        return $diff->days;
    }

    /**
     * Método que aplica un formato en particular a un timestamp.
     * @param datetime Fecha y hora (http://php.net/manual/es/datetime.formats.php)
     * @param format Formato de salida requerido (http://php.net/manual/es/function.date.php)
     * @return string Fecha formateada según formato solicitado
     */
    public static function format($datetime, $format = 'd/m/Y')
    {
        if (!$datetime) {
            return null;
        }
        return date($format, strtotime($datetime));
    }

    /**
     * Método que obtiene la fecha a partir de un número serial.
     * @param n número serial.
     * @return string Fecha en formato YYYY-MM-DD.
     */
    public static function fromSerialNumber($n)
    {
        return date('Y-m-d', ($n - 25568) * 86400);
    }

    /**
     * Método que obtiene un periodo (mes) siguiente a uno específicado.
     * @param periodo Período para el cual se quiere saber el siguiente o =null para actual.
     * @return int Periodo en formato YYYYMM.
     */
    public static function nextPeriod(?int $periodo = null, $mover = 1): int
    {
        if (!$periodo) {
            $periodo = (int)date('Ym');
        }
        if ($mover < 0) {
            return (int)self::previousPeriod($periodo, $mover * -1);
        }
        if ($mover == 0) {
            return (int)$periodo;
        }
        if ($mover > 1) {
            return (int)self::nextPeriod(self::nextPeriod($periodo), $mover - 1);
        }
        $periodo_siguiente = $periodo + 1;
        if (substr($periodo_siguiente, 4) == '13') {
            $periodo_siguiente = $periodo_siguiente + 100 - 12;
        }
        return (int)$periodo_siguiente;
    }

    /**
     * Método que obtiene un periodo (mes) anterior a uno específicado.
     * @param periodo Período para el cual se quiere saber el anterior o =null para actual.
     * @return int Periodo en formato YYYYMM.
     */
    public static function previousPeriod(?int $periodo = null, int $mover = 1)
    {
        if (!$periodo) {
            $periodo = (int)date('Ym');
        }
        if ($mover < 0) {
            return (int)self::nextPeriod($periodo, $mover * -1);
        }
        if ($mover == 0) {
            return (int)$periodo;
        }
        if ($mover > 1) {
            return (int)self::previousPeriod(self::previousPeriod($periodo), $mover - 1);
        }
        $periodo_anterior = $periodo - 1;
        if (substr((string)$periodo_anterior, 4) == '00') {
            $periodo_anterior = $periodo_anterior - 100 + 12;
        }
        return (int)$periodo_anterior;
    }

    /**
     * Método que entrega el último día de un período.
     * @return string Último día del período.
     */
    public static function lastDayPeriod($periodo = null)
    {
        if (!$periodo) {
            $periodo = date('Ym');
        }
        $periodoSiguiente = self::nextPeriod($periodo);
        $primerDia = self::normalize($periodoSiguiente.'01');
        return self::getPrevious($primerDia, 'D', 1);
    }

    /**
     * Método que valida un período con formato AAAA y AAAAMM en un rango de años.
     */
    public static function validPeriod($period, $year_from = 2000, $year_to = 2100, $length = null)
    {
        $n_period = strlen((string)(int)$period);
        if ($length !== null && $length != $n_period) {
            return false;
        }
        if ($n_period == 4) {
            $period = (int)$period;
            return ($period >= $year_from && $period <= $year_to);
        }
        else if ($n_period == 6) {
            $year = (int)substr((string)$period, 0, 4);
            $month = (int)substr((string)$period, 4);
            return ($year >= $year_from && $year <= $year_to && $month >= 1 && $month <= 12);
        }
        else {
            return false;
        }
    }

    /**
     * Método que valida un período con formato AAAA en un rango de años.
     */
    public static function validPeriod4($period, $year_from = 2000, $year_to = 2100)
    {
        return self::validPeriod($period, $year_from, $year_to, 4);
    }

    /**
     * Método que valida un período con formato AAAAMM en un rango de años.
     */
    public static function validPeriod6($period, $year_from = 2000, $year_to = 2100)
    {
        return self::validPeriod($period, $year_from, $year_to, 6);
    }

    /**
     * Método que calcula cuantos meses han pasado entre dos fecha.
     * @param from Fechas desde cuando contar.
     * @param to Fecha hasta cual contar.
     * @return int Meses que han pasado entre las fechas.
     * @link http://stackoverflow.com/a/4233624
     */
    public static function countMonths($from, $to = null)
    {
        if (!$to) {
            $to = date('Y-m-d');
        }
        if (is_numeric($from)) {
            $from = self::normalize($from.'01');
        }
        if (is_numeric($to)) {
            $to = self::normalize($to.'01');
        }
        $d1 = new \DateTime($from);
        $d2 = new \DateTime($to);
        return $d1->diff($d2)->m + ($d1->diff($d2)->y * 12);
    }

    /**
     * Método que obtiene la siguiente fecha a partir de una fecha y una frecuencia.
     * @param fecha Fecha actual a la que se quiere obtener la siguiente.
     * @param tiempo Tiempo que se agregará a la fecha actual: A:año, M:mes, S:semana, D:día.
     * @param cantidad Cantidad de 'frecuencia' a agregar.
     * @return string Nueva fecha en formato YYYY-MM-DD.
     */
    public static function getNext($fecha = null, $tiempo = 'M', $cantidad = 1, $operacion = '+')
    {
        if (!$fecha) {
            $fecha = date('Y-m-d');
        }
        if ($tiempo == 'A') {
            list($y, $m, $d) = explode('-', $fecha);
            $y = $operacion == '+' ? ($y + $cantidad) : ($y - $cantidad);
            if ($m == '02' && $d == 29) {
                $d = 28;
            }
            return $y.'-'.$m.'-'.$d;
        }
        else if ($tiempo == 'M') {
            list($y, $m, $d) = explode('-', $fecha);
            $siguientePeriodo = $y.$m;
            for ($i=0; $i<$cantidad; $i++) {
                if ($operacion == '+') {
                    $siguientePeriodo = self::nextPeriod($siguientePeriodo);
                } else {
                    $siguientePeriodo = self::previousPeriod($siguientePeriodo);
                }
            }
            $siguienteFecha = self::normalize($siguientePeriodo.$d);
            list($y, $m, $d) = explode('-', $siguienteFecha);
            if ($m == '02' && $d > 28) {
                $d = 28;
            } else if (in_array($m, ['04', '06', '09', '11']) && $d > 30) {
                $d = 30;
            }
            return $y.'-'.$m.'-'.$d;
        }
        else if ($tiempo == 'S') {
            return self::getNext($fecha, 'D', 7*$cantidad, $operacion);
        }
        else if ($tiempo == 'D') {
            $date = new \DateTime($fecha);
            if ($operacion == '+') {
                $date->add(new \DateInterval('P'.$cantidad.'D'));
            } else {
                $date->sub(new \DateInterval('P'.$cantidad.'D'));
            }
            return $date->format('Y-m-d');
        }
        else if (is_numeric($tiempo)) {
            return self::getNext($fecha, 'M', (int)$tiempo, $operacion);
        }
        else {
            return false;
        }
    }

    /**
     * Método que obtiene la anterior fecha a partir de una fecha y una frecuencia.
     * @param fecha Fecha actual a la que se quiere obtener la anterior.
     * @param tiempo Tiempo que se quitará a la fecha actual: A:año, M:mes, S:semana, D:día.
     * @param cantidad Cantidad de 'frecuencia' a quitar.
     * @return string Nueva fecha en formato YYYY-MM-DD.
     */
    public static function getPrevious($fecha = null, $tiempo = 'M', $cantidad = 1)
    {
        return self::getNext($fecha, $tiempo, $cantidad, '-');
    }

    /**
     * Método que entrega el primer día de la semana.
     * @return string Primer Día de la semana.
     */
    public static function firstDayWeek($date = null)
    {
        if (!$date) {
            $date = date('Y-m-d');
        }
        $days = date('N', strtotime($date)) - 1;
        return self::getPrevious($date, 'D', $days);
    }

    /**
     * Método que entrega el último día de la semana.
     * @return string Último Día de la semana.
     */
    public static function lastDayWeek($date = null)
    {
        if (!$date) {
            $date = date('Y-m-d');
        }
        $days = 7 - date('N', strtotime($date));
        return self::getNext($date, 'D', $days);
    }

    /**
     * Método que entrega un listado de años.
     * @return array Arreglo decreciente con el listado de años.
     */
    public static function years($total_years, $from = null)
    {
        if (!$from) {
            $from = date('Y');
        }
        $years = [];
        $year_i = $from;
        $year_f = $year_i - $total_years + 1;
        for ($year = $year_i; $year >= $year_f; $year--) {
            $years[] = $year;
        }
        return $years;
    }

    /**
     * Método que entrega la cantidad de días que tiene un mes.
     * @return int Cantidad de días.
     */
    public static function daysInMonth($periodo): int
    {
        $anio = (int)substr($periodo, 0, 4);
        $mes = (int)substr($periodo, 4);
        $date = new \DateTime("$anio-$mes-01");
        return (int)$date->format('t');
    }


}
