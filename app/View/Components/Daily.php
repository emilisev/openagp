<?php

namespace App\View\Components;

use Ghunti\HighchartsPHP\Highchart;

class Daily extends HighChartsComponent {

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        $chart = $this->createChart();
        $this->addBloodGlucoseSeries($chart);
        $this->addTreatmentsSeries($chart);
        echo '<script type="module">'.$chart->render().'</script>';
    }

    /* * * * * * * * * * * * * * * * * * * * * * PRIVATE METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    private function addBloodGlucoseSeries(Highchart $_chart) {
        $data = $this->m_data->getBloodGlucoseData();
        ksort($data);

        //prepare data
        $_chart->series[] = [
            'type' => 'line',
            'data' => $this->formatTimeDataForChart($data),
            'zones' => $this->getDefaultZones(),
            'lineWidth' => 2
        ];
    }

    private function addTreatmentsSeries(Highchart $_chart) {
        $treatments = $this->m_data->getTreatmentsData();
        foreach ($treatments as $insulinDatum) {
            $serie = [];
            foreach ($insulinDatum as $time => $value) {
                $serie[] = [$time, $value];
            }
            $_chart->series[] = [
                'type' => 'column',
                'data' => $serie,
                'yAxis' => 'insulin-yAxis',
                'pointRange' => 60 * 60 * 1000, //largeur
                'opacity' => 1,
                'dataLabels' => ['enabled' => true]
            ];
        }
    }

    private function createChart(): Highchart {
        $chart = $this->createDefaultChart();
        //$chart->chart->height = 500;
        $chart->yAxis = [
            $this->getBloodGlucoseYAxis(),
            $this->getTreatmentYAxis()
        ];
        $xAxis = $this->getBottomLabelledXAxis();
        $min = $this->m_data->getBegin();
        $max = $min + 60 * 60 * 24;
        $xAxis['min'] = $min * 1000;
        $xAxis['max'] = $max * 1000;
        $chart->xAxis = $xAxis;
        return $chart;
    }
}
