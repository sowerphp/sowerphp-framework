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

namespace sowerphp\app\Dev;

use \sowerphp\core\Facade_Session_Message as SessionMessage;

/**
 * Controlador para las acciones relacionadas con la base de datos
 */
class Controller_Bd extends \sowerphp\autoload\Controller
{

    /**
     * Acción que permite listar las tablas de una de las base de datos
     * configuradas y mostrar sus tablas y la información de las mismas
     * (comentarios, columnas y pks).
     */
    public function tablas()
    {
        if (isset($_POST['submit'])) {
            $db = &database($_POST['database']);
            $tables = $db->getTablesFromDatabase();
            $data = [];
            // procesar cada una de las tablas
            foreach ($tables as &$table) {
                $info = $db->getInfoFromTable($table['name']);
                $row = array(
                    'name' => $info['name'],
                    'comment' => $info['comment'],
                    'columns' => [],
                    'pk' => implode('<br />', $info['pk']),
                );
                // procesar cada columna para armar su
                // información
                foreach ($info['columns'] as &$column) {
                    $row['columns'][] = '<strong>[' . $column['name'] . ']</strong> '.
                        ($column['comment'] != '' ? ($column['comment'] .': ') : '').
                        $column['type'].
                        '(' . $column['length'] . ')'.
                        (($column['null'] === 'YES' || $column['null'] == 1) ? ' NULL' : ' NOT NULL').
                        (" DEFAULT '" . $column['default'] . "' ").
                        (($column['auto']==='YES'||$column['auto']==1)?'AUTO ':'').
                        (in_array($column['name'], $info['pk']) ? 'PK ' : '').
                        (is_array($column['fk']) ? ('FK:' . $column['fk']['table'] . '.' . $column['fk']['column']) : '')
                    ;
                }
                $row['columns'] = implode ('<br />', $row['columns']);
                $data[] = $row;
            }
            // setear las variables para mostrar los datos de la bd
            $this->set(array(
                'database' => $_POST['database'],
                'data' => $data
            ));
        }
        // setear listado de bases de datos
        $this->_setDatabases();
    }

