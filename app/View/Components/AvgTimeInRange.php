<?php

namespace App\View\Components;

use Ghunti\HighchartsPHP\Highchart;

class AvgTimeInRange extends HighChartsComponent {
    protected int $m_minValTimeInRangeChart = 2;

    protected array $m_timeInRangeLabels =
        ['veryHigh' => 'Très élevée',
            'veryLow' => 'Très basse',
            'high' => 'Élevée',
            'low' => 'Basse',
            'target' => 'Dans la plage'];

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
        $data = $this->m_data->getTimeInRangePercent();
        //var_dump($data);
        foreach($data as &$value) {
            if($value < $this->m_minValTimeInRangeChart) {
                $data['target'] -= ($this->m_minValTimeInRangeChart - $value);
                $value = $this->m_minValTimeInRangeChart;
            }
        }
        unset($value);
        foreach($data as $label => $value) {
            $_chart->series[] = array(
                'name' => $this->m_timeInRangeLabels[$label],
                'data' => [$value],
                'color' => config('colors.timeInRange.'.$label),
                'enableMouseTracking' => false,
                'pointWidth' => 40,
            );
        }
    }

    private function createChart(): Highchart {
        $chart = $this->createDefaultChart();
        $chart->chart->type = "column";
        $chart->chart->showAxes = false;
        $chart->chart->marginLeft = 0;
        $chart->chart->marginBottom = 0;
        $chart->chart->marginTop = 0;
        $chart->chart->marginRight = 0;
        $chart->chart->height = 100;
        //$chart->chart->width = 40;
        $chart->yAxis->max = 100;
        $chart->yAxis->visible = false;
        $chart->xAxis->visible = false;

        /*$chart->responsive = [
            'rules' => [
                [
                    'condition' => [
                        'maxWidth' => 500
                    ],
                    'chartOptions' => [
                        'height' => 10
                    ]
                ]
            ]
        ];*/

        $chart->plotOptions->series->stacking = "normal";

        return $chart;
    }

}
