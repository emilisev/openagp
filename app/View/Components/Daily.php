<?php

namespace App\View\Components;

use App\Helpers\LabelProviders;
use Ghunti\HighchartsPHP\Highchart;
use App\Helpers\StringToColor;

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
        echo '<script type="module">Highcharts.AST.allowedAttributes.push(\'onclick\');'.$chart->render().'</script>';
    }

    /* * * * * * * * * * * * * * * * * * * * * * PRIVATE METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    private function addBloodGlucoseSeries(Highchart $_chart) {
        $data = $this->m_data->getBloodGlucoseData();
        ksort($data);
        //prepare data
        $_chart->series[] = [
            'type' => 'line',
            'name' => 'Glycémie',
            'data' => $this->formatTimeDataForChart($data),
            'zones' => $this->getDefaultZones(),
            'lineWidth' => 2,
            //'marker' => ['enabled' => true, 'radius' => 1,]
        ];
    }

    private function addCarbsSeries(Highchart $_chart) {
        $data = $this->m_data->getAnalyzedCarbs();
        $maxCarbs = 0;
        foreach($data as $dataSet) {
            if(!empty($dataSet)) {
                $maxCarbs = max($maxCarbs, max($dataSet));
            }
        }
        if($maxCarbs == 0) {
            return;
        }
        $notes = $this->m_data->getTreatmentsData()['notes'];
        $_chart->yAxis[] = [
            'id' => 'carbs-yAxis',
            'visible' => false,
            'max' => $maxCarbs / config('diabetes.treatments.relativeAxisHeight')
        ];

        $stringToColor = new StringToColor();
        foreach($data as $type => $datum) {
            if(empty($datum)) continue;
            $dataForChart = [];
            foreach($datum as $key => $value) {
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
            $_chart->series[] = [
                'type' => 'column',
                'name' => LabelProviders::get($type),
                'color' => $stringToColor->handle($type),
                'data' => $dataForChart,
                'yAxis' => 'carbs-yAxis',
                'pointRange' => 60 * 60 * 1000, //largeur
                'opacity' => 1,
                'dataLabels' => ['enabled' => true, 'format' => '{point.name}']
            ];
        }
    }

    private function addTreatmentsSeries(Highchart $_chart) {
        $data = $this->m_data->getTreatmentsData()['insulin'];
        if(empty($data)) {
            return;
        }
        $_chart->yAxis[] = ['visible' => false] + $this->getTreatmentYAxis();
        $stringToColor = new StringToColor();
        $jsSetNullTreatment = "Highcharts.setNullTreatment('{point.identifier}');";

        foreach ($data as $type => $datum) {
            if(empty($datum)) continue;
            $serieType = 'column';
            $basal = false;

            $dataId = @$this->m_data->getTreatmentsData()['insulinId'][$type];
            $insulinDuration = @$this->m_data->getTreatmentsData()['insulinDuration'][$type];
            if($this->m_data->hasBasalTreatment() && $type == 'Temp Basal') {
                if(!empty($insulinDuration)) {
                    $serieType = 'variwide';
                }
                $basal = true;
            }
            $_chart->series[] = [
                'name' => $type,
                'type' => $serieType,
                'color' => $stringToColor->handle($type),
                'data' => $this->formatTimeDataForChart($datum, $insulinDuration??$dataId),
                'keys' => ['x','y','identifier'],
                'yAxis' => 'insulin-yAxis',
                'borderColor' => $basal?$stringToColor->handle($type):'white',
                'borderRadius' => $basal?0:3,
                'pointRange' => 60 * 60 * 1000, //largeur
                'dataLabels' => ['enabled' => !$basal, 'format' => '{y}UI'],
                'zIndex' => $basal?1:2,
                'tooltip' => [
                    'useHTML' => true,
                    'pointFormat' => '<span style="color:{color}">●</span> '.
                    '{series.name}: <b>{point.y}</b><br/><a href="#" onclick="'.$jsSetNullTreatment.'">Supprimer</a><br/>',
                    'stickOnContact' => true
                ],
            ];
        }
    }

    private function createChart(): Highchart {
        $chart = $this->createDefaultChart();
        $chart->chart->zoomType = 'x';
        $chart->tooltip = [
            'stickOnContact' => true
        ];
        $chart->yAxis = [
            $this->getBloodGlucoseYAxis($this->m_height<200?0:null),
        ];
        $xAxis = ['showLastLabel' => false] + $this->getBottomLabelledXAxis();
        $xMin = $this->m_data->getBegin();
        $xMax = $xMin + 60 * 60 * 24;
        $xAxis['min'] = $xMin * 1000;
        $xAxis['max'] = $xMax * 1000;
        $chart->xAxis = $xAxis;
        return $chart;
    }
}
