<?php

namespace App\View\Components;

use App\Models\DiabetesData;
use Ghunti\HighchartsPHP\Highchart;
use Ghunti\HighchartsPHP\HighchartJsExpr;
use Illuminate\Support\Facades\Request;
use Illuminate\View\Component;
use function App\Models\readableTime;

class Daily extends Component {
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
        $data = $this->m_data->getBloodGlucoseData();
        ksort($data);
        $plotLines = $ticks = [];
        /*foreach(array_keys($data) as $i => $microKey) {
            $key = $microKey / DiabetesData::__1SECOND;*/
        /*$darkLine = true;
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
        $ticks[] = $microKey;*/
        //var_dump($ticks);
        //prepare chart
        $chart = new Highchart();
        $targets = $this->m_data->getTargets();
        $chart->chart = [
            'renderTo' => $this->m_renderTo,
            'height' => 500
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
        $chart->yAxis = [
            'title' => ['text' => 'mg/dL', 'rotation' => 0, 'offset' => 10, 'align' => 'high', 'y' => 25, 'style' => ['fontSize' => '0.8rem']],
            'tickPositions' => [0, $targets['low'], $targets['high'], 350],
            'offset' => 0,
            'showFirstLabel' => false,
            'showLastLabel' => false,
            'plotLines' => [
                ['value' => 0, 'width' => 1, 'color' => '#777777', 'zIndex' => 10],
                ['value' => 350, 'width' => 1, 'color' => '#777777'],
            ]
        ];

        $dataForChart = [];
        foreach($data as $key => $value) {
            $dataForChart[] = [$key, $value];
        }
        $chart->xAxis = [
            [
                'type' => 'datetime',
                'labels' => [
                    'format' => '{value:%H:%M}',
                ],
                'lineWidth' => 0,
                'tickWidth' => 0,
                'gridLineWidth' => 2
            ]
        ];

        $chart->series[] = [
            'type' => 'line',
            'data' => $dataForChart,
            'zones' => $zones,
            'lineWidth' => 2
        ];
        echo '<script type="module">'.$chart->render().'</script>';
    }
}
