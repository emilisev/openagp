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
        foreach($this->m_validTimesInDay as $time => $type) {
            if($type == 'night') {
                unset($this->m_validTimesInDay[$time]);
            }
        }
        $this->m_maxRatio = ceil($this->m_ratiosByLunchType['maxRatio'] * 1.1);
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
        $stringToColor = new StringToColor();
        $yAxisNumber = $xAxisNumber = 0;
        foreach($this->m_validTimesInDay as $timeInDay) {
            $datum = @$this->m_ratiosByLunchType[$timeInDay];
            if(empty($datum)) continue;
            foreach($datum as &$value) {
                $value['y'] = $this->m_maxRatio - $value['y'];
            }
            unset($value);
            $_chart->series[] = [
                'name' => LabelProviders::get($timeInDay),
                'color' => $stringToColor->handle($timeInDay),
                //'borderWidth' => 2,
                'data' => $datum,
                'pointWidth' => 15,
                //'xAxis' => "xAxis$xAxisNumber",
                'yAxis' => "yAxis$yAxisNumber",
                'dataLabels' => ['enabled' => true,
                    'style' => ['fontSize' => '0.8rem'],
                    'formatter' => new HighchartJsExpr("function () {
                        var point = this;
                        var s = '1U:'+($this->m_maxRatio - point.y).toFixed(0)+'g';
                        if(point.point.target == 'inRange') {
                            s += ' ✓';
                        } else if(point.point.target == 'low') {
                            s += ' ↓';
                        } else if(point.point.target == 'lightHigh') {
                            s += ' ↑';
                        } else if(point.point.target == 'high') {
                            s += ' ↑↑';
                        }
                        return s;
                    }")

                ],
                'tooltip' => [
                    'useHTML' => true,
                    'pointFormatter' => new HighchartJsExpr("function () {
                        var point = this;
                        var s = '<span style=\"color:'+point.color+'\">●</span> '+
                        point.series.name+': <b>1U:'+($this->m_maxRatio - point.y).toFixed(0)+'g</b>';
                        if(point.target == 'low') {
                            s += ' Trop fort (augmenter le ratio)';
                        } else if(point.target == 'lightHigh') {
                            s += ' Légèrement trop faible (baisser le ratio)';
                        } else if(point.target == 'high') {
                            s += ' Trop faible (baisser le ratio)';
                        }
                        s +='<br/>';
                        return s;
                    }"),
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
                '<span>Min: 1U:' + min.toFixed(2) + 'g</span><br/>' +
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
