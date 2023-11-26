<?php

namespace App\View\Components;

use Ghunti\HighchartsPHP\Highchart;

class AverageGauge extends HighChartsComponent {

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        $chart = $this->createChart();
        $this->addBloodGlucoseSeries($chart);
        echo '<script type="module">'.$chart->render().'</script>';
    }

    /* * * * * * * * * * * * * * * * * * * * * * PRIVATE METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    private function addBloodGlucoseSeries(Highchart $_chart) {
        //prepare data
        $data = $this->m_data->getBloodGlucoseData();
        $avg = round(array_sum($data) / count($data));
        $_chart->series[] = [
            'data' => [$avg],
            'dataLabels' => [
                'enabled' => true, 'inside' => false,
                'style' => ['fontSize' => '1rem', 'textOutline' => 'white', 'color' => 'black', 'zIndex' => 15],
                'y' => 100,
                'format' => "{y} mg/dL",
                'borderWidth' => 0,
            ]
        ];
    }

    private function createChart(): Highchart {
        $chart = $this->createDefaultChart();
        $chart->chart->type = 'gauge';
        $chart->pane = [
            'startAngle' => -90,
            'endAngle' => 89.9,
            'background' => null,
            'size' => '170%',
            'center' => ['50%', '80%']
        ];
        $targets = $this->m_data->getTargets();
        $tickness = 40;
        $chart->yAxis = [
            'min' => 0,
            'max' => 350,
            'tickPositions' => [$targets['low'], $targets['high']],
            'plotBands' => [
                [
                    'from' => '0',
                    'to' => $targets['veryLow'],
                    'color' => config('colors.timeInRange.veryLow'),
                    'thickness' => $tickness
                ], [
                    'from' => $targets['veryLow'],
                    'to' => $targets['low'],
                    'color' => config('colors.timeInRange.low'),
                    'thickness' => $tickness
                ], [
                    'from' => $targets['low'],
                    'to' => $targets['high'],
                    'color' => config('colors.timeInRange.target'),
                    'thickness' => $tickness
                ], [
                    'from' => $targets['high'],
                    'to' => $targets['veryHigh'],
                    'color' => config('colors.timeInRange.high'),
                    'thickness' => $tickness
                ], [
                    'from' => $targets['veryHigh'],
                    'to' => '350',
                    'color' => config('colors.timeInRange.veryHigh'),
                    'thickness' => $tickness
                ]
            ]
        ];
        return $chart;
    }
}
