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
 * Base de fuente de datos para obtener los datos de servidores
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2019-07-23
 */
abstract class Utility_Servidor_Base_Datasource
{

    protected $datasources = []; ///< Caché para los orígenes de datos

    /**
     * Método para encapsular todas las llamadas a los orígenes de datos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-07-23
     */
    public function __call($method, $args)
    {
        $class_base = get_class($this);
        $class = $class_base.'_'.ucfirst($method);
        if (empty($this->datasources[$class])) {
            if (!class_exists($class)) {
                throw new \Exception(__(
                    'El origen de datos %s::%s() no existe',
                    str_replace('Utility_Servidor_', '', end(explode('\\', $class_base))),
                    $method
                ));
            }
            $this->datasources[$class] = new $class(...$args);
        }
        return $this->datasources[$class];
    }

}
