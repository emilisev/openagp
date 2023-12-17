<?php

namespace App\View\Components;

use App\Helpers\StatisticsComputer;
use Ghunti\HighchartsPHP\Highchart;
use StringToColor\StringToColor;

class Treatment extends HighChartsComponent {

    private array $m_bgData;

    private $m_dataStartPoint = null;
    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Get the view / contents that represent the component.
     */
    public function render() {

        $statComputer = new StatisticsComputer();
        $data = $this->m_data->getBloodGlucoseData();
        $this->m_bgData = $statComputer->computeAverage($data, 60 * 60 * 24);
        $this->m_dataStartPoint = array_key_first($this->m_bgData);
        ksort($this->m_bgData);


        $chart = $this->createChart();
        $this->addBloodGlucoseSeries($chart);
        $this->addTreatmentsSeries($chart);
        //$this->addCarbsSeries($chart);
        echo '<script type="module">'.$chart->render().'</script>';
    }

    /* * * * * * * * * * * * * * * * * * * * * * PRIVATE METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * @param Highchart $_chart
     * @param array $y_AxisBase
     * @param mixed $_plotLines
     * @param mixed $_ticks
     * @param int $_months
     * @param float $_weeklyGraphHeight
     */
    private function addBloodGlucoseSeries(Highchart $_chart): void {
        $_chart->series[] = [
            'type' => 'line',
            'data' => $this->formatTimeDataForChart($this->m_bgData),
            'zones' => $this->getDefaultZones(),
            'lineWidth' => 2,
            //'marker' => ['enabled' => true, 'radius' => 1,]
        ];
    }

    private function addCarbsSeries(Highchart $_chart, int $_weeks, float $_weeklyGraphHeight) {
        //add 1 serie per week
        $yAxisNumber = $xAxisNumber = $currentHeight = 0;
        $statComputer = new StatisticsComputer();
        for ($weekNum = $_weeks; $weekNum >= 1; $weekNum--) {
            $data = $this->m_data->getDailyTreatmentsByMonth($weekNum)['carbs'];
            $data = $statComputer->computeSum($data, 60 * 60 * 24, $this->m_dataStartPoint);
            $this->addCarbsSerie($_chart, $data, $_weeklyGraphHeight, $currentHeight, $yAxisNumber, $xAxisNumber);
            $currentHeight += $_weeklyGraphHeight;
            $yAxisNumber++;
            $xAxisNumber++;
        }
    }

    private function addTreatmentsSeries(Highchart $_chart) {
        $data = $this->m_data->getTreatmentsData()['insulin'];
        if(empty($data)) {
            return;
        }
        $statComputer = new StatisticsComputer();
        $stringToColor = new StringToColor();
        $sums = [];
        $series = [];
        $plotLines = [];
        foreach ($data as $type => $datum) {
            $datum = $statComputer->computeSum($datum, 60 * 60 * 24, $this->m_dataStartPoint);
            foreach($datum as $key => $value) {
                if(array_key_exists($key, $sums)) {
                    $sums[$key] += $value;
                } else {
                    $sums[$key] = $value;
                }
            }
            $series[array_sum($datum)][] = [
                'type' => 'area',
                'stacking' => 'normal',
                'color' => $stringToColor->handle($type),
                'data' => $this->formatTimeDataForChart($datum),
                'yAxis' => 'insulin-yAxis',
            ];
            $avgInsulin = round((array_sum($datum)/count($datum))*10)/10;
            $plotLines[] = ['value' => last($datum), 'width' => 0, 'zIndex' => 1000, 'label' =>
                ['align' => 'right', 'x' => 25, 'text' => "$type<br/>{$avgInsulin}UI"]];
        }
        $max = max($sums);
        //place insulin type with less quantity at bottom
        krsort($series);
        foreach($series as $seriesList) {
            foreach($seriesList as $serie) {
                $_chart->series[] = $serie;
            }
        }
        $_chart->yAxis[] = ['tickPositions' => [0, $max * 3], 'plotLines' => $plotLines] + $this->getTreatmentYAxis();
    }

    private function createChart(): Highchart {
        $chart = $this->createDefaultChart();
        $chart->chart->marginBottom = 30;
        $chart->chart->marginRight = 50;

        $bloodGlucoseYAxis = $this->getBloodGlucoseYAxis();
        $avgBG = array_sum($this->m_bgData) / count($this->m_bgData);
        $bloodGlucoseYAxis['plotLines'][] = ['value' => last($this->m_bgData), 'width' => 0, 'zIndex' => 1000, 'label' =>
            ['align' => 'right', 'x' => 25, 'text' => 'Moy. gly.<br/>'.round($avgBG).'<br/>mg/dL']];

        $chart->yAxis = [$bloodGlucoseYAxis];
        $xAxis = [
            'labels' => [
                'format' => '{value:%d/%m}',
            ],
            'tickInterval' => 7 * 24 * 60 * 60 * 1000,
        ] + $this->getBottomLabelledXAxis();
        $chart->xAxis = $xAxis;
        return $chart;
    }

}
