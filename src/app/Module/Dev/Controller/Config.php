<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
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

namespace sowerphp\app\Dev;

/**
 * Controlador para las acciones relacionadas con la configuración de la
 * aplicación
 */
class Controller_Config extends \sowerphp\autoload\Controller
{

    /**
     * Servicio web que permite obtener el nombre del layout de la aplicación.
     */
    public function _api_layout_GET()
    {
        return config('app.ui.layout');
    }

    /**
     * Servicio web que permite obtener el menú de la aplicación web.
     */
    public function _api_nav_app_GET()
    {
        $this->Api->send(config('nav.app'), 200);
    }

    /**
     * Servicio web que permite obtener el correo que usa la aplicación.
     */
    public function _api_email_GET()
    {
        $default = config('mail.default');
        $mailer = config('mail.mailers.' . $default);
        return $mailer['username'] ?? null;
    }

    /**
     * Servicio web que permite obtener los módulos cargados en la aplicación.
     */
    public function _api_modules_GET()
    {
        $this->Api->send(app('module')->getLoadedModules());
    }

}
