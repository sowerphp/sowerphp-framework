<?php

/**
 * SowerPHP: Minimalist Framework for PHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
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

/**
 * Interfaz para objetos de tipo Model
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2012-10-07
 */
interface ModelInterface {
	public function set($array);
	public function exists();
	public function save();
	public function delete();
}

/**
 * Interfaz para objetos de tipo Models
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
 * @version 2012-10-07
 */
interface ModelsInterface {
	public function clear($statement);
	public function setSelectStatement($selectStatement);
	public function setWhereStatement($whereStatement);
	public function setGroupByStatement($groupByStatement);
	public function setHavingStatement($havingStatement);
	public function setOrderByStatement($orderByStatement);
	public function setLimitStatement($records, $offset);
	public function sanitize($string);
	public function count();
	public function getMax($column);
	public function getMin($column);
	public function getSum($column);
	public function getAvg($column);
	public function getObjects();
	public function getTable();
	public function getRow();
	public function getCol();
	public function getValue();
	public function getList();
}
