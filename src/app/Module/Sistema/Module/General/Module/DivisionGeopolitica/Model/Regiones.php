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

namespace sowerphp\app\Sistema\General\DivisionGeopolitica;

/**
 * Clase para mapear la tabla region de la base de datos
 * Comentario de la tabla: Regiones del país
 * Esta clase permite trabajar sobre un conjunto de registros de la tabla region
 */
class Model_Regiones extends \sowerphp\autoload\Model_Plural_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'region'; ///< Tabla del modelo

    /**
     * Método que entrega la lista de regiones ordenadas por código
     * @return array Tabla con los códigos y regiones
     */
    public function getList()
    {
        return $this->db->getTable('
            SELECT codigo AS id, codigo || \' - \' || region AS glosa
            FROM region
            ORDER BY codigo
        ');
    }

}
