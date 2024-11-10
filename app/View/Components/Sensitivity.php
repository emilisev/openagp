<?php

namespace App\View\Components;

use App\Helpers\LabelProviders;
use App\Helpers\StatisticsComputer;
use App\Models\DiabetesData;
use Ghunti\HighchartsPHP\Highchart;
use App\Helpers\StringToColor;
use Ghunti\HighchartsPHP\HighchartJsExpr;
use function App\Models\readableDateArray;

class Sensitivity extends HighChartsComponent {

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * @var float|int
     */
    private $m_dataStartPoint;

    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        $this->m_dataStartPoint = $this->m_data->getBegin()*DiabetesData::__1SECOND;
        $chart = $this->createChart();
        $this->addCarbsSeries($chart);
        $this->addTreatmentSeries($chart);
        echo '<script type="module">'.$chart->render().'</script>';
    }

    /* * * * * * * * * * * * * * * * * * * * * * PRIVATE METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function addCarbsSeries(Highchart $_chart) {
        $data = $this->m_data->getTreatmentsData()['carbs'];
        if(empty($data)) return;
        $statComputer = new StatisticsComputer();
        $data = $statComputer->computeSum($data, 60 * 60 * 24, $this->m_dataStartPoint);

        $_chart->yAxis[] = [
            'id' => 'carbs-yAxis',
            'min' => 0,
            'opposite' => true,
            'title' => ['text' => __("Glucides (g)"), 'style' => ["fontSize" => "1rem"]]
        ];
        $stringToColor = new StringToColor();
        $dataForChart = [];
        foreach($data as $key => $value) {
            $item = ['x' => $key, 'y' => $value, 'label' => "{$value}g"];
            $dataForChart[] = $item;
        }
        $_chart->series[] = [
            'name' => __("Glucides"),
            'color' => $stringToColor->handle("Glucides"),
            'data' => $dataForChart,
            'yAxis' => 'carbs-yAxis',
            'dataLabels' => ['enabled' => true, 'format' => '{point.label}'],
            'lineWidth' => 2,
        ];
    }

    private function addTreatmentSeries(Highchart $_chart) {
        $insulinData = $this->m_data->getTreatmentsData()['insulin'];
        if(empty($insulinData)) return;
        $dataByDay = [];
        $statComputer = new StatisticsComputer();
        foreach ($insulinData as $type => $datum) {
            if($type == 'basal') {
                $datum = $statComputer->computeBasalSum($datum, 60 * 60 * 24, $this->m_dataStartPoint);
            } else {
                $datum = $statComputer->computeSum($datum, 60 * 60 * 24, $this->m_dataStartPoint);
            }
            foreach($datum as $time => $value) {
                if(array_key_exists($time, $dataByDay)) {
                    $dataByDay[$time] += $value;
                } else {
                    $dataByDay[$time] = $value;
                }
            }
        }
        $_chart->yAxis[] = [
            'id' => 'insulin-yAxis',
            'min' => 0,
            'title' => ['text' => __("Insuline (unités)"), 'style' => ["fontSize" => "1rem"]],
        ];
        $stringToColor = new StringToColor();
        $dataForChart = [];
        foreach($dataByDay as $key => $value) {
            $value = (float)sprintf('%01.2f', $value);
            $item = ['x' => $key, 'y' => $value, 'label' => "{$value}UI"];
            $dataForChart[] = $item;
        }
        $_chart->series[] = [
            'name' => __("Insuline"),
            'color' => $stringToColor->handle("Insuline"),
            'data' => $dataForChart,
            'yAxis' => 'insulin-yAxis',
            'dataLabels' => ['enabled' => true, 'format' => '{point.label}'],
            'lineWidth' => 2,
        ];
    }


    private function createChart(): Highchart {
        $chart = $this->createDefaultChart();
        $xAxis = [
                'labels' => [
                    'format' => '{value:%d/%m}',
                ],
                'tickInterval' => 24 * 60 * 60 * 1000,
            ] + $this->getBottomLabelledXAxis();
        $chart->xAxis = $xAxis;
        return $chart;
    }
}
