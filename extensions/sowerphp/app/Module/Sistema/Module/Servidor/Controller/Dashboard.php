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

namespace sowerphp\app\Sistema\Servidor;

/**
 * Controlador para el dashboard de las estadísticas del servidor
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2019-07-23
 */
class Controller_Dashboard extends \Controller_App
{

    /**
     * Acción que muestra el dashboard
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-07-24
     */
    public function index()
    {
        $Servidor = new Utility_Servidor_Linux();
        $this->set([
            'hostname' => $Servidor->config()->hostname(),
            'p_cpu' => $Servidor->hardware()->cpu()->average(),
            'p_memory' => $Servidor->hardware()->memory()->usage(),
            'partition_app' => $Servidor->hardware()->disk()->usage(__FILE__),
            'network_average' => $Servidor->network()->average(),
            'uname' => $Servidor->config()->uname(),
            'uptime' => $Servidor->config()->uptime(),
            'date' => $Servidor->config()->date(),
            'cpu_count' => $Servidor->hardware()->cpu()->count(),
            'cpu_type' => $Servidor->hardware()->cpu()->type(),
            'memory_total' => $Servidor->hardware()->memory()->total(),
            'memory_used' => $Servidor->hardware()->memory()->used(),
            'memory_free' => $Servidor->hardware()->memory()->free(),
            'memory_shared' => $Servidor->hardware()->memory()->shared(),
            'memory_buff_cache' => $Servidor->hardware()->memory()->buff_cache(),
            'memory_available' => $Servidor->hardware()->memory()->available(),
            'disks' => $Servidor->hardware()->disk()->usage(),
            'memcached' => $Servidor->services()->memcached()->stats(),
        ]);
    }

}
