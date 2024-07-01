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

namespace sowerphp\app\Sistema\Notificaciones;

/**
 * Clase para mapear la tabla notificacion de la base de datos
 * Comentario de la tabla:
 * Esta clase permite trabajar sobre un conjunto de registros de la tabla notificacion
 */
class Model_Notificaciones extends \Model_Plural_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'notificacion'; ///< Tabla del modelo

    /**
     * Método que entrega todas las notificaciones del usuario, leínas y no
     * leídas
     * @param usuario ID del usuario que se quiere obtener sus notificaciones
     * @return array Tabla con las notificaciones
     */
    public function getByUser($usuario)
    {
        $notificaciones = $this->db->getTable('
            SELECT
                n.id,
                n.icono,
                n.fechahora,
                gravedad AS tipo,
                u.usuario,
                n.descripcion,
                n.enlace,
                n.leida
            FROM notificacion AS n LEFT JOIN usuario AS u ON n.de = u.id
            WHERE para = :usuario
            ORDER BY id DESC
        ', [':usuario' => $usuario]);
        $Notificacion = new Model_Notificacion();
        foreach ($notificaciones as &$n) {
            $n['tipo'] = $Notificacion->getSeverity($n['tipo'])->style;
        }
        return $notificaciones;
    }

    /**
     * Método que entrega las últimas X notificaciones no leídas del usuario
     * @param usuario ID del usuario que se quiere obtener sus notificaciones
     * @param limit Cantidad de notificaciones que se desea obtener
     * @return array Tabla con las notificaciones no leídas
     */
    public function getUnreadByUser($usuario, $limit = 3)
    {
        $notificaciones = $this->db->getTable('
            SELECT
                n.id,
                n.fechahora,
                gravedad AS tipo,
                u.usuario,
                n.descripcion,
                n.icono,
                n.enlace
            FROM notificacion AS n LEFT JOIN usuario AS u ON n.de = u.id
            WHERE para = :usuario AND leida = false
            ORDER BY id DESC
            LIMIT '.(int)$limit.'
        ', [':usuario' => $usuario]);
        $Notificacion = new Model_Notificacion();
        foreach ($notificaciones as &$n) {
            $n['tipo'] = $Notificacion->getSeverity($n['tipo'])->style;
        }
        return $notificaciones;
    }

    /**
     * Método que entrega la cantidad total de notificaciones sin leer
     * @param usuario ID del usuario que se quiere obtener sus notificaciones
     * @return int Cantidad de notificaciones sin leer
     */
    public function getCountUnreadByUser($usuario)
    {
        return $this->db->getValue('
            SELECT COUNT(*)
            FROM notificacion
            WHERE para = :usuario AND leida = false
        ', [':usuario' => $usuario]);
    }

}
