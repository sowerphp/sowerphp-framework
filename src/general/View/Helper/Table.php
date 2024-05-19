<?php

/**
 * SowerPHP: Framework PHP hecho en Chile.
 * Copyright (C) SowerPHP <https://www.sowerphp.org>
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

namespace sowerphp\general;

/**
 * Helper para la creación de tablas en HTML
 */
class View_Helper_Table
{

    private $_id = null; ///< Identificador de la tabla
    private $_class = 'table table-striped'; ///< Atributo class para la tabla
    private $_export = false; ///< Crear o no datos para exportar
    private $_exportRemove = []; ///< Datos que se removeran al exportar
    private $_display = null; ///< Indica si se debe o no mostrar la tabla
    private $_height = null; ///< Altura de la tabla en pixeles
    private $_colsWidth = []; ///< Ancho de las columnas en pixeles
    private $_showEmptyCols = true; ///< Indica si se deben mostrar las columnas vacías de la tabla
    private $extensions = ['ods'=>'OpenDocument', 'csv'=>'Planilla CSV', 'xls'=>'Planilla Excel', 'pdf'=>'Documento PDF', 'xml'=>'Archivo XML', 'json'=>'Archivo JSON']; ///< Formatos por defecto para exportar datos

    /**
     * Constructor de la clase para crear una tabla
     * @param table Datos con la tabla que se desea generar
     * @param id Identificador de la tabla
     * @param export Si se desea poder exportar los datos de la tabla
     */
    public function __construct($table = null, $id = null, $export = false, $display = null)
    {
        // si se paso una tabla se genera directamente y se imprime
        // esto evita una línea de programación em muchos casos
        if (is_array($table)) {
            $this->_id = $id;
            $this->_export = $export;
            $this->_display = $display;
            echo $this->generate($table);
        }
    }

    /**
     * Asigna un identificador a la tabla
     * @param id Identificador para asignar a la tabla
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * Asignar el atributo class para la tabla
     * @param class Atributo class (o varios) que se asignarán
     */
    public function setClass($class)
    {
        $this->_class = $class;
    }

    /**
     * Asignar si se deberán generar o no iconos para exportar la tabla
     * @param export Flag para indicar si se debe o no exportar
     */
    public function setExport($export = true)
    {
        $this->_export = $export;
    }

    /**
     * Definir que se deberá remover de la tabla antes de poder exportarla
     * @param remove Atributo con lo que se desea extraer antes de exportar
     */
    public function setExportRemove($remove)
    {
        $this->_exportRemove = $remove;
    }

    /**
     * Asignar si se debe o no mostrar la tabla (o se usa más para mostrar)
     * @param display Flag para indicar si se debe o no mostrar la tabla
     */
    public function setDisplay($display = true)
    {
        $this->_display = $display;
    }

    /**
     * Asignar la altura que podrá ocupar todo el contenedor (div) de la tabla
     * @param height Altura en pixeles del div
     */
    public function setHeight($height = null)
    {
        $this->_height = $height;
    }

    /**
     * Asignar ancho de las columnas
     * @param width Arreglo con los anchos de las columnas (null si una columna debe ser automática)
     */
    public function setColsWidth($width = [])
    {
        $this->_colsWidth = $width;
    }

    /**
     * Asignar si se deben o no mostrar las columnas vacías de la tabla
     * @param show =true se mostrarán todas las columnas =false sólo aquellas donde exista al menos una fila con un dato
     */
    public function setShowEmptyCols($show = true)
    {
        $this->_showEmptyCols = $show;
    }

