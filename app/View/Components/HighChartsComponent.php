<?php

namespace App\View\Components;

use App\Models\DiabetesData;
use Ghunti\HighchartsPHP\Highchart;
use Illuminate\View\Component;
use function App\Models\readableTime;

abstract class HighChartsComponent extends Component {
    protected DiabetesData $m_data;

    protected ?string $m_renderTo;
    protected ?int $m_height;
    private ?int $m_width;

    public function __construct(DiabetesData $data, string $renderTo = null, int $height = null, int $width = null) {
        $this->m_data = $data;
        $this->m_renderTo = $renderTo;
        $this->m_height = $height;
        $this->m_width = $width;
    }

    protected function createDefaultChart(): Highchart {
        $chart = new Highchart();
        $chart->chart = [
            'renderTo' => $this->m_renderTo,
        ];
        if(is_int($this->m_height)) {
            $chart->chart->height = $this->m_height;
        }
        if(is_int($this->m_width)) {
            $chart->chart->width = $this->m_width;
        }
        $chart->plotOptions->series = ['marker' => ['enabled' => false], 'states' => ['inactive' => ['opacity' => 1]]];
        //$chart->tooltip->enabled = false;
        $chart->tooltip = ['shared' => true];
        $chart->title->text = null;
        $chart->legend = ['enabled' => false];
        $chart->time->timezoneOffset = -60;
        return $chart;
    }

    protected function getDefaultZones() {
        $targets = $this->m_data->getTargets();
        return [
            [
                'className' => 'outer-veryLow',
                'color' => config('colors.timeInRange.veryLow'),
                'value' => $targets['veryLow']
            ],
            [
                'className' => 'outer-low',
                'color' => config('colors.timeInRange.low'),
                'value' => $targets['low']
            ],
            [
                'className' => 'outer-inrange',
                'color' => config('colors.timeInRange.target'),
                'value' => $targets['high']
            ],
            [
                'className' => 'outer-inrange',
                'color' => config('colors.timeInRange.high'),
                'value' => $targets['veryHigh']
            ],
            [
                'className' => 'outer-high',
                'color' => config('colors.timeInRange.veryHigh'),
            ],
        ];
    }

    protected function getBloodGlucoseYAxis($_greenLineWidth = 2) {
        $targets = $this->m_data->getTargets();
        $yMax = max($this->m_data->getBloodGlucoseData());
        $tickPositions = [0, $targets['veryLow'], $targets['low'], $targets['high'], $targets['veryHigh']];
        if($yMax > $targets['veryHigh'] - 10) {
            $tickPositions[] = 350;
        }
        return [
            'title' => ['text' => 'mg/dL', 'rotation' => -90, 'offset' => 15, 'align' => 'high', 'y' => 25, 'style' => ['fontSize' => '0.8rem']],
            'id' => 'gloodGlucose-yAxis',
            'tickPositions' => $tickPositions,
            'offset' => -10,
            'showFirstLabel' => false,
            'showLastLabel' => false,
            'plotLines' => [
                ['value' => 0, 'width' => 1, 'color' => '#777777'],
                ['value' => 350, 'width' => 1, 'color' => '#777777'],
                ['value' => $targets['veryHigh'], 'width' => 1, 'color' => '#777777'],
                ['value' => $targets['veryLow'], 'width' => 1, 'color' => '#777777'],
                ['value' => $targets['low'], 'width' => $_greenLineWidth, 'color' => config('colors.timeInRange.target'), 'zIndex' => 5],
                ['value' => $targets['high'], 'width' => $_greenLineWidth, 'color' => config('colors.timeInRange.target'), 'zIndex' => 5],
            ]
        ];

    }
    protected function getTreatmentYAxis() {
        $treatments = $this->m_data->getTreatmentsData()['insulin'];
        $maxInsulin = 0;
        foreach ($treatments as $insulinDatum) {
            foreach ($insulinDatum as $value) {
                $maxInsulin = max($maxInsulin, $value);
            }
        }
        $tickPositions = [0];
        $maxInsulin = ceil($maxInsulin);
        if($maxInsulin <= 3) {
            $tickPositions[] = $maxInsulin / 2;
        } elseif($maxInsulin < 10 && fmod($maxInsulin, 2) > 0) {
            $maxInsulin--;
            $tickPositions[] = $maxInsulin / 2;
        } elseif($maxInsulin > 10) {
            $maxInsulin = (ceil($maxInsulin /10)) * 10;
            for($i = 5; $i < $maxInsulin; $i +=5) {
                $tickPositions[] = $i;
            }
        }
        $tickPositions[] = $maxInsulin;
        $tickPositions[] = $maxInsulin / config('diabetes.treatments.relativeAxisHeight');
        return [
            'title' => ['text' => 'UI', 'rotation' => 0, 'offset' => 10, 'align' => 'high', 'y' => 25, 'style' => ['fontSize' => '0.8rem']],
            'opposite' => true,
            'id' => 'insulin-yAxis',
            //'visible' => false,
            'tickPositions' => $tickPositions,
            'showLastLabel' => false,
            'zIndex' => 150,
        ];
    }

    protected function getBottomLabelledXAxis() {
        return [
            'type' => 'datetime',
            'labels' => [
                'format' => '{value:%H:%M}',
                'distance' => 5
            ],
            'gridLineWidth' => 2,
            'tickInterval' => 3 * 60 * 60 * 1000,
            'tickWidth' => 1,
            'tickPosition' => 'inside',
        ];
    }

    protected function formatTimeDataForChart($_data, $_width = null) {
        $dataForChart = [];
        foreach ($_data as $key => $value) {
            if(!empty($_width) && array_key_exists($key, $_width) && !empty($_width[$key])) {
                $dataForChart[] = [$key, $value, $_width[$key]];
            } else {
                $dataForChart[] = [$key, $value];
            }
        }
        return $dataForChart;
    }

    protected function setChartHeightBasedOnDuration($_chart) {
        $_chart->chart->height = ($this->m_data->getDurationInDays() * 20)+100;
        $_chart->chart->type = "bar";
    }
}
