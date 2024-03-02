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
        foreach($ratios as $type => $datum) {
            if(empty($datum)) continue;
            ksort($datum);
            $datum = $statComputer->computeAverage($datum, 60 * 60 * 24, $dataStartPoint);
            $_chart->series[] = [
                'type' => 'line',
                'name' => LabelProviders::get($type),
                'color' => $stringToColor->handle($type),
                'data' => $this->formatTimeDataForChart($datum),
                'lineWidth' => 2,
            ];
        }
    }


    private function createChart(): Highchart {
        $chart = $this->createDefaultChart();
        $chart->chart->marginBottom = 50;
        $xAxis = ['tickInterval' => 24 * 60 * 60 * 1000, 'labels' => ['format' => '{value:%d/%m}']]+$this->getBottomLabelledXAxis();
        $chart->xAxis = [$xAxis];
        return $chart;
    }
}
