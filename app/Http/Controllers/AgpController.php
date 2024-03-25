<?php

namespace App\Http\Controllers;

use App\DataProviders\NightscoutProvider;
use BeyondCode\ServerTiming\Facades\ServerTiming;
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
        ServerTiming::start('AgpController');
        try {
            if(Request::route()->getName() == 'daily' && !empty($_notes)) {
                $nightscoutProvider = new NightscoutProvider(
                    Request::session()->get('url'), Request::session()->get('apiSecret'),
                    null, null);
                $matchingDates = $nightscoutProvider->searchNotes($_notes);
                if(empty($matchingDates)) {
                    throw new \Exception(__("Aucune donnée contenant les notes \":notes\"", ['notes' => $_notes]));
                }
                $data = [];
                $matchingStringDates = [];
                foreach($matchingDates as $timestamp) {
                    $date = new DateTime();
                    $date->setTimestamp(($timestamp['timestamp']??$timestamp['srvCreated']) / DiabetesData::__1SECOND);
                    $matchingStringDates[$date->format('Ymd')] = $date->format('d/m/Y');
                }
                krsort($matchingStringDates);
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
                    'apiSecret' => Request::session()->get('apiSecret')]]);
        }
        $chart = new Highchart();

        //var_dump(Route::currentRouteName());
        $dateFormatter = new IntlDateFormatter(
            array_search(App::getLocale(), config('languages.list'))??config('app.locale'),
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            null,
            null,
            'E dd MMM y'
        );

        $result = view(
            'web.'.Request::route()->getName(),
            [
                'data' => $data,
                'chart' => $chart,
                'formDefault' => ['startDate' => $startDate, 'endDate' => $endDate],
                //'times' => $times,
                'dateFormatter' => $dateFormatter
            ]);
        ServerTiming::stop('AgpController');
        return $result;
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

        $startDateSeconds = $startDateObject->format('U');
        $endDateSeconds = $endDateObject->format('U');
        $data->parse($endDateSeconds);

        //prepare dates for filtering response
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
