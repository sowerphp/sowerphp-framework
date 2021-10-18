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
namespace sowerphp\app\Sistema\General\DivisionGeopolitica;

/**
 * Clase para mapear la tabla comuna de la base de datos
 * Comentario de la tabla: Comunas de cada provincia del país
 * Esta clase permite trabajar sobre un conjunto de registros de la tabla comuna
 * @author SowerPHP Code Generator
 * @version 2014-04-26 01:36:28
 */
class Model_Comunas extends \Model_Plural_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'comuna'; ///< Tabla del modelo

    /**
     * Método que entrega la lista de comunas agrupadas por regiones
     * @return Arreglo con índice el código de región y una tabla con los códigos y glosas de comunas
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-01-08
     */
    public function getListByRegion()
    {
        return $this->db->getAssociativeArray('
            SELECT r.codigo AS region, c.codigo AS id, c.comuna AS glosa
            FROM region AS r, provincia AS p, comuna AS c
            WHERE
                c.provincia = p.codigo
                AND p.region = r.codigo
            ORDER BY r.codigo, c.comuna
        ');
    }

    /**
     * Método que recupera el código de una comuna a partir de su nombre
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-04-15
     */
    public function getComunaByName($nombre)
    {
        $this->setWhereStatement(['UPPER(comuna) = :comuna'], [':comuna'=>mb_strtoupper($nombre)]);
        $comunas = $this->getObjects();
        if ($comunas and isset($comunas[0]))
            return $comunas[0]->codigo;
        return false;
    }

}
