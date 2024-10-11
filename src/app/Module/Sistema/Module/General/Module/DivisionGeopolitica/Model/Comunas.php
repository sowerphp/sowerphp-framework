<?php

/**
 * SowerPHP: Simple and Open Web Ecosystem Reimagined for PHP.
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

namespace sowerphp\app\Sistema\General\DivisionGeopolitica;

use sowerphp\autoload\Model_Plural;

/**
 * Modelo plural de la tabla "comuna" de la base de datos.
 *
 * Permite interactuar con varios registros de la tabla.
 */
class Model_Comunas extends Model_Plural
{

    /**
     * Entrega la lista de comunas agrupadas por regiones.
     *
     * @return array Arreglo con índice el código de región y una tabla con los
     * códigos y glosas de comunas.
     */
    public function getListByRegion(): array
    {
        return $this->getDatabaseConnection()->getTableWithAssociativeIndex('
            SELECT r.codigo AS region, c.codigo AS id, c.comuna AS glosa
            FROM region AS r, provincia AS p, comuna AS c
            WHERE
                c.provincia = p.codigo
                AND p.region = r.codigo
            ORDER BY r.codigo, c.comuna
        ');
    }

    /**
     * Buscar el código de una comuna a partir de su nombre
     */
    public function getComunaByName($nombre)
    {
        $this->setWhereStatement(['UPPER(comuna) = :comuna'], [':comuna' => mb_strtoupper($nombre)]);
        $comunas = $this->getObjects();
        if ($comunas && isset($comunas[0])) {
            return $comunas[0]->codigo;
        }
        return false;
    }

}
