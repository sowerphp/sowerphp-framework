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
 * Clase para generar gráficos
 * Hace uso de libchart, presentando métodos más simples y evitando que el
 * programador deba escribir tando código
 */
class View_Helper_Chart
{

    private $defaultOptions = array( ///< Opciones por defecto de los gráficos
        'width' => 750,
        'height' => 300,
        'ratio' => 0.65,
        'padding' => [10, 1, 50, 70],
    );

    /**
     * Método que genera un gráfico
     * @param title Título del gráfico
     * @param series Datos del gráfico
     * @param type Tipo de gráfico que se desea generar
     * @param options Opciones para el gráfico
     * @param exit =true si se debe terminar el script, =false si no se debe terminar
     */
    private function generate($title, $series, $type, $options = array(), $exit = true)
    {
        // asignar opciones por defecto del gráfico
        $options = array_merge($this->defaultOptions, $options);
        // crear gráfico
        $class = '\\Libchart\\View\\Chart\\'.$type.'Chart';
        $chart = new $class($options['width'], $options['height']);
        // asignar colores
        if (isset($options['colors'])) {
            $colors = array();
            foreach ($options['colors'] as &$c) {
                $colors[] = new \Libchart\View\Color\Color($c[0], $c[1], $c[2]);
            }
            if ($type == 'Line') {
                $chart->getPlot()->getPalette()->setLineColor($colors);
            }
            else if ($type == 'VerticalBar') {
                $chart->getPlot()->getPalette()->setBarColor($colors);
            }
        }
        // conjunto de series
        $dataSet = new \Libchart\Model\XYSeriesDataSet();
        // procesar cada serie
        foreach ($series as $serie => &$data) {
            $s = new \Libchart\Model\XYDataSet();
            foreach ($data as $key => &$value) {
                if (is_array($value)) {
                    $key = array_shift($value);
                    $value = array_shift($value);
                }
                $s->addPoint(new \Libchart\Model\Point(
                    $key,
                    $value
                ));
            }
            $dataSet->addSerie($serie, $s);
        }
        // renderizar
        $this->render($chart, $title, $dataSet, $options, $exit);
    }

    /**
     * Función que renderiza el gráfico que se está generando
     * @param chart Gráfico a renderizar
     * @param title Título del gráfico
     * @param data Datos del gráfico
     * @param options Opciones del gráfico
     * @param exit =true si se debe terminar el script, =false si no se debe terminar
     */
    private function render(&$chart, $title, $data, $options, $exit = true)
    {
        // opciones por defecto
        $options = array_merge (
            ['disposition'=>'inline', 'filename'=>'grafico.png'],
            $options
        );
        // asignar opciones al gráfico
        if (!empty($options['padding'])) {
            $Padding = new \Libchart\View\Primitive\Padding(
                $options['padding'][0], // arriba
                $options['padding'][1], // derecha
                $options['padding'][2], // abajo
                $options['padding'][3]  // izquierda
            );
            $chart->getPlot()->setGraphPadding($Padding);
        }
        $chart->setTitle($title);
        $chart->setDataSet($data);
        $chart->getPlot()->setGraphCaptionRatio($options['ratio']);
        // enviar cabeceras
        ob_clean ();
        header('Content-type: image/png');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Disposition: '.$options['disposition'].'; filename="'.$options['filename'].'"');
        // renderizar y terminar script
        $chart->render();
        if ($exit) {
            exit(0);
        }
    }

    /**
     * Método para generar un gráfico de lineas
     * @param title Título del gráfico
     * @param series Datos del gráfico
     * @param options Opciones para el gráfico
     * @param exit =true si se debe terminar el script, =false si no se debe terminar
     */
    public function line($title, $series, $options=array(), $exit= true)
    {
        $this->generate ($title, $series, 'Line', $options, $exit);
    }

    /**
     * Método para generar un gráfico de barras verticales
     * @param title Título del gráfico
     * @param series Datos del gráfico
     * @param options Opciones para el gráfico
     * @param exit =true si se debe terminar el script, =false si no se debe terminar
     */
    public function vertical_bar($title, $series, $options = array(), $exit = true)
    {
        $this->generate($title, $series, 'VerticalBar', $options, $exit);
    }

    /**
     * Método para generar un gráfico de barras horizontales
     * @param title Título del gráfico
     * @param series Datos del gráfico
     * @param options Opciones para el gráfico
     * @param exit =true si se debe terminar el script, =false si no se debe terminar
     */
    public function horizontal_bar($title, $series, $options = array(), $exit = true)
    {
        $this->generate($title, $series, 'HorizontalBar', $options, $exit);
    }

    /**
     * Método para generar un gráfico de torta
     * @param title Título del gráfico
     * @param data Datos del gráfico
     * @param options Opciones para el gráfico
     * @param exit =true si se debe terminar el script, =false si no se debe terminar
     */
    public function pie($title, $data, $options = array(), $exit = true)
    {
        // asignar opciones por defecto del gráfico
        $options = array_merge($this->defaultOptions, ['padding'=>false], $options);
        // crear gráfico
        $chart = new \Libchart\View\Chart\PieChart($options['width'], $options['height']);
        // asignar colores
        if (isset($options['colors'])) {
            $colors = array();
            foreach ($options['colors'] as &$c) {
                $colors[] = new Color($c[0], $c[1], $c[2]);
            }
            $chart->getPlot()->getPalette()->setPieColor($colors);
        }
        // asignar datos
        $dataSet = new \Libchart\Model\XYDataSet();
        foreach ($data as $key => $value) {
            $dataSet->addPoint(new \Libchart\Model\Point(
                $key.' ('.$value.')',
                $value
            ));
        }
        $chart->setDataSet($dataSet);
        //renderizar
        $this->render($chart, $title, $dataSet, $options, $exit);
    }

}
