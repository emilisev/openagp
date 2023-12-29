<?php

namespace App\View\Components;

use App\Helpers\LabelProviders;
use App\Helpers\StatisticsComputer;
use App\Models\DiabetesData;
use Ghunti\HighchartsPHP\Highchart;
use App\Helpers\StringToColor;

class Treatment extends HighChartsComponent {

    private array $m_bgData;

    private $m_carbsData = [];

    private $m_dataStartPoint = null;
    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    private $m_insulinData = [];

    private string $m_type;

    public function __construct(DiabetesData $data, string $renderTo = null, int $height = null, string $type = 'chart') {
        $this->m_type = $type;
        parent::__construct($data, $renderTo, $height);

        $statComputer = new StatisticsComputer();
        $data = $this->m_data->getBloodGlucoseData();
        $this->m_bgData = $statComputer->computeAverage($data, 60 * 60 * 24);
        $this->m_dataStartPoint = array_key_first($this->m_bgData);
        ksort($this->m_bgData);

        $insulinData = $this->m_data->getTreatmentsData()['insulin'];
        foreach ($insulinData as $type => $datum) {
            $datum = $statComputer->computeSum($datum, 60 * 60 * 24, $this->m_dataStartPoint);
            $this->m_insulinData[array_sum($datum)][$type] = $datum;
        }

        $carbsData = $this->m_data->getAnalyzedCarbs();
        foreach($carbsData as $type => $datum) {
            $this->m_carbsData[$type] = $statComputer->computeSum($datum, 60 * 60 * 24, $this->m_dataStartPoint);
        }


    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        if($this->m_type == 'chart') {
            return $this->renderChart();
        } elseif($this->m_type == 'squares') {
            return $this->renderSquares();
        }
    }


    public function renderChart() {
        $chart = $this->createChart();
        $this->addBloodGlucoseSeries($chart);
        $this->addTreatmentsSeries($chart);
        $this->addCarbsSerie($chart);
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
            'name' => "Glycémie",
            'data' => $this->formatTimeDataForChart($this->m_bgData),
            'zones' => $this->getDefaultZones(),
            'lineWidth' => 2,
            //'marker' => ['enabled' => true, 'radius' => 1,],
            'zIndex' => 100
        ];
    }

    private function addCarbsSerie(Highchart $_chart) {
        if(empty($this->m_carbsData)) {
            return;
        }
        $stringToColor = new StringToColor();
        foreach($this->m_carbsData as $type => $datum) {
            if(empty($datum)) continue;
            $_chart->series[] = [
                'type' => 'column',
                'name' => LabelProviders::get($type),
                'stacking' => 'normal',
                'color' => $stringToColor->handle($type),
                'data' => $this->formatTimeDataForChart($datum),
                'yAxis' => 'insulin-yAxis',
                'stack' => 'carbs',
            ];
        }
        /*$_chart->yAxis[] = [
            'id' => 'carbs-yAxis',
            'visible' => false,
        ];*/
    }

    private function addTreatmentsSeries(Highchart $_chart) {
        if(empty($this->m_insulinData)) {
            return;
        }
        $stringToColor = new StringToColor();

        //place insulin type with less quantity at bottom
        krsort($this->m_insulinData);
        foreach ($this->m_insulinData as $insulinDatum) {
            foreach($insulinDatum as $type => $datum) {
                $_chart->series[] = [
                    'type' => 'column',
                    'name' => $type,
                    'stacking' => 'normal',
                    'color' => $stringToColor->handle($type),
                    'data' => $this->formatTimeDataForChart($datum),
                    'yAxis' => 'insulin-yAxis',
                    'stack' => 'insulin'
                ];
            }
        }
        $_chart->yAxis[] = ['id' => 'insulin-yAxis', 'visible' => false, /*'type' => 'logarithmic'*/];
    }

    private function createChart(): Highchart {
        $chart = $this->createDefaultChart();
        $chart->chart->marginBottom = 30;
        $chart->chart->marginRight = 50;

        $bloodGlucoseYAxis = $this->getBloodGlucoseYAxis();
        /*$avgBG = array_sum($this->m_bgData) / count($this->m_bgData);
        $bloodGlucoseYAxis['plotLines'][] = ['value' => last($this->m_bgData), 'width' => 0, 'zIndex' => 1000, 'label' =>
            ['align' => 'right', 'x' => 25, 'text' => 'Moy. gly.<br/>'.round($avgBG).'<br/>mg/dL']];*/

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

    private function renderSquares() {
        $stringToColor = new StringToColor();
        foreach ($this->m_insulinData as $insulinDatum) {
            foreach($insulinDatum as $type => $datum) {
                if(empty($datum)) {
                    continue;
                }
                echo view(
                    'cards.square',
                    [
                        'color' => $stringToColor->handle($type),
                        'value' => round((array_sum($datum)/count($datum))*10)/10,
                        'unit' => 'UI/j',
                        'label' => __("Moy. ").$type,
                    ]);

            }
        }

        $carbsData = $this->m_data->getAnalyzedCarbs();
        foreach($this->m_carbsData as $type => $datum) {
            if(!empty($datum)) {
                echo view(
                    'cards.square',
                    [
                        'color' => $stringToColor->handle($type),
                        'value' => round((array_sum($datum) / count($datum)) * 10) / 10,
                        'unit' => 'g/j',
                        'label' => __("Moy. ").LabelProviders::get($type),
                    ]);
            }
        }
    }

}
