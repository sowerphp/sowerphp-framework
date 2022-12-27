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
 * Clase para generar los mantenedores
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2017-04-05
 */
class View_Helper_Maintainer extends View_Helper_Paginator
{

    /**
     * Método que generará el mantenedor y listará los registros disponibles
     * @param data Registros que se deben renderizar
     * @param pages Cantidad total de páginas que tienen los registros
     * @param page Página que se está revisando o 0 para no usar el paginador
     * @param create =true se agrega icono para crear registro
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2018-10-17
     */
    public function listar($data, $pages = 1, $page = 1, $create = true)
    {
        $buffer = $this->form->begin(array('onsubmit'=>'buscar(this)'))."\n";
        if ($create) {
            $buffer .= '<div class="float-start col-4"><a href="'.$this->options['link'].'/crear'.$this->options['listarFilterUrl'].'" title="Crear nuevo registro" class="btn btn-primary"><i class="fa fa-plus fa-fw" aria-hidden="true"></i></a></div>'."\n";
        }
        $this->options['link'] .= '/listar';
        if ($page) {
            $buffer .= $this->paginator ($pages, $page)."\n";
        }
        $buffer .= \sowerphp\general\View_Helper_Table::generate ($data, $this->options['thead']);
        $buffer .= $this->form->end(false)."\n";
        $buffer .= '<div class="text-end mb-2 small">'."\n";
        if ($page) {
            $buffer .= '<a href="'.$this->options['link'].'/0'.$this->options['linkEnd'].'">Mostrar todos los registros (sin paginar)</a>'."\n";
        } else {
            $buffer .= '<a href="'.$this->options['link'].'/1'.$this->options['linkEnd'].'">Paginar registros</a>'."\n";
        }
        $buffer .= '</div>'."\n";
        return $buffer;
    }

}
