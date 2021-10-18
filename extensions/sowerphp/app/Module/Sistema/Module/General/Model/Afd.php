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
namespace sowerphp\app\Sistema\General;

/**
 * Clase para mapear la tabla afd de la base de datos
 * Comentario de la tabla:
 * Esta clase permite trabajar sobre un registro de la tabla afd
 * @author SowerPHP Code Generator
 * @version 2014-12-19 18:06:09
 */
class Model_Afd extends \Model_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'afd'; ///< Tabla del modelo

    // Atributos de la clase (columnas en la base de datos)
    public $codigo; ///< varchar(10) NOT NULL DEFAULT '' PK
    public $nombre; ///< varchar(50) NOT NULL DEFAULT ''

    // Información de las columnas de la tabla en la base de datos
    public static $columnsInfo = array(
        'codigo' => array(
            'name'      => 'Codigo',
            'comment'   => '',
            'type'      => 'varchar',
            'length'    => 10,
            'null'      => false,
            'default'   => '',
            'auto'      => false,
            'pk'        => true,
            'fk'        => null
        ),
        'nombre' => array(
            'name'      => 'Nombre',
            'comment'   => '',
            'type'      => 'varchar',
            'length'    => 50,
            'null'      => false,
            'default'   => '',
            'auto'      => false,
            'pk'        => false,
            'fk'        => null
        ),

    );

    // Comentario de la tabla en la base de datos
    public static $tableComment = '';

    public static $fkNamespace = array(); ///< Namespaces que utiliza esta clase

    // variables para guardar estados y transiciones
    public $estados = [];
    public $transiciones = [];

    /**
     * Contructor para el modelo AFD
     * @param codigo Código del AFD en la BD
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-12-20
     */
    public function __construct($codigo = null)
    {
        parent::__construct($codigo);
        $this->afd = &$this->nombre;
    }

    /**
     * Método que guarda el AFD
     * @param estados Arreglo con arreglo de codigos y nombres de estados
     * @param transiciones Arreglo con arreglo de desdes, valor y hastas de las transiciones
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-12-19
     */
    public function save()
    {
        $this->db->beginTransaction();
        parent::save();
        $this->saveEstados(
            $this->estados['codigos'],
            $this->estados['nombres']
        );
        $this->saveTransiciones(
            $this->transiciones['desdes'],
            $this->transiciones['valores'],
            $this->transiciones['hastas']
        );
        $this->db->commit();

    }

    /**
     * Método que guarda los estados del AFD
     * @param codigo Arreglo con los códigos de los estados
     * @param nombres Arreglo con los nombres/glosas de los estados
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-05-13
     */
    private function saveEstados($codigos, $nombres)
    {
        $this->db->beginTransaction();
        $this->db->query('
            DELETE FROM afd_estado
            WHERE afd = :afd
        ', [':afd'=>$this->codigo]);
        $n = count($codigos);
        for ($i=0; $i<$n; $i++) {
            $codigos[$i] = trim($codigos[$i]);
            $nombres[$i] = trim($nombres[$i]);
            if (!isset($codigos[$i][0]) or !isset($nombres[$i][0]))
                continue;
            $this->db->query(
                'INSERT INTO afd_estado VALUES (:afd, :codigo, :nombre)',
                [':afd'=>$this->codigo, ':codigo'=>$codigos[$i], ':nombre'=>$nombres[$i]]
            );
        }
        $this->db->commit();
    }

    /**
     * Método que guarda los transiciones del AFD
     * @param desdes Arreglo con los estados desde
     * @param valores Arreglo con los valores que hacen pasar desde "desde" a "hasta"
     * @param hastas Arreglo con los estados hasta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-05-13
     */
    private function saveTransiciones($desdes, $valores, $hastas)
    {
        $this->db->beginTransaction();
        $this->db->query('
            DELETE FROM afd_transicion
            WHERE afd = :afd
        ', [':afd'=>$this->codigo]);
        $n = count($desdes);
        for ($i=0; $i<$n; $i++) {
            $desdes[$i] = trim($desdes[$i]);
            $valores[$i] = trim($valores[$i]);
            $hastas[$i] = trim($hastas[$i]);
            if (!isset($desdes[$i][0]) or !isset($valores[$i][0]) or !isset($hastas[$i][0]))
                continue;
            $AfdTransicion = new Model_AfdTransicion();
            $AfdTransicion->afd = $this->codigo;
            $AfdTransicion->desde = $desdes[$i];
            $AfdTransicion->valor = $valores[$i];
            $AfdTransicion->hasta = $hastas[$i];
            $AfdTransicion->save();
        }
        $this->db->commit();
    }

    /**
     * Método que entrega el listado de estados del AFD
     * @param prefix Prefijo que se debe usar en el nombre de la columna (clave en el arreglo)
     * @return Tabla con los estados: código y nombre
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-12-19
     */
    public function getEstados($prefix = '')
    {
        return $this->db->getTable('
            SELECT codigo AS '.$prefix.'codigo, nombre AS '.$prefix.'nombre
            FROM afd_estado
            WHERE afd = :afd
            ORDER BY codigo
        ', [':afd'=>$this->codigo]);
    }

    /**
     * Método que entrega el listado de transiciones del AFD
     * @return Tabla con las transiciones: hasta, valor y desde
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-12-19
     */
    public function getTransicionesTabla()
    {
        return $this->db->getTable('
            SELECT desde, valor, hasta
            FROM afd_transicion
            WHERE afd = :afd
            ORDER BY desde, hasta, valor
        ', [':afd'=>$this->codigo]);
    }

    /**
     * Método que entrega el listado de transiciones del AFD normalizadas para
     * ser utilizadas con la utilidad \sowerphp\general\Utility_Automada_AFD
     * @return Arreglo en formato requerido por Utility_Automada_AFD
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-12-19
     */
    public function getTransiciones()
    {
        $aux = $this->getTransicionesTabla();
        $transiciones = [];
        foreach ($aux as &$t) {
            if (!isset($transiciones[$t['desde']]))
                $transiciones[$t['desde']] = [];
            $transiciones[$t['desde']][$t['valor']] = $t['hasta'];
        }
        return $transiciones;
    }

}
