<?php

namespace App\View\Components;

use Ghunti\HighchartsPHP\Highchart;
use Ghunti\HighchartsPHP\HighchartJsExpr;
use App\Helpers\StringToColor;

abstract class SeveralTimelinesCharts extends HighChartsComponent {

    /**
     * @param Highchart $_chart
     * @param array $_data
     * @param float $_weeklyGraphHeight
     * @param $_currentHeight
     * @param int $_xAxisNumber
     * @param int $_yAxisNumber
     * @param array $y_AxisBase
     * @param mixed $_plotLines
     * @param mixed $_ticks
     */
    protected function addBloodGlucoseSerie(Highchart $_chart, array $_data, float $_weeklyGraphHeight, $_currentHeight, int $_xAxisNumber, int $_yAxisNumber, array $y_AxisBase, mixed $_plotLines, mixed $_ticks): void {
        $dataForChart = $this->formatTimeDataForChart($_data);
        if($_xAxisNumber == 0) {
            $_chart->xAxis = [
                [
                    'type' => 'datetime',
                    'labels' => [
                        'format' => '{value:%A}',
                    ],
                    'plotLines' => $_plotLines,
                    'tickPositions' => $_ticks,
                    'opposite' => true,
                    'lineWidth' => 0,
                    'tickWidth' => 0,
                    'startOnTick' => true,
                    'endOnTick' => true,
                    'showFirstLabel' => false,
                    'showLastLabel' => false,
                ]
            ];
        } else {
            $_chart->xAxis[] = [
                'visible' => false,
                'type' => 'datetime',
            ];
        }

        $_chart->yAxis[] =
            ['top' => $_currentHeight.'%', 'id' => 'gloodGlucose-yAxis'.$_yAxisNumber] +
            $y_AxisBase;
        $_chart->series[] = [
            'type' => 'line',
            'name' => 'Glycémie',
            'data' => $dataForChart,
            'dataLabels' => [
                'enabled' => true,
                'verticalAlign' => 'bottom',
                'formatter' => new HighchartJsExpr(
                    "function() {
                        var date = new Date(this.key);
                        var result = null;
                        if(date.getHours() == 12 && date.getMinutes() == 0) {
                            result = date.getDate();
                        }
                        if(result == 1) {
                            result += '/'+(date.getMonth()+1);
                        }
                        return result;
                    }"),
                'className' => 'upper-labels'
            ],
            'xAxis' => $_xAxisNumber,
            'yAxis' => 'gloodGlucose-yAxis'.$_yAxisNumber,
            'zones' => $this->getDefaultZones()
        ];
    }

    /**
     * @param Highchart $_chart
     * @param $_data
     * @param float $_weeklyGraphHeight
     * @param float $_currentHeight
     * @param int $_yAxisNumber
     * @param int $_xAxisNumber
     */
    protected function addCarbsSerie(Highchart $_chart, $_data, float $_weeklyGraphHeight, float $_currentHeight, int $_yAxisNumber, int $_xAxisNumber): void {
        if(empty($_data)) {
            return;
        }
        $stringToColor = new StringToColor();
        $maxCarbs = max($_data);
        $_chart->yAxis[] = [
            'id' => 'carbs-yAxis'.$_yAxisNumber,
            'visible' => false,
            'height' => $_weeklyGraphHeight.'%',
            'max' => $maxCarbs / config('diabetes.treatments.relativeAxisHeight'),
            'top' => $_currentHeight.'%',
            'min' => 0,
        ];

        $_chart->series[] = [
            'type' => 'column',
            'name' => 'Glucides',
            'color' => $stringToColor->handle('carbs'),
            'data' => $this->formatTimeDataForChart($_data),
            'yAxis' => 'carbs-yAxis'.$_yAxisNumber,
            'xAxis' => $_xAxisNumber,
            'pointRange' => 60 * 60 * 1000, //largeur
            'opacity' => 1,
            'dataLabels' => ['enabled' => true, 'format' => '{point.name}']
        ];
    }

    /**
     * @param Highchart $_chart
     * @param $_data
     * @param float $_weeklyGraphHeight
     * @param $_currentHeight
     * @param int $_yAxisNumber
     * @param int $_xAxisNumber
     */
    protected function addTreatmentsSerie(Highchart $_chart, $_data, float $_weeklyGraphHeight, $_currentHeight, int $_yAxisNumber, int $_xAxisNumber): void {
        $stringToColor = new StringToColor();
        $max = 0;
        foreach($_data as $datum) {
            if(!empty($datum)) {
                $max = max($max, max($datum));
            }
        }
        if($max == 0) {
            return;
        }
        $_chart->yAxis[] = [
            'top' => $_currentHeight.'%',
            'visible' => false,
            'id' => 'treatments-yAxis'.$_yAxisNumber,
            'height' => $_weeklyGraphHeight.'%',
            'min' => 0,
            'max' => $max / config('diabetes.treatments.relativeAxisHeight')
        ];
        foreach($_data as $type => $datum) {
            $dataForChart = $this->formatTimeDataForChart($datum);
            $_chart->series[] = [
                'type' => 'column',
                'name' => $type,
                'color' => $stringToColor->handle($type),
                'data' => $dataForChart,
                'xAxis' => $_xAxisNumber,
                'yAxis' => 'treatments-yAxis'.$_yAxisNumber,
                'pointRange' => 60 * 60 * 1000, //largeur
                //'dataLabels' => ['enabled' => true, 'format' => '{y}UI']
            ];
        }
    }

    /**
     * @param int $_start
     * @return array
     */
    protected function computeTicksAndPlotlines(int $_start, $_max, $_span): array {
        $plotLines = $ticks = [];
        /*foreach(array_keys($data) as $i => $microKey) {
            $key = $microKey / DiabetesData::__1SECOND;*/
        $darkLine = true;
        for($key = $_start; $key <= $_max; $key += $_span) {
            $microKey = $key * 1000;
            if(empty($ticks)) {
                $ticks[] = $microKey;
            }
            $plotLines[] = [
                'value' => $microKey,
                'color' => $darkLine ? '#777777' : '#e9e9e9'
            ];
            if(!$darkLine) {
                $ticks[] = $microKey;
                //var_dump(readableDate($microKey));
            }
            $darkLine = !$darkLine;
        }
        $ticks[] = $microKey;
        return array($ticks, $plotLines);
    }

    protected function createChart(int $_timelinesCount): Highchart {
        $chart = $this->createDefaultChart();
        $chart->tooltip = ['shared' => false];
        $chart->chart->height = ($_timelinesCount * 100) + 50;

        $chart->plotOptions->series->point = [
            'events' => [
                'click' => new HighchartJsExpr(
                "function() {
                    window.location.href = '/daily?day='+(moment(this.x).format('DD/MM/YYYY'));
                }")
            ]
        ];

        return $chart;
    }
}
