<?php

namespace App\View\Components;

use Ghunti\HighchartsPHP\Highchart;
use Illuminate\Support\Facades\Request;

class Monthly extends SeveralTimelinesCharts {
    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        $months = max(1, floor(($this->m_data->getEnd() - $this->m_data->getBegin()) / (60 * 60 * 24 * 30)));
        $start = strtotime("midnight +1day -$months months", $this->m_data->getEnd());
        //var_dump("start", date('Y-m-d H:i:s', $start));

        //prepare chart
        $chart = $this->createChart($months);
        list($ticks, $plotLines) = $this->computeTicksAndPlotlines($start, $start + 60 * 60 * 24 * 30, 60 * 60 * 12 * 7);

        $monthlyGraphHeight = (round(100 / $months * 10)) / 10;
        $yAxisBase = $this->getBloodGlucoseYAxis($_greenLineWidth = 1);
        $yAxisBase['height'] = $monthlyGraphHeight.'%';
        $yAxisBase['id'] = 'gloodGlucose-yAxis1';

        $this->addBloodGlucoseSeries($chart, $yAxisBase, $plotLines, $ticks, $months, $monthlyGraphHeight);
        $this->addTreatmentsSeries($chart, $months, $monthlyGraphHeight);
        $this->addCarbsSeries($chart, $months, $monthlyGraphHeight);

        //echo "<pre>".$chart->render()."</pre>";
        echo '<script type="module">'.$chart->render().'</script>';
    }

    /* * * * * * * * * * * * * * * * * * * * * * PRIVATE METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * @param Highchart $_chart
     * @param array $y_AxisBase
     * @param mixed $_plotLines
     * @param mixed $_ticks
     * @param int $_months
     * @param float $_weeklyGraphHeight
     */
    private function addBloodGlucoseSeries(Highchart $_chart, array $y_AxisBase, mixed $_plotLines, mixed $_ticks, int $_months, float $_weeklyGraphHeight): void {
        //add 1 serie per week
        $yAxisNumber = $xAxisNumber = $currentHeight = 0;
        for ($monthNum = $_months; $monthNum >= 1; $monthNum--) {
            $data = $this->m_data->getDailyDataByMonth($monthNum);
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
            $data = $this->m_data->getDailyTreatmentsByMonth($weekNum)['carbs'];
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
            $data = $this->m_data->getDailyTreatmentsByMonth($weekNum)['insulin'];
            $this->addTreatmentsSerie($_chart, $data, $_weeklyGraphHeight, $currentHeight, $yAxisNumber, $xAxisNumber);
            $currentHeight += $_weeklyGraphHeight;
            $yAxisNumber++;
            $xAxisNumber++;
        }
    }

}
