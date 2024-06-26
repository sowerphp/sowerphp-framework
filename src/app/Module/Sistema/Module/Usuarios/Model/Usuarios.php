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

// namespace del modelo
namespace sowerphp\app\Sistema\Usuarios;

/**
 * Clase para mapear la tabla usuario de la base de datos
 * Comentario de la tabla: Usuarios de la aplicación
 * Esta clase permite trabajar sobre un conjunto de registros de la tabla usuario
 */
class Model_Usuarios extends \Model_Plural_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'usuario'; ///< Tabla del modelo

    /**
     * Método que entrega el listado de usuarios
     * @return array Tabla con el listado de usuarios activos ordenados por nombre
     */
    public function getList()
    {
        return $this->db->getTable('
            SELECT id, usuario || \' - \' || nombre AS glosa
            FROM usuario
            WHERE activo = true
            ORDER BY nombre
        ');
    }

    /**
     * Método que entrega el listado de usuarios pertenecientes a cierto grupo
     * @return array Tabla con el listado de usuarios activos ordenados por nombre que pertenecen al grupo indicado
     */
    public function getListInGroup($grupo)
    {
        return $this->db->getTable('
            SELECT u.id, u.usuario || \' - \' || u.nombre AS glosa
            FROM usuario AS u, usuario_grupo AS ug, grupo AS g
            WHERE u.activo = true AND g.grupo = :grupo AND ug.grupo = g.id AND ug.usuario = u.id
            ORDER BY nombre
        ', [':grupo' => $grupo]);
    }

    /**
     * Método que entrega los correos electrónicos de usuarios pertenecientes a cierto grupo
     * @return array Arreglo con los correos electrónicos
     */
    public function getEmailsInGroup($grupo)
    {
        return $this->db->getCol('
            SELECT u.email
            FROM usuario AS u, usuario_grupo AS ug, grupo AS g
            WHERE u.activo = true AND g.grupo = :grupo AND ug.grupo = g.id AND ug.usuario = u.id
            ORDER BY nombre
        ', [':grupo' => $grupo]);
    }

    /**
     * Método que entrega una estadística mensual con los usuarios que
     * iniciaron sesión por última vez.
     */
    public function getStatsLogin($limit = 12)
    {
        $mes = $this->db->getDriverName() == 'pgsql'
            ? 'TO_CHAR(ultimo_ingreso_fecha_hora, \'YYYY-MM\')'
            : 'DATE_FORMAT(ultimo_ingreso_fecha_hora, "%Y-%m")'
        ;
        return $this->db->getTable('
            SELECT mes, usuarios
            FROM (
                SELECT '.$mes.' AS mes, COUNT(*) AS usuarios
                FROM usuario
                WHERE ultimo_ingreso_fecha_hora IS NOT NULL
                GROUP BY '.$mes.'
                ORDER BY '.$mes.' DESC
                LIMIT '.$limit.'
            ) AS e
            ORDER BY mes
        ');
    }

    /**
     * Método que entrega el objeto del Usuario a partir del ID de telegram
     */
    public function getUserByTelegramID($telegram_id, $model)
    {
        $id = $this->db->getValue('
            SELECT usuario
            FROM usuario_config
            WHERE configuracion = \'telegram\' AND variable = \'id\' AND valor = :telegram_id
        ', ['telegram_id' => $telegram_id]);
        return $id ? new $model($id) : false;
    }

}
