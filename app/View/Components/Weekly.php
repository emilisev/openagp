<?php

namespace App\View\Components;

use Ghunti\HighchartsPHP\Highchart;
use Ghunti\HighchartsPHP\HighchartJsExpr;
use Illuminate\Support\Facades\Request;
use StringToColor\StringToColor;

class Weekly extends HighChartsComponent {
    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        if(Request::route()->getName() == 'agp') {
            $weeks = 2;
        } else {
            $weeks = ceil(($this->m_data->getEnd() - $this->m_data->getBegin()) / (60 * 60 * 24 * 7));
        }
        $start = strtotime("midnight +1day -$weeks weeks", $this->m_data->getEnd());
        //var_dump("start", date('Y-m-d H:i:s', $start));
        //prepare plotLines
        $data = $this->m_data->getDailyDataByWeek($weeks);
        ksort($data);

        //prepare chart
        $chart = $this->createChart($weeks);
        list($ticks, $plotLines) = $this->computeTicksAndPlotlines($start);

        $weeklyGraphHeight = (round(100 / $weeks * 10)) / 10;
        $yAxisBase = $this->getBloodGlucoseYAxis($_greenLineWidth = 1);
        $yAxisBase['height'] = $weeklyGraphHeight.'%';
        $yAxisBase['id'] = 'gloodGlucose-yAxis1';

        $this->addBloodGlucoseSeries($chart, $yAxisBase, $plotLines, $ticks, $weeks, $weeklyGraphHeight);
        $this->addTreatmentsSeries($chart, $weeks, $weeklyGraphHeight);
        $this->addCarbsSeries($chart, $weeks, $weeklyGraphHeight);

        //echo "<pre>".$chart->render()."</pre>";
        echo '<script type="module">'.$chart->render().'</script>';
    }

    /* * * * * * * * * * * * * * * * * * * * * * PRIVATE METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * @param Highchart $_chart
     * @param array $y_AxisBase
     * @param mixed $_plotLines
     * @param mixed $_ticks
     * @param int $_weeks
     * @param float $_weeklyGraphHeight
     */
    private function addBloodGlucoseSeries(Highchart $_chart, array $y_AxisBase, mixed $_plotLines, mixed $_ticks, int $_weeks, float $_weeklyGraphHeight): void {
        //add 1 serie per week
        $yAxisNumber = $xAxisNumber = $currentHeight = 0;
        for ($weekNum = $_weeks; $weekNum >= 1; $weekNum--) {
            $data = $this->m_data->getDailyDataByWeek($weekNum);
            $dataForChart = $this->formatTimeDataForChart($data);
            if($xAxisNumber == 0) {
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
                ['top' => $currentHeight.'%', 'id' => 'gloodGlucose-yAxis'.$yAxisNumber] +
                $y_AxisBase;
            $currentHeight += $_weeklyGraphHeight;
            $_chart->series[] = [
                'type' => 'line',
                'data' => $dataForChart,
                'dataLabels' => [
                    'enabled' => true,
                    'verticalAlign' => 'bottom',
                    'formatter' => new HighchartJsExpr("function() {
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
                'xAxis' => $xAxisNumber,
                'yAxis' => 'gloodGlucose-yAxis'.$yAxisNumber,
                'zones' => $this->getDefaultZones()
            ];
            $yAxisNumber++;
            $xAxisNumber++;
        }
    }

    private function addCarbsSeries(Highchart $_chart, int $_weeks, float $_weeklyGraphHeight) {
        //add 1 serie per week
        $stringToColor = new StringToColor();
        $yAxisNumber = $xAxisNumber = $currentHeight = 0;
        for ($weekNum = $_weeks; $weekNum >= 1; $weekNum--) {
            $data = $this->m_data->getDailyTreatmentsByWeek($weekNum)['carbs'];
            if(!empty($data)) {
                $maxCarbs = max($data);
                $_chart->yAxis[] = [
                    'id' => 'carbs-yAxis'.$yAxisNumber,
                    'visible' => false,
                    'height' => $_weeklyGraphHeight.'%',
                    'max' => $maxCarbs / config('diabetes.treatments.relativeAxisHeight'),
                    'top' => $currentHeight.'%',
                    'min' => 0,
                ];

                $_chart->series[] = [
                    'type' => 'column',
                    'color' => $stringToColor->handle('carbs'),
                    'data' => $this->formatTimeDataForChart($data),
                    'yAxis' => 'carbs-yAxis'.$yAxisNumber,
                    'xAxis' => $xAxisNumber,
                    'pointRange' => 60 * 60 * 1000, //largeur
                    'opacity' => 1,
                    'dataLabels' => ['enabled' => true, 'format' => '{point.name}']
                ];
            }
            $currentHeight += $_weeklyGraphHeight;
            $yAxisNumber++;
            $xAxisNumber++;
        }
    }

    private function addTreatmentsSeries(Highchart $_chart, int $_weeks, float $_weeklyGraphHeight) {
        //add 1 serie per week

        $stringToColor = new StringToColor();
        $yAxisNumber = $xAxisNumber = $currentHeight = 0;
        for ($weekNum = $_weeks; $weekNum >= 1; $weekNum--) {
            $data = $this->m_data->getDailyTreatmentsByWeek($weekNum)['insulin'];
            $max = 0;
            foreach($data as $datum) {
                if(!empty($datum)) {
                    $max = max($max, max($datum));
                }
            }
            if($max > 0) {
                $_chart->yAxis[] = [
                    'top' => $currentHeight.'%',
                    'visible' => false,
                    'id' => 'treatments-yAxis'.$yAxisNumber,
                    'height' => $_weeklyGraphHeight.'%',
                    'min' => 0,
                    'max' => $max / config('diabetes.treatments.relativeAxisHeight')
                ];
                foreach($data as $type => $datum) {
                    $dataForChart = $this->formatTimeDataForChart($datum);
                    $_chart->series[] = [
                        'type' => 'column',
                        'color' => $stringToColor->handle($type),
                        'data' => $dataForChart,
                        'xAxis' => $xAxisNumber,
                        'yAxis' => 'treatments-yAxis'.$yAxisNumber,
                        'pointRange' => 60 * 60 * 1000, //largeur
                        //'dataLabels' => ['enabled' => true, 'format' => '{y}UI']
                    ];
                }
            }
            $currentHeight += $_weeklyGraphHeight;
            $yAxisNumber++;
            $xAxisNumber++;
        }
    }

    /**
     * @param int $start
     * @return array
     */
    private function computeTicksAndPlotlines(int $start): array {
        $plotLines = $ticks = [];
        /*foreach(array_keys($data) as $i => $microKey) {
            $key = $microKey / DiabetesData::__1SECOND;*/
        $darkLine = true;
        for ($key = $start; $key <= $start + 60 * 60 * 24 * 7; $key += 60 * 60 * 12) {
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

    private function createChart(int $_weeks): Highchart {
        $chart = $this->createDefaultChart();
        $chart->chart->height = ($_weeks * 100) + 50;

        return $chart;
    }

}
