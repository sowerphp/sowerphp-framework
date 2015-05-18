<?php

/**
 * SowerPHP: Minimalist Framework for PHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

namespace sowerphp\core;

/**
 * Clase para realizar acciones de la sesión
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-23
 */
class Controller_Session extends \Controller_App
{

    public function beforeFilter()
    {
        if (isset($this->Auth))
            $this->Auth->allow('config');
        parent::beforeFilter();
    }

    /**
     * Acción para poder cambiar la configuración de la sesión a través de la
     * URL
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-22
     */
    public function config ($var, $val, $redirect = null)
    {
        Model_Datasource_Session::write ('config.'.$var, $val);
        if (!$redirect)
            $this->redirect ('/');
        else
            $this->redirect (base64_decode($redirect));
    }

}
