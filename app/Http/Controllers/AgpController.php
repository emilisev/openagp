<?php

namespace App\Http\Controllers;

use App\DataProviders\NightscoutProvider;
use DateTime;
use Ghunti\HighchartsPHP\Highchart;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\DiabetesData;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

class AgpController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function view() {
        $date1 = microtime(true);
        $url = Request::post()['url']??Request::session()->get('url');
        $apiSecret = @Request::post()['url']?Request::post()['apiSecret']:Request::session()->get('apiSecret');
        $dates = Request::post()['dates']??Request::session()->get('dates');
        //var_dump(Request::session()->all(), $url);

        if(empty($url) || empty($dates)) {
            return view('web.welcome');
        }
        if(!empty(Request::post())) {
            Request::session()->put('url', @Request::post()['url']);
            Request::session()->put('apiSecret', @Request::post()['apiSecret']);
            Request::session()->put('dates', @Request::post()['dates']);
        }
        if(Request::route()->getName() == 'daily') {
            if(!empty(Request::get('day'))) {
                $startDate = $endDate = Request::get('day');
            } else {
                $startDate = $endDate = date('d/m/Y');
            }
            $dates = "$startDate - $endDate";
            Request::session()->put('dates', $dates);
        } else {
            list($startDate, $endDate) = explode(" - ", $dates);
        }
        try {
            $startDate = DateTime::createFromFormat('d/m/Y H:i:s', $startDate.' 00:00:00');
            $endDate = DateTime::createFromFormat('d/m/Y H:i:s', $endDate.' 23:59:59');
            /*$startDate = DateTime::createFromFormat('d/m/Y H:i:s', '04/11/2023 12:00:00');
            $endDate = DateTime::createFromFormat('d/m/Y H:i:s', '04/11/2023 23:59:00');*/

            $nightscoutProvider = new NightscoutProvider($url, $apiSecret, $startDate, $endDate);

            $date2 = microtime(true);
            $rawData = ['bloodGlucose' => $nightscoutProvider->fetchEntries()];
            $date2b = microtime(true);
            $rawData['treatments'] = $nightscoutProvider->fetchTreatments();
        } catch (\Exception $e) {
            return view('web.welcome', ['error' => $e->getMessage(), 'formDefault' => ['url' => $url, 'apiSecret' => $apiSecret, 'dates' => $dates]]);
        }

        $date3 = microtime(true);
        //$data = Storage::disk('local')->get('response.json');
        $utcOffset = $endDate->getOffset();
        $data = new DiabetesData($rawData, $utcOffset);
        $data->setTargets([
            'veryHigh' => 250,
            'veryLow' => 54,
            'high' => 180,
            'low' => 70]);
        $data->setAgpStep(30);
        $data->parse();

        $date4 = microtime(true);
        //prepare dates for filtering response
        $startDateSeconds = $startDate->format('U');
        $endDateSeconds = $endDate->format('U');
        $data->filter($startDateSeconds, $endDateSeconds);
        if(count($data->getBloodGlucoseData()) == 0) {
            return view('web.welcome', ['error' => "Aucune donnée pour les dates sélectionnées",
                'formDefault' => ['url' => $url, 'apiSecret' => $apiSecret, 'dates' => $dates]]);
        }
        $data->computeTimeInRange();
        $data->computeAverage();
        $data->computeBloodGlucoseAgp();
        $data->computeInsulinAgp(60*4);
        $data->prepareDailyData(30);
        //$data->smoothAgp([5 => 2, 25=> 3, 50 => 4, 75 => 3, 95 => 2]);
        $data->smoothAgp([5 => 1, 25=> 1, 50 => 1, 75 => 1, 95 => 1]);
        $chart = new Highchart();

        $date5 = microtime(true);
        //var_dump('1 => 2', round(($date2*1000 - $date1*1000))/1000);
        //var_dump('2 => 3', round(($date3*1000 - $date2*1000))/1000);
        /*var_dump('2b => 3', round(($date3*1000 - $date2b*1000))/1000);
        var_dump('3 => 4', round(($date4*1000 - $date3*1000))/1000);
        var_dump('4 => 5', round(($date5*1000 - $date4*1000))/1000);*/
        //var_dump('1 => 5', round(($date5*1000 - $date1*1000))/1000);
        //var_dump(Route::currentRouteName());
        return view('web.'.Request::route()->getName(),
            [
                'data' => $data,
                'chart' => $chart,
                'datepickerDefault' => false,
                'formDefault' => ['url' => $url, 'apiSecret' => $apiSecret, 'dates' => $dates]
        ]);
    }
}
