<?php

namespace App\View\Components;

use App\Helpers\LabelProviders;
use App\Models\DiabetesData;
use Ghunti\HighchartsPHP\Highchart;
use App\Helpers\StringToColor;

class Daily extends HighChartsComponent {

    private $m_compressedView;

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    public function __construct(DiabetesData $data, string $renderTo = null, int $height = null) {
        parent::__construct($data, $renderTo, $height);
        $this->m_compressedView = !empty($this->m_height) && $this->m_height < 200;
    }


    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        $chart = $this->createChart();
        $this->addBloodGlucoseSeries($chart);
        $this->addTreatmentsSeries($chart);
        $this->addCarbsSeries($chart);
        $this->addNotes($chart);
        $this->addIOBSerie($chart);
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
            'zIndex' => 8
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
        $_chart->yAxis[] = [
            'id' => 'carbs-yAxis',
            'visible' => false,
            'max' => $maxCarbs / config('diabetes.treatments.relativeAxisHeight')
        ];
        $stringToColor = new StringToColor();
        $serieIndex = 0;
        foreach($data as $type => $datum) {
            if(empty($datum)) continue;
            $dataForChart = [];
            foreach($datum as $key => $value) {
                $item = ['x' => $key, 'y' => $value, 'label' => "{$value}g"];
                $dataForChart[] = $item;
            }
            $serieIndex ++;
            $_chart->series[] = [
                'type' => 'column',
                'name' => LabelProviders::get($type),
                'color' => $stringToColor->handle($type),
                'data' => $dataForChart,
                'yAxis' => 'carbs-yAxis',
                'pointRange' => 60 * 60 * 1000, //largeur
                'opacity' => 1,
                'zIndex' => 12,
                'dataLabels' => ['enabled' => true, 'format' => '{point.label}'],
                /*'tooltip' => [
                    'useHTML' => true,
                    'pointFormat' => '<span style="color:{color}">●</span> '.
                        '{series.name}: <b>{point.label}</b><br/>',
                ]*/
            ];
        }
    }

    private function addIOBSerie(Highchart $_chart) {
        $data = $this->m_data->getTreatmentsData();
        if(!array_key_exists('iob', $data)|| empty($data['iob'])) {
            return;
        }
        ksort($data['iob']);
        //prepare data
        $_chart->series[] = [
            'type' => 'line',
            'data' => $this->formatTimeDataForChart($data['iob']),
            'yAxis' => 'iob-yAxis',
            'lineWidth' => 1,
            'color' => config('colors.iob'),
            'zIndex' => 7,
            'enableMouseTracking'=> false
        ];
        $_chart->yAxis[] = [
            'id' => 'iob-yAxis', 'visible' => false
        ];
    }

    private function addNotes(Highchart $_chart) {
        $notes = $this->m_data->getTreatmentsData()['notes'];
        $bg = $this->m_data->getBloodGlucoseData();
        $annotations = [];
        foreach($notes as $key => $value) {
            $bgAtKey = 150;
            foreach($bg as $bgKey => $currentBg) {
                if(isset($previousKey) && $key > $previousKey && $key < $bgKey) {
                    $bgAtKey = $currentBg;
                    break;
                }
                $previousKey = $bgKey;
            }
            $annotations[] = ['point'=>['x' => $key, 'y' => $bgAtKey+20, 'xAxis' => 0, 'yAxis' => 0], 'text' => $value];
        }
        $_chart->annotations = [['labels' => $annotations,
            'labelOptions' => ['backgroundColor' => '#e4e4e4', 'borderColor' => '#a2a2a2']]
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
            if(empty($datum)) continue;
            $serieType = 'column';
            $basal = false;
            $step = false;

            $dataId = @$this->m_data->getTreatmentsData()['insulinId'][$type];
            $insulinDuration = @$this->m_data->getTreatmentsData()['insulinDuration'][$type];
            if($this->m_data->hasBasalTreatment() && strpos(strtolower($type), 'basal') !== false) {
                if(!empty($insulinDuration)) {
                    $serieType = 'variwide';
                } else {
                    $serieType = 'line';
                    $step = 'left';
                }
                $basal = true;
            }
            if($this->m_compressedView) {
                $tooltip = [];
            } else {
                $tooltip = [
                    'useHTML' => true,
                    'pointFormat' => '<span style="color:{color}">●</span> '.
                        '{series.name}: <b>{point.y}</b><br/><a href="#" onclick="Highcharts.setNullTreatment(\'{point.identifier}\');">Supprimer</a><br/>',
                    'stickOnContact' => true
                ];
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
                'zIndex' => $basal?1:9,
                //'tooltip' => $tooltip,
                'step' => $step,
            ];

        }
    }

    private function createChart(): Highchart {
        $chart = $this->createDefaultChart();
        $chart->chart->zoomType = 'x';
        $chart->tooltip = ['shared' => true
        ];

        $chart->yAxis = [
            $this->getBloodGlucoseYAxis($this->m_compressedView ?0:null),
        ];
        $xAxis = ['showLastLabel' => false] + $this->getBottomLabelledXAxis();
        $xMin = $this->m_data->getBegin();
        $xMax = $xMin + 60 * 60 * 24;
        $xAxis['min'] = $xMin * 1000;
        $xAxis['max'] = $xMax * 1000;
        $profiles = $this->m_data->getSimpleProfiles();
        foreach($profiles as $key => $value) {
            if($key < $this->m_data->getBegin() * DiabetesData::__1SECOND) {
                $key = $this->m_data->getBegin() * DiabetesData::__1SECOND;
            }
            preg_match('/^([^\(]*)(\(([0-9]+)%\))?$/', @$value['profile']??$value['notes'], $matches);
            $profileName = $matches[1];
            $profilePercent = @$matches[3]??100;
            if(!isset($previousProfile) || $previousProfile != $profileName) {
                $xAxis['plotLines'][] = ['value' => $key,
                    'label' => ['text' => @$value['profile'] ?? $value['notes']],
                    'zIndex' => -1];
            } elseif($previousPercent != 100) {
                if($previousPercent < 100) {
                    $color = config('colors.profile.weak');
                } elseif($previousPercent > 100) {
                    $color = config('colors.profile.strong');
                }
                $xAxis['plotBands'][] = ['from' => $previousKey, 'to' => $key,
                    'label' => ['text' => ($previousPercent!= 100?$previousPercent.'%':null)],
                    'color' => $color,
                    'zIndex' => -5];
            }
            $previousProfile = $profileName;
            $previousPercent = $profilePercent;
            $previousKey = $key;
        }
        $chart->xAxis = $xAxis;
        return $chart;
    }
}
