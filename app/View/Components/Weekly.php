<?php

namespace App\View\Components;

use Ghunti\HighchartsPHP\Highchart;
use Ghunti\HighchartsPHP\HighchartJsExpr;
use Illuminate\Support\Facades\Request;
use StringToColor\StringToColor;

class Weekly extends SeveralTimelinesCharts {
    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        if(Request::route()->getName() == 'agp') {
            $weeks = 2;
        } else {
            $weeks = ceil(($this->m_data->getEnd() - $this->m_data->getBegin()) / (60 * 60 * 24 * 7));
        }
        $start = strtotime("midnight +1day -$weeks weeks", $this->m_data->getEnd());
        //var_dump("start", date('Y-m-d H:i:s', $start));
        //prepare plotLines
        /*$data = $this->m_data->getDailyDataByWeek($weeks);
        ksort($data);*/

        //prepare chart
        $chart = $this->createChart($weeks);
        list($ticks, $plotLines) = $this->computeTicksAndPlotlines($start, $start + 60 * 60 * 24 * 7, 60 * 60 * 12);

        $weeklyGraphHeight = (round(100 / $weeks * 10)) / 10;
        $yAxisBase = $this->getBloodGlucoseYAxis($_greenLineWidth = 1);
        $yAxisBase['height'] = $weeklyGraphHeight.'%';
        $yAxisBase['id'] = 'gloodGlucose-yAxis1';

        $this->addBloodGlucoseSeries($chart, $yAxisBase, $plotLines, $ticks, $weeks, $weeklyGraphHeight);
        $this->addTreatmentsSeries($chart, $weeks, $weeklyGraphHeight);
        $this->addCarbsSeries($chart, $weeks, $weeklyGraphHeight);

        //echo "<pre>".$chart->render()."</pre>";
        echo '<script type="module">'.$chart->render().'</script>';
    }

    /* * * * * * * * * * * * * * * * * * * * * * PRIVATE METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * @param Highchart $_chart
     * @param array $y_AxisBase
     * @param mixed $_plotLines
     * @param mixed $_ticks
     * @param int $_weeks
     * @param float $_weeklyGraphHeight
     */
    private function addBloodGlucoseSeries(Highchart $_chart, array $y_AxisBase, mixed $_plotLines, mixed $_ticks, int $_weeks, float $_weeklyGraphHeight): void {
        //add 1 serie per week
        $yAxisNumber = $xAxisNumber = $currentHeight = 0;
        for ($weekNum = $_weeks; $weekNum >= 1; $weekNum--) {
            $data = $this->m_data->getDailyDataByWeek($weekNum);
            $this->addBloodGlucoseSerie($_chart, $data, $_weeklyGraphHeight, $currentHeight, $xAxisNumber, $yAxisNumber, $y_AxisBase, $_plotLines, $_ticks);
            $yAxisNumber++;
            $xAxisNumber++;
            $currentHeight += $_weeklyGraphHeight;
        }
    }

    private function addCarbsSeries(Highchart $_chart, int $_weeks, float $_weeklyGraphHeight) {
        //add 1 serie per week
        $yAxisNumber = $xAxisNumber = $currentHeight = 0;
        for ($weekNum = $_weeks; $weekNum >= 1; $weekNum--) {
            $data = $this->m_data->getDailyTreatmentsByWeek($weekNum)['carbs'];
            $this->addCarbsSerie($_chart, $data, $_weeklyGraphHeight, $currentHeight, $yAxisNumber, $xAxisNumber);
            $currentHeight += $_weeklyGraphHeight;
            $yAxisNumber++;
            $xAxisNumber++;
        }
    }

    private function addTreatmentsSeries(Highchart $_chart, int $_weeks, float $_weeklyGraphHeight) {
        //add 1 serie per week
        $yAxisNumber = $xAxisNumber = $currentHeight = 0;
        for ($weekNum = $_weeks; $weekNum >= 1; $weekNum--) {
            $data = @$this->m_data->getDailyTreatmentsByWeek($weekNum)['insulin'];
            if(!empty($data)) {
                $this->addTreatmentsSerie($_chart, $data, $_weeklyGraphHeight, $currentHeight, $yAxisNumber, $xAxisNumber);
            }
            $currentHeight += $_weeklyGraphHeight;
            $yAxisNumber++;
            $xAxisNumber++;
        }
    }
}
