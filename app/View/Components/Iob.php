<?php

namespace App\View\Components;

use App\Helpers\ParserHelper;
use Ghunti\HighchartsPHP\Highchart;

class Iob extends HighChartsComponent {

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        $chart = $this->createChart();
        $this->addIobSeries($chart);
        echo '<script type="module">'.$chart->render().'</script>';
    }


    /**
     * @param Highchart $_chart
     * @return void
     */
    private function addIobSeries(Highchart $_chart): void {
        //prepareData
        $data = $this->m_data->getIobData();
        if(empty($data)) {
            return;
        }
        $outerSerie = $innerSerie = $middleSerie = [];
        foreach (array_keys($data[50]) as $index) {
            $microTime = ParserHelper::getTodayTimestampFromTimeInMicroSeconds($index);
            $outerSerie[] = [$microTime, $data[5][$index], $data[95][$index]];
            $innerSerie[] = [$microTime, $data[25][$index], $data[75][$index]];
            $middleSerie[] = [$microTime, $data[50][$index]];
        }
        //set series
        $_chart->series[] = [
            'type' => 'areasplinerange',
            'data' => $outerSerie,
            'name' => '5% - 95%',
            'color' => config('colors.iob.outerSerie')
        ];
        $_chart->series[] = [
            'type' => 'areasplinerange',
            'data' => $innerSerie,
            'name' => '25% - 75%',
            'color' => config('colors.iob.innerSerie')
        ];
        $_chart->series[] = [
            'type' => 'spline',
            'data' => $middleSerie,
            'name' => __('Insuline active moyenne'),
            'lineWidth' => 3,
            'color' => config('colors.iob.iob')
        ];
    }

    private function createChart(): Highchart {
        $chart = $this->createDefaultChart();
        $chart->chart->marginRight = 40;
        $chart->chart->zoomType = 'xy';
        $xAxis = $this->getBottomLabelledXAxis();
        $xAxis['min'] = strtotime('midnight') * 1000;
        $xAxis['max'] = (strtotime('midnight') + 60 * 60 *24 )* 1000;
        $chart->xAxis = $xAxis;

        //percentTicks
        $data = $this->m_data->getIobData();
        $iobAxis = [];
        $iobAxis['plotLines'][] = ['value' => last($data[5]), 'width' => 0, 'zIndex' => 1000, 'label' => ['align' => 'right', 'x' => 25, 'text' => '5%']];
        $iobAxis['plotLines'][] = ['value' => last($data[25]), 'width' => 0, 'zIndex' => 1000, 'label' => ['align' => 'right', 'x' => 25, 'text' => '25%']];
        $iobAxis['plotLines'][] = ['value' => last($data[50]), 'width' => 0, 'zIndex' => 1000, 'label' => ['align' => 'right', 'x' => 25, 'text' => '50%']];
        $iobAxis['plotLines'][] = ['value' => last($data[75]), 'width' => 0, 'zIndex' => 1000, 'label' => ['align' => 'right', 'x' => 25, 'text' => '75%']];
        $iobAxis['plotLines'][] = ['value' => last($data[95]), 'width' => 0, 'zIndex' => 1000, 'label' => ['align' => 'right', 'x' => 25, 'text' => '95%']];
        $iobAxis['plotLines'][] = ['value' => 0, 'width' => 1, 'zIndex' => 1000];

        $chart->yAxis = [$iobAxis];
        return $chart;
    }
}
