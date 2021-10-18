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

namespace sowerphp\app;

/**
 * Controlador para mostrar páginas (vistas) de documentación
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2014-09-23
 */
class Controller_Documentacion extends \Controller_App
{

    /**
     * Se permite el acceso a la documentación a usuarios con sesión iniciada
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-13
     */
    public function beforeFilter ()
    {
        $this->Auth->allowWithLogin('index');
        parent::beforeFilter();
    }

    /**
     * Acción que mostrará el índice de la documentación y además renderizará
     * la página de documentación que se haya solicitado
     * @param page Página que se desea renderizar (serán varios argumentos)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-01
     */
    public function index($page = null)
    {
        if ($page==null) {
            $archivos = array_merge_recursive(
                $this->archivos(DIR_WEBSITE.'/View/Documentacion'),
                $this->archivos(\sowerphp\core\App::layer('sowerphp/app').'/sowerphp/app/View/Documentacion')
            );
            $this->set([
                'doxygen' => is_dir(DIR_WEBSITE.'/webroot/doc/html'),
                'archivos' => $archivos,
            ]);
        } else {
            $this->autoRender = false;
            $view = urldecode(implode('/', func_get_args()));
            $this->render('Documentacion/'.$view);
        }
    }

    /**
     * Método que realiza la búsqueda de los archivos que hay disponibles para
     * ser mostrado como documentación.
     * @param dir Directorio donde se está buscando la documentación
     * @return Arreglo con la estructura de directorio y archivos dentro de ellos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-10-25
     */
    private function archivos($base, $dir = '')
    {
        $archivos = [];
        $realdir = $base.$dir;
        if (!is_dir($realdir)) return [];
        $files = scandir($realdir);
        foreach ($files as &$file) {
            if ($file[0]=='.' || $file=='index.php' || !is_readable($realdir.'/'.$file)) continue;
            if (is_dir($realdir.'/'.$file)) {
                $archivos[$file] = $this->archivos($base, $dir.'/'.$file);
            } else {
                $archivos[] = substr($file, 0, strpos($file, '.'));
            }
        }
        return $archivos;
    }

}
