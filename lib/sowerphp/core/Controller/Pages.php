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
 * Clase para cargar una página y entregarla al usuario
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-03-23
 */
class Controller_Pages extends \Controller_App
{

    /**
     * Método que se ejecuta antes de ejecutar la acción del controlador
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-04-28
     */
    public function beforeFilter()
    {
        if (isset($this->Auth))
            $this->Auth->allow('display');
        parent::beforeFilter();
    }

    /**
     * Renderizar página
     * @page page Página que se desea renderizar ubicada en View/Pages
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-05-18
     */
    public function display ($page)
    {
        if ($this->autoRender) {
            $this->autoRender = false;
            $this->render ('Pages'.$page);
        }
    }

}