    /**
     * Acción que permite poblar datos en tablas de una BD
     * @todo Poblar tablas con PK autoincrementales. IDEA: actualizar serie.
     */
    public function poblar()
    {
        // setear listado de bases de datos
        $this->_setDatabases();
        // procesar formulario si fue enviado
        if (isset($_POST['submit'])) {
            if (!isset($_FILES['file']) || $_FILES['file']['error']) {
                SessionMessage::error('No fue posible leer el archivo de carga de datos.');
                return;
            }
            // cargar hojas del archivo
            try {
                $sheets = \sowerphp\general\Utility_Spreadsheet::sheets($_FILES['file']);
                if (!$sheets) {
                    throw new \Exception('No se encontraron hojas para procesar en el archivo de carga de datos (o bien no se logró leer el listado de hojas del archivo).');
                }
            } catch (\Exception $e) {
                SessionMessage::error($e->getMessage());
                return;
            }
            // hacer todo en una transacción
            $db = &database($_POST['database']);
            $db->beginTransaction();
            // cada hoja del archivo son los datos de una tabla
            $message = [];
            foreach ($sheets as $id => &$name) {
                $data = \sowerphp\general\Utility_Spreadsheet::read($_FILES['file'], $id);
                $table = $name;
                $info = $db->getInfoFromTable($table);
                $cols = array_flip(array_shift($data));
                $existsQuery = 'SELECT COUNT(*) FROM '.$table.' ';
                $whereQuery = 'WHERE '.implode(' = \'?\' AND ', $info['pk']).' = \'?\'';
                $updateQuery = 'UPDATE '.$table.' SET';
                $insertQuery = 'INSERT INTO '.$table.' ('.implode(', ', array_keys($cols)).') VALUES ';
                // contar registros totales (en el archivo) y existentes antes de hacer algo en la tabla
                $registros = array(
                    'total' => count($data),
                    'existentes' => $db->getValue('SELECT COUNT(*) FROM '.$table),
                    'actualizados' => 0,
                    'insertados' => 0,
                );
                // eliminar datos de la tabla en caso que se haya solicitado
                if (isset($_POST['delete'])) {
                    $db->executeRawQuery('DELETE FROM '.$table);
                }
                // variable para almacenar el ID más alto (en caso que exista para luego alterar la secuencia/autoincremental)
                $id = 0;
                // agregar (o actualizar) cada registro con cada
                // una de las filas de la tabla
                foreach ($data as &$row) {
                    $where = $whereQuery;
                    $pkCompleta = true;
                    foreach ($info['pk'] as $pk) {
                        if (empty($row[$cols[$pk]])) {
                            if (isset($row[$cols[$pk]])) {
                                $col_as_string = (string)$row[$cols[$pk]];
                                if (!isset($col_as_string[0])) {
                                    $pkCompleta = false;
                                }
                            } else {
                                $pkCompleta = false;
                            }
                        }
                        $where = preg_replace ('/\?/', $row[$cols[$pk]], $where, 1);
                    }
                    // si el registro existe se actualiza
                    if ($pkCompleta && $db->getValue($existsQuery.$where)) {
                        $values = [];
                        $auxCols = array_keys($cols);
                        foreach ($row as &$col) {
                            if ((string)$col !== '0' && empty($col)) {
                                $values[] = array_shift($auxCols).' = NULL';
                            } else {
                                $values[] = array_shift($auxCols).' = \''.$col.'\'';
                            }
                        }
                        $db->query ($query = $updateQuery.' '.implode(', ', $values).' '.$where);
                        $registros['actualizados']++;
                    }
                    // si el registro no existe se inserta
                    else {
                        $values = [];
                        foreach ($row as &$col) {
                            if ((string)$col !== '0' && empty($col)) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = '\''.$col.'\'';
                            }
                        }
                        $db->executeRawQuery($insertQuery.' ('.implode(', ', $values).')');
                        $registros['insertados']++;
                    }
                    // ir guarando el ID más grande
                    if (in_array('id', $info['pk'])) {
                        $id = max($id, $row[$cols['id']]);
                    }
                }
                // alterar secuencia
                if (in_array('id', $info['pk']) && $id > 0) {
                    $es_serial = false;
                    foreach ($info['columns'] as $columna) {
                        if ($columna['name'] == 'id') {
                            if ($columna['auto']) {
                                $es_serial = true;
                            }
                            break;
                        }
                    }
                    if ($es_serial) {
                        if ($db->getDriverName() == 'pgsql') {
                            $db->executeRawQuery('SELECT SETVAL (\''.$table.'_id_seq\', '.$id.');')->errorCode();
                        }
                    }
                }
                // crear mensaje para esta tabla
                $message[] = $_POST['database'].'.'.$table.
                    ': existentes='.$registros['existentes'].
                    ', actualizados='.$registros['actualizados'].
                    ', insertados='.$registros['insertados'].
                    ', total='.$registros['total']
                ;
            }
            // terminar transacción
            $db->commit();
            SessionMessage::info(implode('<br />', $message));
        }
    }

    /**
     * Acción para descargar los datos de tablas de una base de datos, al
     * elegir la base de datos, se muestra una nueva pantalla donde se
     * eligen las tablas para las cuales se desea descargar sus datos. Una
     * vez se eligen las tablas se descargan los datos en el archivo de
     * formato seleccionado (ej: ODS o XLS).
     */
    public function descargar()
    {
        // en caos que se haya seleccionado una base de datos
        if (isset($_POST['step1'])) {
            $db = &database($_POST['database']);
            $this->set([
                'database' => $_POST['database'],
                'type' => $_POST['type'],
                'tables' => $db->getTablesFromDatabase(),
            ]);
            return $this->render('Bd/descargar_step2');
        }
        // en caso que se hayan seleccionado las tablas descargar datos
        else if (isset($_POST['step2'])) {
            $db = &database($_POST['database']);
            $data = [];
            foreach ($_POST['tables'] as &$table) {
                $data[$table] = $db->getTableWithColsNames ('
                    SELECT *
                    FROM '.$table.'
                ');
            }
            $this->set([
                'id' => 'database_' . $_POST['database'],
                'type' => $_POST['type'],
                'data' => $data,
            ]);
            return $this->render('Bd/descargar_step3');
        }
        // en caso que no se haya seleccionado aun la bd
        else {
            // setear listado de bases de datos
            $this->_setDatabases();
            return $this->render('Bd/descargar_step1');
        }
    }

    /**
     * Acción para ejecutar una consulta SQL en una de las bases de datos
     */
    public function consulta()
    {
        if (isset($_POST['submit'])) {
            ini_set('memory_limit', '1024M');
            $db = &database($_POST['database']);
            try {
                $data = $db->getTableWithColsNames($_POST['query']);
            } catch (\Exception $e) {
                SessionMessage::error($e->getMessage());
            }
            if (isset($data)) {
                if ($_POST['resultados'] == 'web') {
                    $this->set([
                        'data' => $data,
                        'database' => $_POST['database']
                    ]);
                } else {
                    \sowerphp\general\Utility_Spreadsheet_CSV::generate($data, 'query_'.$_POST['database'].'_'.date('U'));
                }
            }
        }
        // setear listado de bases de datos
        $this->_setDatabases();
    }

    /**
     * Método que busca las bases de datos configuradas en la aplicación y las
     * asigna para la vista como un arreglo asociativo.
     */
    private function _setDatabases()
    {
        $databases = [];
        $connections = config('database.connections');
        foreach ($connections as $database => $config) {
            $databases[$database] = $config['username'] . '@' . $config['host']
                . ':' . $config['database']
                . ' (' . $database . ':' . $config['driver'] . ')'
            ;
        }
        $this->set('databases', $databases);
    }

}
