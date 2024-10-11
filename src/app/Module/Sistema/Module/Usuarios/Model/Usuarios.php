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

namespace sowerphp\app\Sistema\Usuarios;

use sowerphp\autoload\Model_Plural;

/**
 * Modelo plural de la tabla "usuario" de la base de datos.
 *
 * Permite interactuar con varios registros de la tabla.
 */
class Model_Usuarios extends Model_Plural
{

    /**
     * Determina el ID (campo `id` de la tabla `usuario`) a partir de las
     * credenciales pasadas.
     *
     * Las credenciales podrán ser un arreglo asociativo con uno de estos
     * índices y campos:
     *
     *   - El campo `id` del usuario en el índice `id`.
     *   - El campo `usuario` del usuario en el índice `username`.
     *   - El campo `email` del usuario en el índice `email`.
     *   - El campo `hash` del usuario en el índice `hash`.
     *
     * Si el ID determinado es una 'X' y además se pasó el campo `password`,
     * entonces lo que se pasó fue el campo `hash` en la contraseña.
     *
     * Adicionalmente, se permite recibir cualquiera de los valores previos en
     * un arreglo no asociativo en el índice `0` del arreglo de credenciales.
     *
     * @param array $credentials
     * @return integer|null
     */
    public function getIdFromCredentials(array $credentials): ?int
    {
        // Determinar un campo ID a partir de diferentes datos que podrían
        // venir en las credenciales.
        $id = $credentials[0]
            ?? $credentials['id']
            ?? $credentials['email']
            ?? $credentials['username']
            ?? $credentials['hash']
            ?? null
        ;
        // Si no viene ID se entrega NULL (no se pudo determinar el ID del
        // usuario).
        if ($id === null) {
            return null;
        }
        // Si el $id es una 'X' y viene $credentials['password'] entonces se
        // está pasando el hash de un usuario.
        if ($id == 'X' && !empty($credentials['password'])) {
            $id = $credentials['password'];
        }
        // Si viene ID y no es numérico, se debe determinar el ID del usuario.
        if (!is_numeric($id)) {
            $query = $this->query();
            // Se busca el ID a partir del correo electrónico.
            if (strpos($id, '@')) {
                $user = $query->where('email', mb_strtolower($id))->first();
            }
            // Se busca el ID a partir del nombre de usuario.
            else if (!isset($id[31])) {
                $user = $query->where('usuario', $id)->first();
            }
            // Se busca el ID a partir del hash del usuario.
            else {
                $user = $query->where('hash', $id)->first();
            }
            // Si no se encontró usuario se retorna NULL.
            if ($user === null) {
                return null;
            }
            // El usuario existe y se recuperó su ID numérico (ID real).
            $id = $user->id;
        }
        // Se entrega el ID encontrado.
        return $id;
    }

    /**
     * Entrega el listado de usuarios.
     *
     * @return array Tabla con el listado de usuarios activos ordenados por
     * nombre.
     */
    public function getList(): array
    {
        return $this->getDatabaseConnection()->getTable('
            SELECT id, usuario || \' - \' || nombre AS glosa
            FROM usuario
            WHERE activo = true
            ORDER BY nombre
        ');
    }

    /**
     * Entrega el listado de usuarios pertenecientes a cierto grupo.
     *
     * @return array Tabla con el listado de usuarios activos ordenados por
     * nombre que pertenecen al grupo indicado.
     */
    public function getListInGroup(int $grupo): array
    {
        return $this->getDatabaseConnection()->getTable('
            SELECT u.id, u.usuario || \' - \' || u.nombre AS glosa
            FROM usuario AS u, usuario_grupo AS ug, grupo AS g
            WHERE u.activo = true AND g.grupo = :grupo AND ug.grupo = g.id AND ug.usuario = u.id
            ORDER BY nombre
        ', [':grupo' => $grupo]);
    }

    /**
     * Entrega los correos electrónicos de usuarios pertenecientes a cierto
     * grupo.
     *
     * @return array Arreglo con los correos electrónicos.
     */
    public function getEmailsInGroup(int $grupo): array
    {
        return $this->getDatabaseConnection()->getCol('
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
    public function getStatsLogin(int $limit = 12): array
    {
        $mes = $this->getDatabaseConnection()->getDriverName() == 'pgsql'
            ? 'TO_CHAR(ultimo_ingreso_fecha_hora, \'YYYY-MM\')'
            : 'DATE_FORMAT(ultimo_ingreso_fecha_hora, "%Y-%m")'
        ;
        return $this->getDatabaseConnection()->getTable('
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
     * Método que entrega el objeto del Usuario a partir del ID de Telegram.
     */
    public function getUserByTelegramID(int $telegram_id)
    {
        $id = $this->getDatabaseConnection()->getValue('
            SELECT usuario
            FROM usuario_config
            WHERE
                configuracion = \'telegram\'
                AND variable = \'id\'
                AND valor = :telegram_id
        ', [
            'telegram_id' => $telegram_id
        ]);
        $class = config('auth.providers.users.model');
        return $id ? new $class($id) : null;
    }

}
