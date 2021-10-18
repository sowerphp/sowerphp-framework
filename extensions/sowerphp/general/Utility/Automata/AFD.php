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

namespace sowerphp\general;

/**
 * Clase para trabajar con un autómata finito determinístico (AFD)
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2017-09-13
 */

class Utility_Automata_AFD
{

    private $transitions; ///< Transiciones del autómata
    private $q0; ///< Estado inicial del autómata
    private $F; ///< Conjunto de estados finales
    private $status; ///< Estado en que se detuvo el AFD
    private $input; ///< Entrada en la que se detuvo el AFD

    /**
     * Constructor de la clase
     *
     * Ejemplo de llamada:
     * \code
        $t = array(0=>array('a'=>1, 'b'=>'0'), 1=>array('a'=>0, 'b'=>'1'));
        $F = array(1);
        $q0 = 0;
        $afd = new \sowerphp\general\Utility_Automata_AFD($t, $F, $q0);
        \endcode
     *
     * @param transitions Transiciones definidas para el AFD
     * @param F Estados finales de aceptación
     * @param q0 Estado inicial del AFD
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-03-30
     */
    public function __construct($transitions = array(), $F = array(), $q0 = 0)
    {
        $this->transitions = $transitions;
        $this->F = $F;
        $this->q0 = $q0;
    }

    /**
     * Método que evalua la entrada según las transiciones del autómata
     * @param input Entrada para el AFD (un string o un arreglo de símbolos)
     * @return =true si el estado de detención es de aceptación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2013-08-15
     */
    public function run($input)
    {
        $estado = $this->q0;
        $simbols = is_array($input) ? count($input) : strlen($input);
        for ($i=0; $i<$simbols; ++$i) {
            if (isset($this->transitions[$estado][$input[$i]])) {
                $this->input = $input[$i];
                $estado = $this->transitions[$estado][$input[$i]];
            }
        }
        $this->status = $estado;
        return in_array($estado, $this->F);
    }

    /**
     * Obtener el estado final en que se detuvo el AFD
     * @return Entrega el estado donde se detuvo el AFD después de correr
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2013-08-17
     */
    public function getFinalState()
    {
        return $this->status;
    }

    /**
     * Obtener la entrada final en que se detuvo el AFD
     * @return Entrega el estado donde se detuvo el AFD después de correr
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-09-13
     */
    public function getFinalInput()
    {
        return $this->input;
    }

    /**
     * Método que entrega el grafo del AFD
     *
     * Requiere que esté la biblioteca clue/graph de composer
     *
     * @return Grafo en instancia de la clase \Fhaculty\Graph\Graph
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-03-29
     */
    public function getGraph()
    {
        require_once DIR_FRAMEWORK.'/extensions/sowerphp/general/Vendor/autoload.php';
        $graph = new \Fhaculty\Graph\Graph();
        // crear estados y transiciones
        $vertexs = [];
        $edges = [];
        foreach ($this->transitions as $from => $data) {
            if (!isset($vertexs[$from])) {
                $vertexs[$from] = $graph->createVertex($from);
                if ($from == $this->q0) {
                    $vertexs[$from]->setAttribute('graphviz.style', 'filled');
                    $vertexs[$from]->setAttribute('graphviz.fillcolor', 'gray');
                }
            }
            foreach ($data as $valor => $to) {
                if (!isset($vertexs[$to]))
                    $vertexs[$to] = $graph->createVertex($to);
                if (!isset($edges[$from][$to])) {
                    $vertexs[$from]->createEdgeTo($vertexs[$to]);
                    $edges[$from][$to] = [];
                }
                $edges[$from][$to][] = $valor;
            }
        }
        // agregar valores de las transiciones
        foreach ($vertexs as $v) {
            if (!isset($edges[$v->getId()]))
                continue;
            $aux = $edges[$v->getId()];
            foreach ($aux as $to => $valores) {
                $e = $v->getEdgesTo($vertexs[$to])->getEdgeFirst();
                $e->setAttribute('graphviz.label', implode(',', $valores));
                $e->setAttribute('graphviz.fontsize', 12);
            }
        }
        return $graph;
    }

    /**
     * Método que entrega los datos de la imagen PNG del grafo del AFD
     *
     * Requiere: GraphViz y que esté la biblioteca graphp/graphviz de composer
     *
     * @return Datos de una imagen PNG
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-03-29
     */
    public function image()
    {
        $graph = $this->getGraph();
        $graphviz = new \Graphp\GraphViz\GraphViz();
        return $graphviz->createImageData($graph);
    }

}
