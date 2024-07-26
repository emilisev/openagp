<?php

namespace App\View\Components;

use App\Models\DiabetesData;
use Ghunti\HighchartsPHP\Highchart;

class Battery extends HighChartsComponent {

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
        $this->addBatterySerie($chart);
        echo '<script type="module">'.$chart->render().'</script>';
    }

    /* * * * * * * * * * * * * * * * * * * * * * PRIVATE METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function addBatterySerie(Highchart $_chart) {
        $data = $this->m_data->getBatteryInfo();
        ksort($data);
        //prepare data
        $_chart->series[] = [
            'type' => 'line',
            'name' => __('Batterie'),
            'data' => $this->formatTimeDataForChart($data),
            'lineWidth' => 1,
            'color' => config('colors.iob.iob'),
        ];
    }

    private function createChart(): Highchart {
        $chart = $this->createDefaultChart();

        $xAxis = ['showLastLabel' => false] + $this->getBottomLabelledXAxis();
        $xMin = $this->m_data->getBegin();
        $xMax = $xMin + 60 * 60 * 24;
        $xAxis['min'] = $xMin * 1000;
        $xAxis['max'] = $xMax * 1000;
        $chart->xAxis = $xAxis;
        $chart->yAxis = ['min' => 0, 'max' => 100];
        return $chart;
    }

    /**
     * @param $_from
     * @param $_to
     * @param $_percent
     * @return array
     */
    private function getProfilePercentBackground($_from, $_to, $_percent): array {
        if($_percent < 100) {
            $color = config('colors.profile.weak');
            $borderColor = config('colors.profile.weakBorder');
        } elseif($_percent > 100) {
            $color = config('colors.profile.strong');
            $borderColor = config('colors.profile.strongBorder');
        } else {
            return [];
        }

        return ['from' => $_from,
            'to' => $_to,
            'label' => ['text' => ($_percent != 100 ? $_percent.'%' : null)],
            'color' => $color,
            'borderWidth' => 1,
            'borderColor' => $borderColor,
            'zIndex' => -5];
    }

    private function getProfilesForDailyView() {
        $profiles = $this->m_data->getProfiles();
        $keysToRemove = [];
        $simpleProfiles = [];
        foreach($profiles as $key => $value) {
            if($key < $this->m_data->getBegin() * DiabetesData::__1SECOND) {
                $key = $this->m_data->getBegin() * DiabetesData::__1SECOND;
            }
            $profileString = @$value['profile'] ?? $value['notes'];
            preg_match('/[^ ](\(([0-9]+)%\))$/', $profileString, $matches);
            if(!empty($matches)) {
                $profileName = str_replace($matches[1], '', $profileString);
                $profilePercent = $matches[2];
            } else {
                $profileName = $profileString;
                $profilePercent = 100;
            }
            $simpleProfiles[$key] = ['fullText' => $profileString, 'name' => $profileName, 'percent' => $profilePercent];
        }
        foreach($simpleProfiles as $key => $value) {
            if(isset($previousKey) && $simpleProfiles[$previousKey] == $value) {
                $keysToRemove[] = $key;
            }
            $previousKey = $key;
        }
        $result = array_filter(
            $simpleProfiles,
            function($_key) use($keysToRemove) {
                return !in_array($_key, $keysToRemove);
            },
            ARRAY_FILTER_USE_KEY);
        return $result;
    }
}
