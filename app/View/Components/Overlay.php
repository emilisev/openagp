<?php

namespace App\View\Components;

use App\Models\DiabetesData;
use Ghunti\HighchartsPHP\Highchart;
use Ghunti\HighchartsPHP\HighchartJsExpr;
use Illuminate\Support\Facades\Request;
use App\Helpers\StringToColor;
use function App\Models\readableDate;

class Overlay extends SeveralTimelinesCharts {
    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        $weeks = ceil(($this->m_data->getEnd() - $this->m_data->getBegin()) / (60 * 60 * 24 * 7));
        //prepare chart
        $chart = $this->createChart($weeks);
        $chart->chart->height = ($weeks * 300) + 50;
        $chart->legend = ['enabled' => true,
            /*'labelFormatter' => new HighchartJsExpr("function() {
                var maxRatio = $this->m_maxRatio;
                var lastVal = this.yData[this.yData.length - 1],
                chart = this.chart,
                xAxis = this.xAxis,
                points = this.points,
                avg = 0,
                counter = 0,
                min, max;
                this.yData.forEach(function(point, inx) {
                    var actualValue = maxRatio - point;
                    if (!min || min > actualValue) {
                        min = actualValue;
                    }

                    if (!max || max < actualValue) {
                        max = actualValue;
                    }

                    counter++;
                    avg += actualValue;
                });
                avg /= counter;

                return this.name + '<br>' +
                '<span>Min: 1U:' + min + 'g</span><br/>' +
                '<span>Max: 1U:' + max + 'g</span><br/>' +
                '<span>Moy: 1U:' + avg.toFixed(2) + 'g</span><br/>'
              }"
            )*/
        ];

        $weeklyGraphHeight = (round(100 / $weeks * 10)) / 10;
        $yAxisBase = $this->getBloodGlucoseYAxis($_greenLineWidth = 1);
        $yAxisBase['height'] = $weeklyGraphHeight.'%';

        $this->addBloodGlucoseSeries($chart, $yAxisBase, $weeks, $weeklyGraphHeight);

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
    private function addBloodGlucoseSeries(Highchart $_chart, array $y_AxisBase, int $_weeks, float $_weeklyGraphHeight): void {
        //add 1 graph per week
        $yAxisNumber = $xAxisNumber = $currentHeight = 0;
        $weekDay = 0;
        $_chart->xAxis = [
            [
                'type' => 'datetime',
                'labels' => [
                    'format' => '{value:%H:%M}',
                ],
                'opposite' => true,
                'lineWidth' => 0,
                //'tickWidth' => 0,
                'startOnTick' => true,
                'endOnTick' => true,
                'showFirstLabel' => false,
                'showLastLabel' => false,
            ]
        ];
        for($currentDate = $this->m_data->getBegin(); $currentDate <= $this->m_data->getEnd(); $currentDate += 60 * 60 * 24) {
            $data = DiabetesData::filterData($this->m_data->getBloodGlucoseData(), $currentDate, $currentDate + 60 * 60 * 24);
            if($weekDay == 0 ) {
                $_chart->yAxis[] =
                    ['top' => $currentHeight.'%', 'id' => 'gloodGlucose-yAxis'.$yAxisNumber] +
                    $y_AxisBase;
            } else {
                $modifiedData = [];
                foreach($data as $key => $value) {
                    $modifiedData[$key - ($weekDay * 60 * 60 * 24 * 1000)] = $value;
                }
                $data = $modifiedData;
            }
            $dataForChart = $this->formatTimeDataForChart($data);
            $_chart->series[] = [
                'type' => 'line',
                'name' => date('D d', $currentDate),
                'data' => $dataForChart,
                'xAxis' => $xAxisNumber,
                'yAxis' => 'gloodGlucose-yAxis'.$yAxisNumber,
            ];

            if($weekDay ++ >= 7) {
                $weekDay = 0;
                $yAxisNumber++;
                $xAxisNumber++;
                $currentHeight += $_weeklyGraphHeight;
                $_chart->xAxis[] = [
                    'visible' => false,
                    'type' => 'datetime',
                ];
            }
        }
    }

    private function addCarbsSeries(Highchart $_chart, int $_weeks, float $_weeklyGraphHeight) {
        //add 1 serie per week
        $yAxisNumber = $xAxisNumber = $currentHeight = 0;
        for ($weekNum = $_weeks; $weekNum >= 1; $weekNum--) {
            $data = $this->m_data->getDailyTreatmentsByWeek($weekNum)['carbs'];
            $this->addCarbsSerie($_chart, $data, $_weeklyGraphHeight, $currentHeight, $yAxisNumber, $xAxisNumber);
            $currentHeight += $_weeklyGraphHeight;
            $yAxisNumber++;
            $xAxisNumber++;
        }
    }

    private function addTreatmentsSeries(Highchart $_chart, int $_weeks, float $_weeklyGraphHeight) {
        //add 1 serie per week
        $yAxisNumber = $xAxisNumber = $currentHeight = 0;
        for ($weekNum = $_weeks; $weekNum >= 1; $weekNum--) {
            $data = @$this->m_data->getDailyTreatmentsByWeek($weekNum)['insulin'];
            if(!empty($data)) {
                $this->addTreatmentsSerie($_chart, $data, $_weeklyGraphHeight, $currentHeight, $yAxisNumber, $xAxisNumber);
            }
            $currentHeight += $_weeklyGraphHeight;
            $yAxisNumber++;
            $xAxisNumber++;
        }
    }
}
