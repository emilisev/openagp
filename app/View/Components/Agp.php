<?php

namespace App\View\Components;

use App\Models\DiabetesData;
use Ghunti\HighchartsPHP\Highchart;
use Illuminate\View\Component;
use function App\Models\readableTime;
use function App\Models\readableTimeArray;

class Agp extends Component {
    private $m_data;

    /**
     * @var string
     */
    private $m_type;
    private $m_renderTo;
    private int $m_height;


    /**
     * Create the component instance.
     */
    public function __construct(DiabetesData $data, string $renderTo = null, string $height = null) {
        $this->m_data = $data;
        $this->m_renderTo = $renderTo;
        $this->m_height = $height;
    }

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        $chart = new Highchart();
        //prepareData
        $data = $this->m_data->getAgpData();
        $targets = $this->m_data->getTargets();
        $outerSerie = $innerSerie = $middleSerie = [];
        foreach(array_keys($data[50]) as $index) {
            $microTime = getTodayTimestampFromTimeInMicroSeconds($index);
            $outerSerie[] = [$microTime, $data[5][$index], $data[95][$index]];
            $innerSerie[] = [$microTime, $data[25][$index], max($data[75][$index], min($data[95][$index], $targets['high']))];
            $middleSerie[] = [$microTime, $data[50][$index]];
        }
        //set chart options
        $chart->chart = [
            'renderTo' => $this->m_renderTo,
            'height' => $this->m_height,
            //'style' => ['fontSize' => '0.8rem']
        ];
        $chart->plotOptions->series = ['enableMouseTracking' => false, 'marker' => ['enabled' => false]];
        $chart->time->timezoneOffset = -60;
        $chart->tooltip->enabled = false;
        $chart->title->text = null;
        $chart->legend = ['enabled' => false];
        /*echo "<pre>";
        var_dump(array_map(function ($_val) {
            return date('H:i', strtotime("midnight +$_val seconds"));
        }, array_keys($data[50])));*/
        $chart->xAxis = [
            'type' => 'datetime',
            'labels' => [
                'format' => '{value:%H:%M}',
            ],
            'tickInterval' => 3 * 60 * 60 * 1000,
            'tickWidth' => 1,
            'tickPosition' => 'inside',
            'min' => strtotime('midnight') * 1000,
            'max' => (strtotime('midnight') + 60 * 60 *24 )* 1000,
            'showLastLabel' => false,
        ];
        $chart->yAxis = [
            [
                'title' => ['text' => 'mg/dL', 'rotation' => 0, 'offset' => 10, 'align' => 'high', 'y' => 25, 'style' => ['fontSize' => '0.8rem']],
                'tickPositions' => [0, $targets['veryLow'], $targets['low'], $targets['high'], $targets['veryHigh'], 350],
                'plotLines' => [
                    ['value' => $targets['low'], 'width' => 3, 'color' => '#00b657', 'zIndex' => 10],
                    ['value' => $targets['high'], 'width' => 3, 'color' => '#00b657', 'zIndex' => 10],
                ]
            ], [
                'title' => ['text' => 'UI', 'rotation' => 0, 'offset' => 10, 'align' => 'high', 'y' => 25, 'style' => ['fontSize' => '0.8rem']],
                'opposite' => true
                ]
        ];
        //set series
        $chart->series[] = [
            'type' => 'areasplinerange',
            'data' => $outerSerie,
            'zones' => [
                [
                    'className' => 'outer-low',
                    'color' => '#fb987a',
                    'value' => $targets['low']
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
        $chart->series[] = [
            'type' => 'areasplinerange',
            'data' => $innerSerie,
            'zones' => [
                [
                    'className' => 'inner-low',
                    'color' => '#ffcc7a',
                    'value' => $targets['low']
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
                    'color' => '#bfe1c6'
                ]
            ]
        ];
        $chart->series[] = [
            'type' => 'spline',
            'data' => $middleSerie,
            'lineWidth' => 3,
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
        //add insulin data
        //echo "<pre>";
        $insulinData =   $this->m_data->getInsulinAgpData();
        foreach($insulinData as $insulinType => $insulinDatum) {
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
            foreach($insulinDatum as $time => $values) {
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
                $serie[] = [getTodayTimestampFromTimeInMicroSeconds($time),
                    $min,
                    $v25,
                    $v50,
                    $v75,
                    $max];
            }
            //var_dump($serie);
            $chart->series[] = [
                'type' => 'boxplot',
                'data' => $serie,
                'yAxis' => 1,
                'pointRange' => 60 * 60 * 1000, //largeur
                'opacity' => 1,
                'dataLabels' => ['enabled' => true]
            ];
        }
        echo '<script type="module">'.$chart->render().'</script>';
    }
}

function getTodayTimestampFromTimeInMicroSeconds($_time) {
    return strtotime("midnight +".($_time/1000)." seconds") * 1000;
}
