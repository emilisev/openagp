<?php

namespace App\View\Components;

use App\Helpers\LabelProviders;
use App\Helpers\StatisticsComputer;
use App\Models\DiabetesData;
use Ghunti\HighchartsPHP\Highchart;
use App\Helpers\StringToColor;

class Ratios extends HighChartsComponent {

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        $chart = $this->createChart();
        $this->addCarbsSeries($chart);
        echo '<script type="module">'.$chart->render().'</script>';
    }

    /* * * * * * * * * * * * * * * * * * * * * * PRIVATE METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


    private function addCarbsSeries(Highchart $_chart) {
        $ratios = $this->m_data->getRatiosByLunchType();
        $dataStartPoint = $this->m_data->getBegin()*DiabetesData::__1SECOND;
        $statComputer = new StatisticsComputer();
        $stringToColor = new StringToColor();
        $yAxisNumber = $xAxisNumber = 0;
        $xAxis = ['tickInterval' => 24 * 60 * 60 * 1000, 'labels' => ['format' => '{value:%d/%m}']]+$this->getBottomLabelledXAxis();
        $validTimesInDay = array_intersect(config('diabetes.lunchTypes'), array_keys($ratios));
        $percentInc = 100 / count($validTimesInDay);
        $max = 0;
        foreach($validTimesInDay as $timeInDay) {
            $max = max($max, max(@$ratios[$timeInDay]));
        }
        foreach($validTimesInDay as $timeInDay) {
            $datum = @$ratios[$timeInDay];
            if(empty($datum)) continue;
            $datum = $statComputer->computeAverage($datum, 60 * 60 * 24, $dataStartPoint);
            foreach($datum as &$value) {
                $value = $max - $value;
            }
            unset($value);
            $_chart->series[] = [
                'type' => 'bar',
                'name' => LabelProviders::get($timeInDay),
                'color' => $stringToColor->handle($timeInDay),
                'data' => $this->formatTimeDataForChart($datum),
                'lineWidth' => 2,
                //'xAxis' => "xAxis$xAxisNumber",
                'yAxis' => "yAxis$yAxisNumber",
                'dataLabels' => ['enabled' => true, 'format' => '1U:{subtract '.$max.' y}g'],
                'tooltip' => [
                    'useHTML' => true,
                    'pointFormat' => '<span style="color:{point.color}">●</span> '.
                        '{series.name}: <b>1U:{subtract '.$max.' point.y}g</b><br/>',
                ]
            ];
            //barres
            $_chart->yAxis[] = [
                'id' => "yAxis$yAxisNumber",
                'left' => ($yAxisNumber * $percentInc).'%',
                'width' => ($percentInc-5).'%',
                'tickPositions' => [0, ceil($max/10)*10],
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
        $chart->chart->marginLeft = 50;
        $chart->chart->marginBottom = 0;
        $xAxis = ['tickInterval' => 24 * 60 * 60 * 1000, 'labels' => ['format' => '{value:%d/%m}']]+$this->getBottomLabelledXAxis();
        $chart->xAxis = [$xAxis];
        return $chart;
    }
}
