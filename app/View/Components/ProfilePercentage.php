<?php

namespace App\View\Components;

use App\Helpers\ParserHelper;
use App\Models\DiabetesData;
use Ghunti\HighchartsPHP\Highchart;

class ProfilePercentage extends HighChartsComponent {

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    protected $m_incrementsInSeconds = 15 * 60;

    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        $chart = $this->createChart();
        $this->addBloodGlucoseSeries($chart);
        $this->addProfilesHeatMap($chart);
        echo '<script type="module">'.$chart->render().'</script>';
    }

    /**
     * @param Highchart $_chart
     * @return void
     */
    private function addBloodGlucoseSeries(Highchart $_chart): void {
        $targets = $this->m_data->getTargets();
        //prepareData
        $this->m_data->setAgpStep($this->m_incrementsInSeconds / 60);
        $data = $this->m_data->getAgpData();
        $bgSerie = [];
        foreach (array_keys($data[50]) as $index) {
            $bgSerie[] = [ParserHelper::getTodayTimestampFromTimeInMicroSeconds($index), $data[50][$index]];
        }
        //set serie
        $_chart->series[] = [
            'type' => 'spline',
            'data' => $bgSerie,
            'name' => 'Glycémie moyenne',
            'lineWidth' => 3,
            'colorAxis' => false,
            'color' => '#00b657',
            'zones' => [
                [
                    'color' => '#ffad00',
                    'value' => $targets['low']
                ],
                [
                    'color' => '#00b657',
                    'value' => $targets['high']
                ],
                [
                    'color' => '#ffad00'
                ]
            ]
        ];
    }

    private function addProfilesHeatMap(Highchart $_chart) {
        $data = $this->getProfilesForAverageView();
        $dataForChart = [];
        foreach($data as $key => $value) {
            $dataForChart[]= [ParserHelper::getTodayTimestampFromTimeInMicroSeconds($key*DiabetesData::__1SECOND), $value];
        }
        $_chart->series[] = [
            'type' => 'column',
            'data' => $dataForChart,
            'name' => __('Pourcentage moyen du profil'),
            'borderWidth' => 0,
            'pointPadding' => -0.3,
            'pointRange' => $this->m_incrementsInSeconds *DiabetesData::__1SECOND,
            'yAxis' => 1,
            'zIndex' => -1
        ];
        $_chart->colorAxis = [[
            'stops' => [
                [0, config('colors.profileGraph.weak')],
                [0.5, '#ffffff'],
                [1, config('colors.profileGraph.strong')]
            ],
            'min' => 80,
            'max' => 120,
        ]];
    }

    private function createChart(): Highchart {
        $chart = $this->createDefaultChart();
        $chart->chart->marginRight = 40;
        $xAxis = $this->getBottomLabelledXAxis();
        $xAxis['min'] = strtotime('midnight') * 1000;
        $xAxis['max'] = (strtotime('midnight') + 60 * 60 *24 )* 1000;
        $chart->xAxis = $xAxis;

        //percentTicks
        $bloodGlucoseYAxis = $this->getBloodGlucoseYAxis();
        $chart->yAxis = [$bloodGlucoseYAxis, ['min' => 0, 'max' => 1, 'visible' => false]];
        return $chart;
    }

    private function getProfilesForAverageView() {
        $profiles = $this->m_data->getProfiles();
        $simpleProfiles = [];
        foreach($profiles as $key => $value) {
            if($key < $this->m_data->getBegin() * DiabetesData::__1SECOND) {
                $key = $this->m_data->getBegin() * DiabetesData::__1SECOND;
            }
            $profileString = @$value['profile'] ?? $value['notes'];
            preg_match('/[^ ](\(([0-9]+)%\))$/', $profileString, $matches);
            if(!empty($matches)) {
                $profilePercent = $matches[2];
            } else {
                $profilePercent = 100;
            }
            $simpleProfiles[$key] = $profilePercent;
        }
        $profilePercentageByStep = [];
        $profilesKeys = array_keys($simpleProfiles);
        $j = 0;
        foreach($profilesKeys as $keyIndex => $key) {
            $j ++;
            //var_dump(readableDate($key), readableDate($profilesKeys[$keyIndex +1]), $profilePercent);
            for($i = $key;
                array_key_exists($keyIndex +1, $profilesKeys) && $i <= $profilesKeys[$keyIndex +1] - $this->m_incrementsInSeconds*DiabetesData::__1SECOND;
                $i += $this->m_incrementsInSeconds*DiabetesData::__1SECOND) {
                $date = $i / DiabetesData::__1SECOND;
                $timeInDay = (date('H', $date) * 60 * 60) + (date('i', $date) * 60) + date('s', $date);
                $step = floor($timeInDay / $this->m_incrementsInSeconds) * $this->m_incrementsInSeconds;
                //var_dump($step);
                $profilePercentageByStep[$step][] = $simpleProfiles[$key];
            }
            //var_dump($profilePercentageByStep);
            //if($j > 10) die();
        }
        foreach($profilePercentageByStep as $step => &$value) {
            $value = round(array_sum($value) / count($value));
        }
        return $profilePercentageByStep;
    }
}
