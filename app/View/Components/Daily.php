<?php

namespace App\View\Components;

use Ghunti\HighchartsPHP\Highchart;
use StringToColor\StringToColor;

class Daily extends HighChartsComponent {

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        $chart = $this->createChart();
        $this->addBloodGlucoseSeries($chart);
        $this->addTreatmentsSeries($chart);
        $this->addCarbsSeries($chart);
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
            'lineWidth' => 2,
            //'marker' => ['enabled' => true, 'radius' => 1,]
        ];
    }

    private function addCarbsSeries(Highchart $_chart) {
        $data = $this->m_data->getTreatmentsData()['carbs'];
        $notes = $this->m_data->getTreatmentsData()['notes'];
        if(empty($data)) {
            return;
        }
        $maxCarbs = max($data);
        $_chart->yAxis[] = [
            'id' => 'carbs-yAxis',
            'visible' => false,
            'max' => $maxCarbs / config('diabetes.treatments.relativeAxisHeight')
        ];

        $dataForChart = [];
        foreach ($data as $key => $value) {
            $item = ['x' => $key, 'y' => $value, 'name' => "{$value}g"];
            if(array_key_exists($key, $notes)) {
                $item['name'] .= ' ('.$notes[$key].')';
            }
            $dataForChart[] = $item;
        }
        foreach ($notes as $key => $value) {
            if(!array_key_exists($key, $data)) {
                $item = ['x' => $key, 'y' => 0, 'name' => $value];
                $dataForChart[] = $item;
            }
        }

        $stringToColor = new StringToColor();
        $_chart->series[] = [
            'type' => 'column',
            'color' => $stringToColor->handle('carbs'),
            'data' => $dataForChart,
            'yAxis' => 'carbs-yAxis',
            'pointRange' => 60 * 60 * 1000, //largeur
            'opacity' => 1,
            'dataLabels' => ['enabled' => true, 'format' => '{point.name}']
        ];
    }

    private function addTreatmentsSeries(Highchart $_chart) {
        $data = $this->m_data->getTreatmentsData()['insulin'];
        if(empty($data)) {
            return;
        }
        $_chart->yAxis[] = ['visible' => false] + $this->getTreatmentYAxis();
        $stringToColor = new StringToColor();
        foreach ($data as $type => $datum) {
            $_chart->series[] = [
                'type' => 'column',
                'color' => $stringToColor->handle($type),
                'data' => $this->formatTimeDataForChart($datum),
                'yAxis' => 'insulin-yAxis',
                'pointRange' => 60 * 60 * 1000, //largeur
                'opacity' => 1,
                'dataLabels' => ['enabled' => true, 'format' => '{y}UI']
            ];
        }
    }

    private function createChart(): Highchart {
        $chart = $this->createDefaultChart();
        //$chart->chart->height = 500;

        $chart->yAxis = [
            $this->getBloodGlucoseYAxis(),
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
