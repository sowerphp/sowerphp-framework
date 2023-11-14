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
 * Clase para generar datos paginados
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2017-04-05
 */
class View_Helper_Paginator extends \sowerphp\general\View_Helper_Table
{

    protected $options; ///< Opciones del mantenedor
    protected $form; ///< Formulario (objeto de FormHelper) que se está usando en el mantenedor

    /**
     * Constructor de la clase
     * @param array $options Arreglo con las opciones para el mantenedor
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2023-03-20
     */
    public function __construct(array $options = [], $filter = true)
    {
        if (!isset($options['filter'])) {
            $options['filter'] = (bool)$filter;
        }
        if ($options['filter']) {
            $this->options = array_merge([
                'link'=>'', 'linkEnd'=>'', 'listarFilterUrl'=>'', 'thead'=>2, 'remove'=>[2],
            ], $options);
        } else {
            $this->options = array_merge([
                'link'=>'', 'linkEnd'=>'', 'listarFilterUrl'=>'', 'thead'=>1, 'remove'=>[],
            ], $options);
        }
        if (!isset($this->options['remove_cols'])) {
            $this->options['remove_cols'] = [-1];
        }
        $this->form = new \sowerphp\general\View_Helper_Form('normal');
        $this->setExport(true);
        $this->setExportRemove([
            'rows' => $this->options['remove'],
            'cols' => $this->options['remove_cols'],
        ]);
    }

    /**
     * Método que generará el paginador y listará los registros disponibles
     * @param data Registros que se deben renderizar
     * @param pages Cantidad total de páginas que tienen los registros
     * @param page Página que se está revisando o 0 para no usar el paginador
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-04-05
     */
    public function generate($data, $pages = 1, $page = 1)
    {
        $buffer = $this->form->begin(['onsubmit'=>'buscar(this)'])."\n";
        if ($page) {
            $buffer .= $this->paginator($pages, $page)."\n";
        }
        $buffer .= parent::generate($data, $this->options['thead']);
        $buffer .= $this->form->end(false)."\n";
        if ($pages) {
            $buffer .= '<div class="text-end mb-2 small">'."\n";
            if ($page) {
                $buffer .= '<a href="'.$this->options['link'].'/0'.$this->options['linkEnd'].'">Mostrar todos los registros (sin paginar)</a>'."\n";
            } else {
                $buffer .= '<a href="'.$this->options['link'].'/1'.$this->options['linkEnd'].'">Paginar registros</a>'."\n";
            }
            $buffer .= '</div>'."\n";
        }
        return $buffer;
    }

    /**
     * Método que genera el paginador para el mantenedor
     * @param pages Cantidad total de páginas que tienen los registros
     * @param page Página que se está revisando o 0 para no usar el paginador
     * @param groupOfPages De a cuantas páginas se mostrará en el paginador
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2018-10-15
     */
    protected function paginator($pages, $page, $groupOfPages = 10)
    {
        if (!$pages) {
            return;
        }
        // cálculoss necesarios para crear enlaces
        $group = ceil($page/$groupOfPages);
        $from = ($group-1)*$groupOfPages + 1;
        $to = min($from+$groupOfPages-1, $pages);
        // crear enlaces para paginador
        $buffer = '<div class="float-start w-50 d-flex justify-content-center" style="margin-left:20%"><nav><ul class="pagination">'."\n";
        $buffer .= '<li class="page-item'.($page==1?' disabled':'').'"><a href="'.$this->options['link'].'/1'.$this->options['linkEnd'].'" title="Ir a la primera página" class="page-link"><i class="fa fa-fast-backward fa-fw" aria-hidden="true"></i></a></li>';
        $buffer .= '<li class="page-item'.($group==1?' disabled':'').'"><a href="'.$this->options['link'].'/'.($from-1).$this->options['linkEnd'].'" title="Ir al grupo de páginas anterior (página '.($from-1).')" class="page-link"><i class="fa fa-backward fa-fw" aria-hidden="true"></i></a></li>';
        for ($i=$from; $i<=$to; $i++) {
            if ($page==$i) {
                $buffer .= '<li class="page-item active"><a href="#" onclick="return false" class="page-link">'.$i.'</a></li>';
            } else {
                $buffer .= '<li class="page-item"><a href="'.$this->options['link'].'/'.$i.$this->options['linkEnd'].'" title="Ir a la página '.$i.'" class="page-link">'.$i.'</a></li>';
            }
        }
        $buffer .= '<li class="page-item'.($group==ceil($pages/$groupOfPages)?' disabled':'').'"><a href="'.$this->options['link'].'/'.($to+1).$this->options['linkEnd'].'" title="Ir al grupo de páginas siguiente (página '.($to+1).')" class="page-link"><i class="fa fa-forward fa-fw" aria-hidden="true"></i></a></li>';
            $buffer .= '<li class="page-item'.($page==$pages?' disabled':'').'"><a href="'.$this->options['link'].'/'.$pages.$this->options['linkEnd'].'" title="Ir a la última página" class="page-link"><i class="fa fa-fast-forward fa-fw" aria-hidden="true"></i></a></li>';
        $buffer .= '</ul></nav></div>'."\n";
        // retornar enlaces
        return $buffer;
    }

}
