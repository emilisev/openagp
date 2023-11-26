<?php

namespace App\View\Components;

use App\Models\DiabetesData;
use Ghunti\HighchartsPHP\Highchart;
use Illuminate\View\Component;

class TimeInRange extends Component {
    private $m_data;

    protected $m_minValTimeInRangeChart = 2;

    protected $m_timeInRangeColors =
        ['veryHigh' => '#e36a00', 'veryLow' => '#a60006', 'high' => '#fed65c', 'low' => '#fd8c80', 'target' => '#58a618'];

    protected $m_timeInRangeLabels =
    ['veryHigh' => 'Très élevée', 'veryLow' => 'Très basse', 'high' => 'Élevée', 'low' => 'Basse', 'target' => 'Dans la plage'];
    /**
     * @var string
     */
    private $m_type;
    private $m_renderTo;


    /**
     * Create the component instance.
     */
    public function __construct(DiabetesData $data, string $type, string $renderTo = null) {
        $this->m_data = $data;
        $this->m_type = $type;
        $this->m_renderTo = $renderTo;
    }

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        if($this->m_type == 'text') {
            return $this->renderText();
        } elseif($this->m_type == 'chart') {
            return $this->renderChart();
        }elseif($this->m_type == 'settings') {
            return $this->renderSettings();
        }

    }

    private function renderChart() {
        $chart = new Highchart();

        $chart->chart = [
            "renderTo" => $this->m_renderTo,
            "type" => "column",
            "showAxes" => false,
            'margin' => 0,
            'height' => 180,
            'width' => 70,
        ];
        $chart->title->text = null;
        $chart->yAxis->max = 100;
        $chart->yAxis->visible = false;
        $chart->legend = ['enabled' => false];
        /*$chart->legend = [
            'layout' => 'vertical',
            'align' => 'right',
            'verticalAlign' => 'middle',
            'itemMarginTop' => 10,
            'itemMarginBottom' => 10
        ];*/
        $chart->xAxis->visible = false;
        $chart->tooltip->enabled = false;

        $chart->plotOptions->series->stacking = "normal";

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
            $chart->series[] = array(
                'name' => $this->m_timeInRangeLabels[$label],
                'data' => [$value],
                'color' => $this->m_timeInRangeColors[$label],
                'enableMouseTracking' => false
            );
        }

        return '<script type="module">'.$chart->render().'</script>';
    }

    private function renderSettings() {
        return view('cards.timeInRange.settings', ['targets' => $this->m_data->getTargets()]);
    }
    private function renderText() {
        return view('cards.timeInRange.text', ['timeInRangeData' => $this->m_data->getTimeInRangePercent()]);
    }
}
