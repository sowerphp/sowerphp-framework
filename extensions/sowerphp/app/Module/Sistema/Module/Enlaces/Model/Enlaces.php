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
namespace sowerphp\app\Sistema\Enlaces;

/**
 * Clase para mapear la tabla enlace de la base de datos
 * Comentario de la tabla: Enlaces de la aplicación
 * Esta clase permite trabajar sobre un conjunto de registros de la tabla enlace
 * @author SowerPHP Code Generator
 * @version 2014-05-04 11:25:25
 */
class Model_Enlaces extends \Model_Plural_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'enlace'; ///< Tabla del modelo

    /**
     * Método para obtener de forma recursiva los enlaces en sus
     * correspondientes categorías.
     * @param madre Categoría madre de los enlaces que se desea recuperar o =null para todos
     * @return Arreglo asociativo con los datos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2014-05-05
     */
    public function getAll ($madre = null)
    {
        $enlaces = $this->db->getTable(
            'SELECT enlace, url FROM enlace WHERE categoria = :madre ORDER BY enlace',
            [':madre' => $madre]
        );
        if ($madre == null) {
            $categorias = $this->db->getTable(
                'SELECT id, categoria FROM enlace_categoria WHERE madre IS NULL ORDER BY categoria'
            );
        } else {
            $categorias = $this->db->getTable(
                'SELECT id, categoria FROM enlace_categoria WHERE madre = :madre ORDER BY categoria',
                [':madre' => $madre]
            );
        }
        foreach ($categorias as &$categoria) {
            $enlaces[$categoria['categoria']] = $this->getAll($categoria['id']);
        }
        return $enlaces;
    }

}
