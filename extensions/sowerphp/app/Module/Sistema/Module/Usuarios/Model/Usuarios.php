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
namespace sowerphp\app\Sistema\Usuarios;

/**
 * Clase para mapear la tabla usuario de la base de datos
 * Comentario de la tabla: Usuarios de la aplicación
 * Esta clase permite trabajar sobre un conjunto de registros de la tabla usuario
 * @author SowerPHP Code Generator
 * @version 2014-04-05 17:32:18
 */
class Model_Usuarios extends \Model_Plural_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'usuario'; ///< Tabla del modelo

    /**
     * Método que entrega el listado de usuarios
     * @return Tabla con el listado de usuarios activos ordenados por nombre
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-01-07
     */
    public function getList()
    {
        return $this->db->getTable('
            SELECT id, '.$this->db->concat('usuario', ' - ', 'nombre').' AS glosa
            FROM usuario
            WHERE activo = true
            ORDER BY nombre
        ');
    }

    /**
     * Método que entrega el listado de usuarios pertenecientes a cierto grupo
     * @return Tabla con el listado de usuarios activos ordenados por nombre que pertenecen al grupo indicado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-01-07
     */
    public function getListInGroup($grupo)
    {
        return $this->db->getTable('
            SELECT u.id, '.$this->db->concat('u.usuario', ' - ', 'u.nombre').' AS glosa
            FROM usuario AS u, usuario_grupo AS ug, grupo AS g
            WHERE u.activo = true AND g.grupo = :grupo AND ug.grupo = g.id AND ug.usuario = u.id
            ORDER BY nombre
        ', [':grupo'=>$grupo]);
    }

    /**
     * Método que entrega los correos electrónicos de usuarios pertenecientes a cierto grupo
     * @return Arreglo con los correos electrónicos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-01-08
     */
    public function getEmailsInGroup($grupo)
    {
        return $this->db->getCol('
            SELECT u.email
            FROM usuario AS u, usuario_grupo AS ug, grupo AS g
            WHERE u.activo = true AND g.grupo = :grupo AND ug.grupo = g.id AND ug.usuario = u.id
            ORDER BY nombre
        ', [':grupo'=>$grupo]);
    }

    /**
     * Método que entrega una estadística mensual con los usuarios que iniciaron
     * sesión por última vez
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2016-09-21
     */
    public function getStatsLogin($limit = 12)
    {
        $mes = $this->db->config['type']=='PostgreSQL' ? 'TO_CHAR(ultimo_ingreso_fecha_hora, \'YYYY-MM\')' : 'DATE_FORMAT(ultimo_ingreso_fecha_hora, "%Y-%m")';
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
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2017-10-16
     */
    public function getUserByTelegramID($telegram_id, $model)
    {
        $id = $this->db->getValue('
            SELECT usuario
            FROM usuario_config
            WHERE configuracion = \'telegram\' AND variable = \'id\' AND valor = :telegram_id
        ', ['telegram_id'=>$telegram_id]);
        return $id ? new $model($id) : false;
    }

}
