<?php

/**
 * MiPaGiNa (MP)
 * Copyright (C) 2014 Esteban De La Fuente Rubio (esteban[at]delaf.cl)
 * 
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 * 
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 * 
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

// Clase abstracta para el controlador (padre de esta)
App::uses('{classs}BaseController', '{module}Controller');

// Clase para el modelo de este controlador
App::uses('{class}', '{module}Model');

/**
 * Clase final para el controlador asociado a la tabla {table} de la base de datos
 * Comentario de la tabla: {comment}
 * Esta clase permite controlar las acciones entre el modelo y vista para la tabla {table}
 * @author {author}
 * @version {version}
 */
final class {classs}Controller extends {classs}BaseController {

	protected $module_url = '/{module_url}';

}
