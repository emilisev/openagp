<?php

namespace App\View\Components;

use App\Models\DiabetesData;
use Ghunti\HighchartsPHP\Highchart;
use Ghunti\HighchartsPHP\HighchartJsExpr;
use Illuminate\Support\Facades\Request;
use Illuminate\View\Component;

class Weekly extends Component {
    private $m_data;

    /**
     * @var string
     */
    private $m_renderTo;

    /**
     * Create the component instance.
     */
    public function __construct(DiabetesData $data, string $renderTo = null) {
        $this->m_data = $data;
        $this->m_renderTo = $renderTo;
    }

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        if(Request::route()->getName() == 'agp') {
            $weeks = 2;
        } else {
            $weeks = ceil(($this->m_data->getEnd() - $this->m_data->getBegin()) / (60*60*24*7));
        }
        $start = strtotime("midnight +1day -$weeks weeks", $this->m_data->getEnd());
        //var_dump("start", date('Y-m-d H:i:s', $start));
        //prepare plotLines
        $data = $this->m_data->getDailyDataByWeek($weeks);
        ksort($data);
        $plotLines = $ticks = [];
        /*foreach(array_keys($data) as $i => $microKey) {
            $key = $microKey / DiabetesData::__1SECOND;*/
        $darkLine = true;
        for($key = $start; $key <= $start + 60*60*24*7; $key += 60*60*12) {
            $microKey = $key * 1000;
            if(empty($ticks)) {
                $ticks[] = $microKey;
            }
            $plotLines[] = [
                'value' => $microKey,
                'color' => $darkLine?'#777777':'#e9e9e9'
            ];
            if(!$darkLine) {
                $ticks[] = $microKey;
                //var_dump(readableDate($microKey));
            }
            $darkLine = !$darkLine;
        }
        $ticks[] = $microKey;
        //var_dump($ticks);
        //prepare chart
        $chart = new Highchart();
        $targets = $this->m_data->getTargets();
        $chart->chart = [
            'renderTo' => $this->m_renderTo,
            'height' => ($weeks * 100)+50
        ];
        $chart->plotOptions->series = ['enableMouseTracking' => false, 'marker' => ['enabled' => false]];
        $chart->tooltip->enabled = false;
        $chart->title->text = null;
        $chart->legend = ['enabled' => false];
        $chart->time->timezoneOffset = -60;
        $zones = [
            [
                'className' => 'outer-low',
                'color' => '#ff2b34',
                'value' => $targets['low']
            ],
            [
                'className' => 'outer-inrange',
                'color' => '#25bf70',
                'value' => $targets['high']
            ],
            [
                'className' => 'outer-high',
                'color' => '#ffb61b',
            ],
        ];
        //with blank
        /*$blankHeight = (round(100 / $weeks / 8 * 10))/10;
        $weeklyGraphHeight = (round(100 / $weeks * 7 / 8 * 10))/10 + ($blankHeight / $weeks);*/
        //without blank
        $weeklyGraphHeight = (round(100 / $weeks * 10))/10;
        $yAxisBase = [
            'title' => ['text' => 'mg/dL', 'rotation' => 0, 'offset' => 10, 'align' => 'high', 'y' => 25, 'style' => ['fontSize' => '0.8rem']],
            'tickPositions' => [0, $targets['low'], $targets['high'], 350],
            'height' => $weeklyGraphHeight.'%',
            'offset' => 0,
            'showFirstLabel' => false,
            'showLastLabel' => false,
            'plotLines' => [
                ['value' => 0, 'width' => 1, 'color' => '#777777', 'zIndex' => 10],
                ['value' => 350, 'width' => 1, 'color' => '#777777'],
            ]
        ];
        $chart->yAxis = [];

        //add 1 serie per week
        $yAxisNumber = $xAxisNumber = $currentHeight = 0;
        for($weekNum = $weeks; $weekNum >= 1; $weekNum --) {
            $data = $this->m_data->getDailyDataByWeek($weekNum);
            $dataForChart = [];
            foreach($data as $key => $value) {
                $dataForChart[] = [$key, $value];
            }
            if($xAxisNumber == 0) {
                $chart->xAxis = [
                    [
                        'type' => 'datetime',
                        'labels' => [
                            'format' => '{value:%A}',
                        ],
                        'plotLines' => $plotLines,
                        'tickPositions' => $ticks,
                        'opposite' => true,
                        'lineWidth' => 0,
                        'tickWidth' => 0,
                        'startOnTick' => true,
                        'endOnTick' => true,
                        'showFirstLabel' => false,
                        'showLastLabel' => false,
                    ]
                ];
            } else {
                $chart->xAxis[] = [
                    'visible' => false,
                    'type' => 'datetime',
                ];
            }
            //with blank
            /*if($yAxisNumber > 0) {
                $chart->yAxis[] = [
                    'top' => $currentHeight.'%',
                    'height' => $blankHeight.'%',
                    'title' => ['enabled' => false],
                ];
                $currentHeight += $blankHeight;
                $yAxisNumber ++;
                $chart->series[] = [
                    'type' => 'line',
                    'data' => [],
                    'xAxis' => $xAxisNumber,
                    'yAxis' => $yAxisNumber,
                ];
            }*/
            $chart->yAxis[] = $yAxisBase + ['top' => $currentHeight.'%'];
            $currentHeight += $weeklyGraphHeight;
            $chart->series[] = [
                'type' => 'line',
                'data' => $dataForChart,
                'dataLabels' => ['enabled' => true, 'verticalAlign' => 'bottom', 'formatter' => new HighchartJsExpr("function() {
                    var date = new Date(this.key);
                    var result = null;
                    if(date.getHours() == 12 && date.getMinutes() == 0) {
                        result = date.getDate();
                    }
                    if(result == 1) {
                        result += '/'+(date.getMonth()+1);
                    }
                    return result;
                }"
                )],
                'xAxis' => $xAxisNumber,
                'yAxis' => $yAxisNumber,
                'zones' => $zones
            ];
            $yAxisNumber ++;
            $xAxisNumber ++;
        }
        //echo "<pre>".$chart->render()."</pre>";
        echo '<script type="module">'.$chart->render().'</script>';
    }
}
