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
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use IntlDateFormatter;

class AgpController extends BaseController {
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /********************** PUBLIC METHODS *********************/
    public function postToGet(Request $_request) {
        $notes = Request::get('notes');
        if(!empty($notes)) {
            return Redirect::to('daily/notes/'.$notes);
        }
        return $this->view($_request);
    }

    public function searchNotes(Request $_request, string $_notes = null) {
        $nightscoutProvider = new NightscoutProvider(
            Request::session()->get('url'), Request::session()->get('apiSecret'),
            null, null);
        $matchingDates = $nightscoutProvider->searchNotes($_notes);
        if(empty($matchingDates)) {
            return view(
                'web.welcome', ['error' => __("Aucune donnée contenant les notes :notes", ['notes' => $_notes])],
            );
        }
        foreach($matchingDates as $timestamp) {
            $date = new DateTime();
            $date->setTimestamp($timestamp['timestamp']/DiabetesData::__1SECOND);
            Request::session()->set('startDate', $date->format('d/m/Y'));
            Request::session()->set('endDate', $date->format('d/m/Y'));
        }

    }


    public function view(Request $_request, string $_notes = null) {
        $date1 = microtime(true);
        try {
            if(Request::route()->getName() == 'daily' && !empty($_notes)) {
                $nightscoutProvider = new NightscoutProvider(
                    Request::session()->get('url'), Request::session()->get('apiSecret'),
                    null, null);
                $matchingDates = $nightscoutProvider->searchNotes($_notes);
                if(empty($matchingDates)) {
                    throw new \Exception(__("Aucune donnée contenant les notes :notes", ['notes' => $_notes]));
                }
                $data = [];
                $matchingStringDates = [];
                foreach($matchingDates as $timestamp) {
                    $date = new DateTime();
                    $date->setTimestamp($timestamp['timestamp'] / DiabetesData::__1SECOND);
                    $startDate = $date->format('d/m/Y');
                    $matchingStringDates[$startDate] = $startDate;
                }
                foreach($matchingStringDates as $startDate) {
                    $endDate = $startDate;
                    Request::session()->put('startDate', $startDate);
                    Request::session()->put('endDate', $endDate);
                    $data[] = $this->fetchAndPrepareData($startDate, $endDate);
                }
            } else {
                $startDate = Request::session()->get('startDate');
                $endDate = Request::session()->get('endDate');
                $data = $this->fetchAndPrepareData($startDate, $endDate);
            }
        } catch(\Exception $e) {
            return view(
                'web.welcome', ['error' => $e->getMessage(),
                'formDefault' => ['url' => Request::session()->get('url'),
                    'apiSecret' => Request::session()->get('apiSecret'),
                    'dates' => "$startDate - $endDate"]]);
        }
        $chart = new Highchart();

        $date2 = microtime(true);
        $date3 = microtime(true);
        $date5 = microtime(true);
        /*var_dump('1 => 2', round(($date2 * 1000 - $date1 * 1000)) / 1000);
        var_dump('2 => 3', );
        var_dump('2b => 3', round(($date3 * 1000 - $date2b * 1000)) / 1000);
        var_dump('3 => 4', round(($date4 * 1000 - $date3 * 1000)) / 1000);
        var_dump('4 => 5', round(($date5 * 1000 - $date4 * 1000)) / 1000);
        var_dump('1 => 5', );*/
        //$times = ['total' => round(($date5 * 1000 - $date1 * 1000)) / 1000, 'network' => round(($date3 * 1000 - $date2 * 1000)) / 1000];
        //var_dump(Route::currentRouteName());
        $dateFormatter = new IntlDateFormatter(
            array_search(App::getLocale(), config('languages.list'))??config('app.locale'),
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            null,
            null,
            'E dd MMM y'
        );

        return view(
            'web.'.Request::route()->getName(),
            [
                'data' => $data,
                'chart' => $chart,
                'formDefault' => ['startDate' => $startDate, 'endDate' => $endDate],
                //'times' => $times,
                'dateFormatter' => $dateFormatter
            ]);
    }

    private function fetchAndPrepareData($_startDate, $_endDate) {
        $startDateObject = DateTime::createFromFormat('d/m/Y H:i:s', $_startDate.' 00:00:00');
        $endDateObject = DateTime::createFromFormat('d/m/Y H:i:s', $_endDate.' 23:59:59');
        if(!$startDateObject || !$endDateObject) {
            throw new \Exception(__("Vérifiez les dates sélectionnées"));
        }
        /*$startDate = DateTime::createFromFormat('d/m/Y H:i:s', '04/11/2023 12:00:00');
        $endDate = DateTime::createFromFormat('d/m/Y H:i:s', '04/11/2023 23:59:00');*/

        $nightscoutProvider = new NightscoutProvider(
            Request::session()->get('url'), Request::session()->get('apiSecret'),
            $startDateObject, $endDateObject);
        $forceTreatmentRefresh = false;
        if(Request::route()->getName() == 'daily' && !empty(Request::get('setNullTreatment'))) {
            $this->setNullTreatment($nightscoutProvider, Request::get('setNullTreatment'));
            $forceTreatmentRefresh = true;
        }
        $rawData = ['bloodGlucose' => $nightscoutProvider->fetchEntries()];
        $rawData['treatments'] = $nightscoutProvider->fetchTreatments($forceTreatmentRefresh);

        //$data = Storage::disk('local')->get('response.json');
        $utcOffset = $endDateObject->getOffset();
        $data = new DiabetesData($rawData, $utcOffset);
        $data->setTargets(config('diabetes.bloodGlucose.targets'));
        $data->setAgpStep(30);
        $data->parse();

        //prepare dates for filtering response
        $startDateSeconds = $startDateObject->format('U');
        $endDateSeconds = $endDateObject->format('U');
        $data->filter($startDateSeconds, $endDateSeconds);
        if(count($data->getBloodGlucoseData()) == 0) {
            throw new \Exception(__("Aucune donnée pour les dates sélectionnées"));
        }
        $data->computeTimeInRange();
        $data->computeAverage();
        $data->computeBloodGlucoseAgp();
        $data->prepareDailyData(30);
        //$data->smoothAgp([5 => 2, 25=> 3, 50 => 4, 75 => 3, 95 => 2]);
        $data->smoothAgp([5 => 1, 25 => 1, 50 => 1, 75 => 1, 95 => 1]);
        return $data;
    }

    private function setNullTreatment(NightscoutProvider $_nightscoutProvider, $_identifier) {
        $_nightscoutProvider->setNullTreatment($_identifier);



    }
}