    /**
     * Método que genera la tabla en HTML a partir de un arreglo
     * @param table Tabla que se generará
     * @todo Programar opción para no mostrar todas las columnas
     */
    public function generate($table, $thead = 1)
    {
        // si el arreglo esta vacio o no es arreglo retornar nada
        if (!is_array($table) || !count($table)) {
            return null;
        }
        // si no se debe mostrar las columnas vacías se quitan del arreglo de datos
        if (!$this->_showEmptyCols) {
            $n_rows = count($table) - 1;
            // contar las filas vacías en cada columna
            $empty_cols = [];
            for ($i=1; $i<=$n_rows; $i++) {
                $col_count = 0;
                foreach ($table[$i] as $col) {
                    if (empty($col)) {
                        if (!isset($empty_cols[$col_count])) {
                            $empty_cols[$col_count] = 0;
                        }
                        $empty_cols[$col_count]++;
                    }
                    $col_count++;
                }
            }
            // quitar columnas vacías
            foreach ($table as $k_row => $row) {
                $col_count = 0;
                foreach ($row as $k_col => $col) {
                    if (isset($empty_cols[$col_count]) && $empty_cols[$col_count] == $n_rows) {
                        unset($table[$k_row][$k_col]);
                    }
                    $col_count++;
                }
            }
            // quitar anchos de columnas vacías
            $colsWidth = [];
            $col = 0;
            foreach ($this->_colsWidth as $width) {
                if (!isset($empty_cols[$col])) {
                    $colsWidth[] = $width;
                }
                $col++;
            }
        }
        // si se muestran todas las columnas se copia el ancho de ellas
        else {
            $colsWidth = $this->_colsWidth;
        }
        // Utilizar buffer para el dibujado, así lo retornaremos en vez
        // de imprimir directamente
        $buffer = ($this->_height ? '<div style="max-height:'.$this->_height.'px;overflow:auto">' : '<div>')."\n";
        // Crear iconos para exportar y ocultar/mostrar tabla
        if ($this->_id !== null) {
            $buffer .= '<div class="tableIcons hidden-print text-end">'."\n";
            $buffer .= $this->export($table);
            $buffer .= $this->showAndHide();
            $buffer .= '</div>'."\n";
        }
        // Iniciar tabla
        $buffer .= '<div class="clearfix"></div><div class="table-responsive">'."\n";
        $buffer .= '<table style="width:100%" class="'.$this->_class.'"'.($this->_id?' id="'.$this->_id.'"':'').'>'."\n";
        // Definir cabecera de la tabla
        // títulos de columnas
        $buffer .= "\t".'<thead>'."\n";
        $titles = array_shift($table);
        $buffer .= "\t\t".'<tr>'."\n";
        $i = 0;
        foreach ($titles as &$col) {
            if (isset($colsWidth[$i]) && $colsWidth[$i] != null) {
                $w = ' style="width:'.$colsWidth[$i].'px"';
            } else {
                $w = '';
            }
            $buffer .= "\t\t\t".'<th'.$w.'>'.$col.'</th>'."\n";
            $i++;
        }
        $buffer .= "\t\t".'</tr>'."\n";
        // extraer otras filas que son parte de la cabecera
        for ($i=1; $i<$thead; ++$i) {
            $titles = array_shift($table);
            if ($titles) {
                $buffer .= "\t\t".'<tr>'."\n";
                foreach ($titles as &$col) {
                    $buffer .= "\t\t\t".'<td>'.$col.'</td>'."\n";
                }
                $buffer .= "\t\t".'</tr>'."\n";
            }
        }
        $buffer .= "\t".'</thead>'."\n";
        // Definir datos de la tabla
        $buffer .= "\t".'<tbody>'."\n";
        if (is_array($table)) {
            foreach ($table as &$row) {
                $buffer .= "\t\t".'<tr>'."\n";
                foreach ($row as &$col) {
                    $buffer .= "\t\t\t".'<td>'.$col.'</td>'."\n";
                }
                $buffer .= "\t\t".'</tr>'."\n";
            }
        }
        $buffer .= "\t".'</tbody>'."\n";
        // Finalizar tabla
        $buffer .= '</table>'."\n";
        $buffer .= '</div>'."\n";
        $buffer .= '</div>'."\n";
        // Retornar tabla en HTML
        return $buffer;
    }

    /**
     * Crea los datos de la sesión de la tabla para poder exportarla
     * @param table Tabla que se está exportando
     */
    private function export(&$table)
    {
        // si no se debe exportar retornar vacío
        if (!$this->_export) {
            return '';
        }
        // generar datos para la exportación
        $data = [];
        $nRow = 0;
        $nRows = count($table);
        foreach ($table as &$row) {
            $nRow++;
            if (isset($this->_exportRemove['rows'])) {
                if (in_array($nRow, $this->_exportRemove['rows']) || in_array($nRow-$nRows-1, $this->_exportRemove['rows'])) {
                    continue;
                }
            }
            $nCol = 0;
            $nCols = count($row);
            $aux = [];
            foreach ($row as &$col) {
                $nCol++;
                if (isset($this->_exportRemove['cols'])) {
                    if (in_array($nCol, $this->_exportRemove['cols']) || in_array($nCol-$nCols-1, $this->_exportRemove['cols'])) {
                        continue;
                    }
                }
                $aux[] = $col;
            }
            $data[] = $aux;
        }
        // escribir datos para la exportación y colocar iconos si se logró
        // guardar en la caché
        $buffer = '';
        $data_saved = (new \sowerphp\core\Cache())->set('session.'.session_id().'.export.'.$this->_id, $data);
        if ($data_saved) {
            $buffer = '<button type="button" class="btn btn-primary dropdown-toggle mb-2" data-bs-toggle="dropdown" role="button" aria-expanded="false" id="dropdown_'.$this->_id.'" title="Guardar como..."><i class="fas fa-download fa-fw"></i> Guardar como...</button>';
            $buffer .= '<div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown_'.$this->_id.'">';
            $extensions = \sowerphp\core\Configure::read('app.tables.extensions') ? \sowerphp\core\Configure::read('app.tables.extensions') : $this->extensions;
            foreach ($extensions as $e => $n) {
                $buffer .= '<a href="'.url('/exportar/'.$e.'/'.$this->_id).'" class="dropdown-item">'.$n.'</a>';
            }
            $buffer .= '</div>'."\n";
        }
        return $buffer;
    }

    /**
     * Botones para mostrar y ocultar la tabla (+/-)
     */
    public function showAndHide()
    {
        $buffer = '';
        if ($this->_display !== null) {
            $buffer .= '<button type="button" class="btn btn-primary" onclick="$(\'#'.$this->_id.'\').show(); $(\'#tableShow'.$this->_id.'\').hide(); $(\'#tableHide'.$this->_id.'\').show();" id="tableShow'.$this->_id.'" title="Mostrar tabla"><i class="far fa-plus-square fa-fw"></i></button>';
            $buffer .= '<button type="button" class="btn btn-primary" onclick="$(\'#'.$this->_id.'\').hide(); $(\'#tableHide'.$this->_id.'\').hide(); $(\'#tableShow'.$this->_id.'\').show();" id="tableHide'.$this->_id.'" title="Ocultar tabla"><i class="far fa-minus-square fa-fw"></i></button>';
            $buffer .= '<script type="text/javascript"> $(function() { ';
            if ($this->_display) {
                $buffer .= '$(\'#tableShow'.$this->_id.'\').hide();';
            } else {
                $buffer .= '$(\'#'.$this->_id.'\').hide(); $(\'#tableHide'.$this->_id.'\').hide();';
            }
            $buffer .= ' }); </script>';
        }
        return $buffer;
    }

}
