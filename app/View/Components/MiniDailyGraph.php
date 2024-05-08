<?php

namespace App\View\Components;

use App\Models\DiabetesData;
use Ghunti\HighchartsPHP\Highchart;
use Illuminate\View\Component;

class MiniDailyGraph extends Component {

    protected array $m_data;

    protected ?string $m_renderTo;

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    public function __construct(array $data, string $renderTo = null) {
        $this->m_data = $data;
        $this->m_renderTo = $renderTo;
    }


    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        $chart = $this->createChart();
        $this->addSerie($chart);
        echo '<script type="module">Highcharts.AST.allowedAttributes.push(\'onclick\');'.$chart->render().'</script>';
    }

    /* * * * * * * * * * * * * * * * * * * * * * PRIVATE METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    private function addSerie(Highchart $_chart) {
        $dataForChart = [];
        foreach($this->m_data as $item) {
            $dataForChart[] = [$item['timeAsSeconds'] * DiabetesData::__1SECOND, $item['value']];
            $lastValue = $item['value'];
        }
        $dataForChart[] = [DiabetesData::__1DAY, $lastValue];
        $_chart->series[] = [
            //'name' => $type,
            'type' => 'line',
            //'color' => $stringToColor->handle($type),
            'data' => $dataForChart,//$this->formatTimeDataForChart($datum, $insulinDuration??$dataId),
            //'keys' => ['x','y','identifier'],
            //'yAxis' => 'insulin-yAxis',
            //'borderColor' => $basal?$stringToColor->handle($type):'white',
            //'borderRadius' => $basal?0:3,
            //'pointRange' => 60 * 60 * 1000, //largeur
            //'dataLabels' => ['enabled' => !$basal, 'format' => '{y}UI'],
            //'zIndex' => $basal?1:2,
            //'tooltip' => $tooltip,
            'step' => 'left'
        ];
    }


    private function createChart(): Highchart {
        $chart = new Highchart();
        $chart->chart = [
            'renderTo' => $this->m_renderTo,
            'width' => 250,
            'height' => 150
        ];
        $chart->title->text = null;
        $chart->legend = ['enabled' => false];

        $xAxis = [
            'type' => 'datetime',
            'labels' => [
                'format' => '{value:%H:%M}',
                'distance' => 5
            ],
            'gridLineWidth' => 2,
            'tickInterval' => 3 * 60 * 60 * 1000,
            'tickWidth' => 1,
            'tickPosition' => 'inside',
            'min' => 0,
            'max' => DiabetesData::__1DAY
        ];

        $chart->xAxis = $xAxis;



        return $chart;
    }
}
