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

use sowerphp\core\Network_Request as Request;

/**
 * Clase para el controlador asociado a la tabla usuario_grupo de la base de
 * datos
 * Comentario de la tabla: Relación entre usuarios y los grupos a los que pertenecen
 * Esta clase permite controlar las acciones entre el modelo y vista para la
 * tabla usuario_grupo
 */
class Controller_UsuarioGrupos extends \sowerphp\autoload\Controller_Model
{

    protected $deleteRecord = false; ///< Indica si se permite o no borrar registros

    public function editar(Request $request, ...$pk)
    {
        list($usuario) = $pk;
        $listar = base64_encode(url($request->getBaseUrlWithoutSlash().'/sistema/usuarios/usuario_grupos/listar'.base64_decode($_GET['listar'])));
        return redirect('/sistema/usuarios/usuarios/editar/'.$usuario.'?listar='.$listar);
    }

}
