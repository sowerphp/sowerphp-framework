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
 * Clase para generar página de configuración de aplicaciones
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2019-06-12
 */
class View_Helper_AppsConfig
{

    protected $id; ///< ID para las apps que se están configurando
    protected $form; ///< Formulario (objeto de FormHelper) que se está usando en el helper

    /**
     * Constructor de la clase
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-06-12
     */
    public function __construct($id , $form)
    {
        $this->id = $id;
        $this->form = $form;
    }

    /**
     * Método que generará la configuración de una aplicación.
     * Para generar la de múltiples aplicaciones se deberá iterar con este
     * método.
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2019-06-27
     */
    public function generate($App)
    {
        $buffer = '';
        $buffer .= '<div class="card mb-4">'."\n";
        $buffer .= '    <div class="card-body">'."\n";
        $buffer .= '        <div class="float-end">'."\n";
        $buffer .= '            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#app-'.$App->getCodigo().'">'."\n";
        $buffer .= '                <i class="fas fa-cogs fa-fw"></i>'."\n";
        $buffer .= '            </button>'."\n";
        $buffer .= '        </div>'."\n";
        $buffer .= '        <img src="'.$App->getLogo().'" alt="Logo '.$App->getNombre().'" class="float-start me-4" style="max-width:32px" />'."\n";
        $buffer .= '        <h5 class="card-title">'.$App->getNombre().'</h5>'."\n";
        $buffer .= '        <p class="small text-muted">'.$App->getDescripcion().'</p>'."\n";
        $buffer .= '    </div>'."\n";
        $buffer .= '</div>'."\n";
        $buffer .= '<div class="modal fade" id="app-'.$App->getCodigo().'" tabindex="-1" role="dialog" aria-labelledby="'.$App->getCodigo().'Label" aria-hidden="true">'."\n";
        $buffer .= '    <div class="modal-dialog modal-lg" role="document">'."\n";
        $buffer .= '        <div class="modal-content">'."\n";
        $buffer .= '            <div class="modal-header">'."\n";
        $buffer .= '                <h5 class="modal-title" id="'.$App->getCodigo().'Label">'."\n";
        $buffer .= '                    <img src="'.$App->getLogo().'" alt="Logo '.$App->getNombre().'" class="float-start me-4" style="max-width:32px" />'."\n";
        $buffer .= '                    '.$App->getNombre().''."\n";
        $buffer .= '                </h5>'."\n";
        $buffer .= '                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">'."\n";

        $buffer .= '                </button>'."\n";
        $buffer .= '            </div>'."\n";
        $buffer .= '            <div class="modal-body">'."\n";
        $buffer .= '                <p>'.$App->getDescripcion().'</p>'."\n";
        $buffer .= $App->getConfigPageHTML($this->form);
        $buffer .= '            </div>'."\n";
        $buffer .= '            <div class="modal-footer">'."\n";
        if ($App->getURL()) {
            $buffer .= '            <a href="'.$App->getURL().'" class="btn btn-primary" target="_blank">Ir a '.$App->getNombre().'</a>'."\n";
        }
        $buffer .= '                <button type="submit" name="'.str_replace('\\', '_', $App->getNamespace()).'_'.$App->getCodigo().'SubmitApp" class="btn btn-primary">Guardar configuración</button>'."\n";
        $buffer .= '            </div>'."\n";
        $buffer .= '        </div>'."\n";
        $buffer .= '    </div>'."\n";
        $buffer .= '</div>'."\n";
        return $buffer;
    }

}
