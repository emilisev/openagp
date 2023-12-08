<?php

namespace App\View\Components;

use App\Http\Controllers\AgpController;
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
        $data = $this->m_data->getDailyTimeInRangePercent();
        $series = [];
        foreach(array_reverse($data) as $label => $values) {
            $zones = [];
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
            }

            $series[] = array(
                'name' => $label,
                'data' => array_values($values),
                'color' => config('colors.dailyTimeInRange.'.$label),
                'enableMouseTracking' => false,
                'zones' => $zones,
                'pointWidth' => 15
            );
        }
        $_chart->series = $series;
    }

    private function createChart(): Highchart {
        $chart = $this->createDefaultChart();
        $chart->chart->type = "bar";
        $chart->plotOptions->series->stacking = "percent";

        $categories = [];
        $data = $this->m_data->getDailyTimeInRange();
        $mergedData = array_replace($data['target'], $data['other']);
        foreach(array_keys($mergedData) as $time) {
            $categories[] = date('D d M', $time / DiabetesData::__1SECOND);
        }
        $chart->xAxis = [
            'categories' => $categories,
        ];
        $chart->yAxis = [
            'plotLines' => [
                ['value' => 70, 'width' => 2, 'color' => config('colors.dailyTimeInRange.belowTarget'), 'zIndex' => 10],
            ],
        ];

        $chart->chart->height = count($categories) * 20;

        return $chart;
    }

}
