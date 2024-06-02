<?php

namespace App\View\Components;

use App\Models\DiabetesData;
use Ghunti\HighchartsPHP\Highchart;

class DailyTimeInRange extends HighChartsComponent {
    protected int $m_minValTimeInRangeChart = 2;

    protected array $m_timeInRangeLabels =
    ['veryHigh' => 'Très élevée', 'veryLow' => 'Très basse', 'high' => 'Élevée', 'low' => 'Basse', 'target' => 'Dans la plage'];


    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        $chart = $this->createChart();
        $this->addBloodGlucoseSeries($chart);

        return '<script type="module">'.$chart->render().'</script>';
    }

    /* * * * * * * * * * * * * * * * * * * * * * PRIVATE METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    private function addBloodGlucoseSeries(Highchart $_chart) {
        if($this->m_data->getDurationInDays() > 60) {
            $data = $this->m_data->getWeeklyTimeInRangePercent();
            $_chart->chart->height = (count($data['target']) * 20)+100;
            $timeLabelsFormat = '\s\e\m. d M';
        } else {
            $data = $this->m_data->getDailyTimeInRangePercent();
            $timeLabelsFormat = 'D d M';
        }

        $categories = [];
        $mergedData = array_replace($data['target'], $data['other']);
        foreach(array_keys($mergedData) as $time) {
            $categories[] = date($timeLabelsFormat, $time / DiabetesData::__1SECOND);
        }
        $_chart->xAxis = [
            'categories' => $categories,
        ];


        $series = [];
        foreach(array_reverse($data) as $label => $values) {
            $zones = [];
            $dataLabels = ['enabled' => false];
            if($label == 'target') {
                $zones = [
                    [
                        'color' => config('colors.dailyTimeInRange.belowTarget'),
                        'value' => 70
                    ],
                    [
                        'color' => config('colors.dailyTimeInRange.target'),
                        'value' => 100
                    ],
                ];
                $dataLabels = [
                    'enabled' => true,
                    'format' => '{y:.0f} %',
                    'style' => ['fontSize' => '1rem', 'textOutline' => 'none', 'color' => 'rgb(51, 51, 51)', 'fontWeight' => 'initial']
                ];
            }
            $series[] = array(
                'name' => $label,
                'data' => array_values($values),
                'color' => config('colors.dailyTimeInRange.'.$label),
                'enableMouseTracking' => false,
                'zones' => $zones,
                'pointWidth' => 15,
                'dataLabels' => $dataLabels
            );
        }
        $_chart->series = $series;
    }

    private function createChart(): Highchart {
        $chart = $this->createDefaultChart();
        $this->setChartHeightBasedOnDuration($chart);
        $chart->plotOptions->series->stacking = "percent";
        $chart->chart->marginLeft = 90;
        $chart->chart->marginBottom = 40;

        $chart->yAxis = [
            'title' => ['enabled' => false],
            'plotLines' => [
                ['value' => 70, 'width' => 2, 'color' => config('colors.dailyTimeInRange.belowTarget'), 'zIndex' => 10],
            ],
        ];

        return $chart;
    }

}
