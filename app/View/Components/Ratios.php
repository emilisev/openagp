<?php

namespace App\View\Components;

use App\Helpers\LabelProviders;
use App\Helpers\StatisticsComputer;
use App\Models\DiabetesData;
use Ghunti\HighchartsPHP\Highchart;
use App\Helpers\StringToColor;
use Ghunti\HighchartsPHP\HighchartJsExpr;

class Ratios extends HighChartsComponent {

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    protected $m_validTimesInDay;

    protected $m_maxRatio;

    protected $m_ratiosByLunchType;


    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        $this->m_ratiosByLunchType = $this->m_data->getRatiosByLunchType();
        $this->m_validTimesInDay = array_intersect(config('diabetes.lunchTypes'), array_keys($this->m_ratiosByLunchType));
        $max = 0;
        foreach($this->m_validTimesInDay as $timeInDay) {
            $max = max($max, max(@$this->m_ratiosByLunchType[$timeInDay]));
        }
        $this->m_maxRatio = ceil($max * 1.1);

        $chart = $this->createChart();
        $this->addCarbsSeries($chart);
        echo '<script type="module">'.$chart->render().'</script>';
    }

    /* * * * * * * * * * * * * * * * * * * * * * PRIVATE METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


    private function addCarbsSeries(Highchart $_chart) {
        if(empty($this->m_validTimesInDay)) {
            return;
        }
        $percentInc = 100 / count($this->m_validTimesInDay);
        $dataStartPoint = $this->m_data->getBegin()*DiabetesData::__1SECOND;
        $statComputer = new StatisticsComputer();
        $stringToColor = new StringToColor();
        $yAxisNumber = $xAxisNumber = 0;
        foreach($this->m_validTimesInDay as $timeInDay) {
            $datum = @$this->m_ratiosByLunchType[$timeInDay];
            if(empty($datum)) continue;
            $datum = $statComputer->computeAverage($datum, 60 * 60 * 24, $dataStartPoint);
            foreach($datum as &$value) {
                $value = $this->m_maxRatio - $value;
            }
            unset($value);
            $_chart->series[] = [
                'name' => LabelProviders::get($timeInDay),
                'color' => $stringToColor->handle($timeInDay),
                'data' => $this->formatTimeDataForChart($datum),
                'pointWidth' => 15,
                //'xAxis' => "xAxis$xAxisNumber",
                'yAxis' => "yAxis$yAxisNumber",
                'dataLabels' => ['enabled' => true, 'format' => '1U:{subtract '.$this->m_maxRatio.' y}g'],
                'tooltip' => [
                    'useHTML' => true,
                    'pointFormat' => '<span style="color:{point.color}">●</span> '.
                        '{series.name}: <b>1U:{subtract '.$this->m_maxRatio.' point.y}g</b><br/>',
                ]
            ];
            //barres
            $_chart->yAxis[] = [
                'id' => "yAxis$yAxisNumber",
                'left' => ($yAxisNumber * $percentInc).'%',
                'width' => ($percentInc-5).'%',
                'tickPositions' => [0, $this->m_maxRatio],
                'visible' => false,
            ];
            //grille
            /*$_chart->xAxis[] = [
                'id' => "xAxis$xAxisNumber",
                'left' => (($xAxisNumber * ($percentInc+4))).'%',
                'width' => ($percentInc-5).'%',
            ] + $xAxis;*/
            //barres
            /*$_chart->yAxis[] = [
                'id' => "yAxis$yAxisNumber",
                'left' => 50+($yAxisNumber * 200),
                'width' => 100,
            ];*/
            //grille
            /*$_chart->xAxis[] = [
                    'id' => "xAxis$xAxisNumber",
                    'left' => 50+($xAxisNumber * 200),
                    'width' => 100,
                ] + $xAxis;*/
            $yAxisNumber++;
            $xAxisNumber++;
        }
    }


    private function createChart(): Highchart {
        $chart = $this->createDefaultChart();
        $this->setChartHeightBasedOnDuration($chart);
        $chart->legend = ['enabled' => true,
            'labelFormatter' => new HighchartJsExpr("function() {
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
            )
      ];
        $xAxis = ['tickInterval' => 24 * 60 * 60 * 1000, 'labels' => ['format' => '{value:%d/%m}']]+$this->getBottomLabelledXAxis();
        $chart->xAxis = [$xAxis];
        return $chart;
    }
}
