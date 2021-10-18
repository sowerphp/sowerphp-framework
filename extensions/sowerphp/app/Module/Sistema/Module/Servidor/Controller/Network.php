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
 * Controlador para las acciones asociadas a la red
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2019-07-24
 */
class Controller_Network extends \Controller_App
{

    /**
     * Acción que muestra información de la red
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-07-24
     */
    public function info()
    {
        $Servidor = new Utility_Servidor_Linux();
        $this->set([
            'interfaces' => $Servidor->network()->interfaces(),
            'dns' => $Servidor->network()->dns(),
        ]);
    }

    /**
     * Servicio web entrega el promedio de RX y TX en la red
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-07-24
     */
    public function _api_average_GET()
    {
        // obtener datos históricos
        $averages = $this->Cache->get('servidor_network_averages');
        if (!$averages) {
            $averages = [];
        }
        // obtener dato actual
        $Servidor = new Utility_Servidor_Linux();
        $average = $Servidor->network()->average();
        // agregar dato actual a los históricos
        $averages[] = $average;
        // guardar datos históricos en caché para consulta futura
        $this->Cache->set('servidor_network_averages', array_slice($averages, -100));
        // entregar datos al cliente
        $this->Api->send($averages);
    }

}
