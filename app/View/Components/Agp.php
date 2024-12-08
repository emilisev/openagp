<?php

namespace App\View\Components;

use App\Helpers\ParserHelper;
use App\Models\DiabetesData;
use Ghunti\HighchartsPHP\Highchart;
use function App\Models\readableTime;

class Agp extends HighChartsComponent {

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        $chart = $this->createChart();
        $this->addBloodGlucoseSeries($chart);
        //$this->addStackedGradiantTreatmentSeries($chart);
        echo '<script type="module">'.$chart->render().'</script>';
    }

    /**
     * @param Highchart $_chart
     * @return void
     * @deprecated
     */
    private function addBoxPlotTreatmentSeries(Highchart $_chart): void {
    //add insulin data
        //echo "<pre>";
        $insulinData = $this->m_data->getInsulinAgpData();
        foreach ($insulinData as $insulinDatum) {
            $serie = [];

            /*echo '<pre>';
            var_dump($insulinDatum);
            foreach($insulinDatum as $time => $values) {
                $serie[] = ['x' => getTodayTimestampFromTimeInSeconds($time), 'y' => $values['avg'], 'name' => $values['frequency']];
            }
            $chart->series[] = [
                'type' => 'column',
                'data' => $serie,
                'yAxis' => 1,
            ];*/
            //var_dump($insulinType, $insulinDatum);
            foreach ($insulinDatum as $time => $values) {
                sort($values['treatments']);
                $min = min($values['treatments']);
                $v25 = $values['treatments'][max(0, floor(count($values['treatments']) * 0.25) - 1)];
                $v50 = $values['treatments'][max(0, floor(count($values['treatments']) * 0.50) - 1)];
                $v75 = $values['treatments'][max(0, floor(count($values['treatments']) * 0.75) - 1)];
                $max = max($values['treatments']);
                /*if($min == $v25 || $v75 == $max) {
                    $v25 = $min;
                    $v75 = $max;
                }*/
                $serie[] = [ParserHelper::getTodayTimestampFromTimeInMicroSeconds($time),
                    $min,
                    $v25,
                    $v50,
                    $v75,
                    $max];
            }
            //var_dump($serie);
            $_chart->series[] = [
                'type' => 'boxplot',
                'data' => $serie,
                'yAxis' => 1,
                'pointRange' => 60 * 60 * 1000, //largeur
                'opacity' => 1,
                'dataLabels' => ['enabled' => true]
            ];
        }
    }

    /**
     * @param Highchart $_chart
     * @return void
     */
    private function addStackedGradiantTreatmentSeries(Highchart $_chart): void {
        //add insulin data
        //echo "<pre>";
        $colors = [];
        $colors[0] = ['min' => 'navajowhite', 'v25' => 'lightsalmon', 'v50' => 'orangered', 'v75' => 'lightsalmon', 'max' => 'navajowhite'];
        $colors[1] = ['min' => 'paleturquoise', 'v25' => 'skyblue', 'v50' => 'deepskyblue', 'v75' => 'skyblue', 'max' => 'paleturquoise'];

        $insulinData = $this->m_data->getInsulinAgpData();
        $colorScheme = 0;
        foreach ($insulinData as $insulinDatum) {
            $serie = [];
            $maxCount = 0;
            foreach ($insulinDatum as $values) {
                $maxCount = max($maxCount, count($values['treatments']));
            }

            foreach ($insulinDatum as $time => $values) {
                $opacity = ((count($values['treatments']) / $maxCount)*2/3)+1/3;
                $microTime = ParserHelper::getTodayTimestampFromTimeInMicroSeconds($time);
                sort($values['treatments']);
                $min = min($values['treatments']);
                $v25 = $values['treatments'][max(0, floor(count($values['treatments']) * 0.25) - 1)];
                $v50 = $values['treatments'][max(0, floor(count($values['treatments']) * 0.50) - 1)];
                $v75 = $values['treatments'][max(0, floor(count($values['treatments']) * 0.75) - 1)];
                $max = max($values['treatments']);
                if($v75 == $v50) {
                    $v75 = min($v75 + 0.2, $max);
                }
                if($v25 == $min) {
                    $v25 = min($v25 + 0.2, $v50);
                }

                $stops = [
                    [0, $colors[$colorScheme]['max']], // max
                    [1-($v75/$max), $colors[$colorScheme]['v75']], // v75
                    [1-($v50/$max), $colors[$colorScheme]['v50']], // v50
                    [1-($v25/$max), $colors[$colorScheme]['v25']], // v25
                    [1-($min/$max), $colors[$colorScheme]['min']], // min
                    [1, 'white'], // 0
                ];

                $serie[] = [
                    'x' => $microTime, 'y' => $max,
                    'color' => [
                        'linearGradient'=> [ 'x1' => 0, 'x2' => 0, 'y1' => 0, 'y2' => 1 ],
                        'stops' => $stops,
                    ],
                    'opacity' => $opacity,
                    'name' => $v50
                ];
            }
            $chartSerie = [
                'type' => 'column',
                'data' => $serie,
                'yAxis' => 1,
                'pointRange' => 60 * 60 * 1000, //largeur
                'borderWidth' => 0,
                'zIndex' => 150,
            ];
            $chartSerie['dataLabels'] = [
                'enabled' => true,
                'format' => '{point.name}',
                'style' => ['fontSize' => '0.8rem', 'textOutline' => 'white', 'color' => 'black']];
            $_chart->series[] = $chartSerie;
            $colorScheme ++;
        }
    }

    /**
     * @param Highchart $_chart
     * @return void
     * @deprecated
     */
    private function addStackedTreatmentSeries(Highchart $_chart): void {
        //add insulin data
        //echo "<pre>";
        $colors = [];
        $colors[0] = ['min' => '#f26161', 'v25' => '#bf2626', 'v50' => '#8B0000FF', 'v75' => '#bf2626', 'max' => '#f26161'];
        $colors[1] = ['min' => '#6466d9', 'v25' => '#383bd9', 'v50' => '#0a0da5', 'v75' => '#383bd9', 'max' => '#6466d9'];

        $insulinData = $this->m_data->getInsulinAgpData();
        $colorScheme = 0;
        foreach ($insulinData as $insulinType => $insulinDatum) {
            $series = [];
            $maxCount = 0;
            foreach ($insulinDatum as $values) {
                $maxCount = max($maxCount, count($values['treatments']));
            }

            foreach ($insulinDatum as $time => $values) {
                $opacity = ((count($values['treatments']) / $maxCount)*2/3)+1/3;
                $microTime = ParserHelper::getTodayTimestampFromTimeInMicroSeconds($time);
                sort($values['treatments']);
                $min = min($values['treatments']);
                $v25 = $values['treatments'][max(0, floor(count($values['treatments']) * 0.25) - 1)] - $min;
                $v50 = $values['treatments'][max(0, floor(count($values['treatments']) * 0.50) - 1)] - $v25;
                $v75 = $values['treatments'][max(0, floor(count($values['treatments']) * 0.75) - 1)] - $v50;
                $max = max($values['treatments']) - $v75;

                $series['min'][] = ['x' => $microTime, 'y' => $min, 'color' => $colors[$colorScheme]['min'], 'opacity' => $opacity];
                $series['v25'][] = ['x' => $microTime, 'y' => $v25, 'color' => $colors[$colorScheme]['v25'], 'opacity' => $opacity];
                $series['v50'][] = ['x' => $microTime, 'y' => $v50, 'color' => $colors[$colorScheme]['v50'], 'opacity' => $opacity];
                $series['v75'][] = ['x' => $microTime, 'y' => $v75, 'color' => $colors[$colorScheme]['v75'], 'opacity' => $opacity];
                $series['max'][] = ['x' => $microTime, 'y' => $max, 'color' => $colors[$colorScheme]['max'], 'opacity' => $opacity];
            }
            foreach($series as $serieName => $serie) {
                $chartSerie = [
                    'type' => 'column',
                    'stacking' => 'overlap',
                    'stack' => $insulinType,
                    'data' => $serie,
                    'yAxis' => 1,
                    'pointRange' => 60 * 60 * 1000, //largeur
                    'borderWidth' => 0
                ];
                if($serieName == 'v50') {
                    $chartSerie['dataLabels'] = ['enabled' => true, 'inside' => false, 'style' => ['fontSize' => '0.8rem', 'textOutline' => 'white', 'color' => 'black']];
                }
                $_chart->series[] = $chartSerie;
            }
            $colorScheme ++;
        }
    }


    private function createChart(): Highchart {
        $chart = $this->createDefaultChart();
        $chart->chart->marginRight = 40;
        $xAxis = $this->getBottomLabelledXAxis();
        $xAxis['min'] = strtotime('midnight') * 1000;
        $xAxis['max'] = (strtotime('midnight') + 60 * 60 *24 )* 1000;
        $chart->xAxis = $xAxis;
        $treatmentYAxis = $this->getTreatmentYAxis();
        $treatmentYAxis['visible'] = false;
        //$treatmentYAxis['stackLabels'] = ['enabled' => true];

        //percentTicks
        $data = $this->m_data->getAgpData();
        $bloodGlucoseYAxis = $this->getBloodGlucoseYAxis();
        $bloodGlucoseYAxis['plotLines'][] = ['value' => last($data[5]), 'width' => 0, 'zIndex' => 1000, 'label' => ['align' => 'right', 'x' => 25, 'text' => '5%']];
        $bloodGlucoseYAxis['plotLines'][] = ['value' => last($data[25]), 'width' => 0, 'zIndex' => 1000, 'label' => ['align' => 'right', 'x' => 25, 'text' => '25%']];
        $bloodGlucoseYAxis['plotLines'][] = ['value' => last($data[50]), 'width' => 0, 'zIndex' => 1000, 'label' => ['align' => 'right', 'x' => 25, 'text' => '50%']];
        $bloodGlucoseYAxis['plotLines'][] = ['value' => last($data[75]), 'width' => 0, 'zIndex' => 1000, 'label' => ['align' => 'right', 'x' => 25, 'text' => '75%']];
        $bloodGlucoseYAxis['plotLines'][] = ['value' => last($data[95]), 'width' => 0, 'zIndex' => 1000, 'label' => ['align' => 'right', 'x' => 25, 'text' => '95%']];

        $chart->yAxis = [$bloodGlucoseYAxis, $treatmentYAxis];
        return $chart;
    }

    /**
     * @param Highchart $_chart
     * @return void
     */
    private function addBloodGlucoseSeries(Highchart $_chart): void {
        $targets = $this->m_data->getTargets();
        //prepareData
        $data = $this->m_data->getAgpData();
        $outerSerie = $innerSerie = $middleSerie = [];
        foreach (array_keys($data[50]) as $index) {
            $microTime = ParserHelper::getTodayTimestampFromTimeInMicroSeconds($index);
            $outerSerie[] = [$microTime, $data[5][$index], $data[95][$index]];
            //$innerSerie[] = [$microTime, $data[25][$index], max($data[75][$index], min($data[95][$index], $targets['high']))];
            $innerSerie[] = [$microTime, $data[25][$index], $data[75][$index]];
            $middleSerie[] = [$microTime, $data[50][$index]];
        }
        /*echo "<pre>";
        var_dump(array_map(function ($_val) {
            return date('H:i', strtotime("midnight +$_val seconds"));
        }, array_keys($data[50])));*/

        //set series
        $_chart->series[] = [
            'type' => 'areasplinerange',
            'data' => $outerSerie,
            'name' => '5% - 95%',
            'zones' => [
                [
                    'className' => 'outer-low',
                    'color' => '#fb987a',
                    'value' => $targets['tightRange']
                ],
                [
                    'className' => 'outer-inrange',
                    'color' => '#bfe1c6',
                    'value' => $targets['high']
                ],
                [
                    'className' => 'outer-high',
                    'color' => '#ffe4b8',
                    'value' => $targets['veryHigh']
                ],
                [
                    'className' => 'outer-veryHigh',
                    'color' => '#feccbd'
                ]
            ]
        ];
        $_chart->series[] = [
            'type' => 'areasplinerange',
            'data' => $innerSerie,
            'name' => '25% - 75%',
            'zones' => [
                [
                    'className' => 'inner-low',
                    'color' => '#ffcc7a',
                    'value' => $targets['tightRange']
                ],
                [
                    'className' => 'inner-inrange',
                    'color' => '#8bcd9e',
                    'value' => $targets['high']
                ],
                [
                    'className' => 'inner-high',
                    'color' => '#ffcc7a',
                    'value' => $targets['veryHigh']
                ],
                [
                    'className' => 'inner-veryHigh',
                    'color' => '#ffbc50'
                ]
            ]
        ];
        $_chart->series[] = [
            'type' => 'spline',
            'data' => $middleSerie,
            'name' => 'Glycémie moyenne',
            'lineWidth' => 3,
            'zones' => [
                [
                    'color' => '#ffad00',
                    'value' => $targets['tightRange']
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
}
