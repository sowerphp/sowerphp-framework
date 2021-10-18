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

namespace sowerphp\app\Dev;

/**
 * Controlador para las acciones relacionadas con la configuración de la
 * aplicación
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2017-01-26
 */
class Controller_Config extends \Controller_App
{

    /**
     * Servicio web que permite obtener el nombre del layout de la aplicación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-01-26
     */
    public function _api_layout_GET()
    {
        return \sowerphp\core\Configure::read('page.layout');
    }

    /**
     * Servicio web que permite obtener el menú de la aplicación web
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-01-26
     */
    public function _api_nav_app_GET()
    {
        $this->Api->send(\sowerphp\core\Configure::read('nav.app'), 200, JSON_PRETTY_PRINT);
    }

    /**
     * Servicio web que permite obtener el correo que usa la aplicación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-01-26
     */
    public function _api_email_GET()
    {
        return \sowerphp\core\Configure::read('email.default.user');
    }

    /**
     * Servicio web que permite obtener los módulos cargados en la aplicación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-01-26
     */
    public function _api_modulos_GET()
    {
        $this->Api->send(\sowerphp\core\Module::loaded(), 200, JSON_PRETTY_PRINT);
    }

}
