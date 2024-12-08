<?php

namespace App\View\Components;

use App\Models\DiabetesData;
use Ghunti\HighchartsPHP\Highchart;

class DailyTimeInRange extends HighChartsComponent {

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    protected int $m_durationBeforeWeekDisplay = 60;

    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        foreach(config('diabetes.bloodGlucose.targets') as $key => $value) {
            if(isset($previousValue)) {
                $this->m_timeInRangeLabels[$key] .= ' (>= '.$value.' <'.$previousValue.')';
            } else {
                $this->m_timeInRangeLabels[$key] .= " (>=$value)";
            }
            $previousValue = $value;
        }
        $chart = $this->createChart();
        $this->addTimeInRangeSeries($chart);

        return '<script type="module">'.$chart->render().'</script>';
    }

    /* * * * * * * * * * * * * * * * * * * * * * PRIVATE METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    private function addTimeInRangeSeries(Highchart $_chart) {
        if($this->m_data->getDurationInDays() > $this->m_durationBeforeWeekDisplay) {
            $data = $this->m_data->getWeeklyTimeInRangePercent();
            $_chart->chart->height = (count($data['tightRange']) * 20)+100;
        } else {
            $data = $this->m_data->getDailyTimeInRangePercent();
        }

        $series = [];
        foreach(array_reverse(array_keys($this->m_timeInRangeLabels)) as $label) {
            $values = $data[$label]??[];
            $series[] = array(
                'name' => $this->m_timeInRangeLabels[$label],
                'data' => $this->formatTimeDataForChart($values),
                'color' => config('colors.timeInRange.'.$label),
                'pointWidth' => 15,
            );
        }
        $_chart->series = $series;
    }

    private function createChart(): Highchart {
        $chart = $this->createDefaultChart();
        $this->setChartHeightBasedOnDuration($chart);
        $chart->plotOptions->series->stacking = "percent";
        $chart->chart->marginLeft = 90;
        $chart->tooltip = ['shared' => true, 'valueDecimals' => 0, 'valueSuffix' => '%',
        ];
        if($this->m_data->getDurationInDays() > $this->m_durationBeforeWeekDisplay) {
            $timeLabelsFormat = 'sem. %d %b';
        } else {
            $timeLabelsFormat = '%a %d %b';
        }
        $chart->xAxis = [
            'type' => 'datetime',
            'tickInterval' => $this->m_data->getDurationInDays() > $this->m_durationBeforeWeekDisplay ?DiabetesData::__1DAY * 7:DiabetesData::__1DAY,
            'labels' => [
                'format' => '{value:'.$timeLabelsFormat.'}',
            ],
        ];
        $chart->legend = ['enabled' => true, 'reversed' => true];

        return $chart;
    }

}
