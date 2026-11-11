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
use function App\DataProviders\readableDate;

class AgpController extends BaseController {
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected array $m_nightscoutProviders = [];

    /********************** PUBLIC METHODS *********************/
    public function postToGet(Request $_request) {
        $notes = Request::get('notes');
        if(!empty($notes)) {
            return Redirect::to('daily/notes/'.$notes);
        }
        return $this->view($_request);
    }

    public function getMatchingDatesForNotes(string $_notes = null) {
        $nightscoutProvider = $this->getNightscoutProvider(null, null);
        $matchingDates = $nightscoutProvider->searchNotes($_notes);
        if(empty($matchingDates)) {
            throw new \Exception( __("Aucune donnée contenant les notes :notes", ['notes' => $_notes]));
        }
        return $matchingDates;

    }

    public function view(Request $_request, string $_notes = null) {
        ServerTiming::start('AgpController');
        try {
            if(empty(Request::session()->get('bgFrequency'))) {
                $this->checkBgFrequency();
            }
            if(Request::route()->getName() == 'daily' && !empty($_notes)) {
                $matchingDates = $this->getMatchingDatesForNotes($_notes);
                $data = [];
                $matchingStringDates = [];
                foreach($matchingDates as $timestamp) {
                    $date = new DateTime();
                    $date->setTimestamp(($timestamp['date']??$timestamp['timestamp']??$timestamp['srvCreated']) / DiabetesData::__1SECOND);
                    if($this->getFocusOnNight() && $date->format('H') >=12) {
                        $date->modify('+1 day');
                    }
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
                $endDateObject = DateTime::createFromFormat('d/m/Y', $endDate);
                if(in_array(Request::route()->getName(), ['daytoday', 'minmax'])) {
                    $startDateObject = DateTime::createFromFormat('d/m/Y', $startDate);
                    $dailyData = $mins = $maxs = [];
                    for($date = $endDateObject->format('U'); $date >= $startDateObject->format('U'); $date -= 60 *60 * 24) {
                        //var_dump(date('Y-m-d H:i:s', $date));
                        $data = $this->fetchAndPrepareData(date('d/m/Y', $date), date('d/m/Y', $date));
                        if(Request::route()->getName() == 'minmax'
                            && ($numericBg = array_filter($data->getBloodGlucoseData(), function($_v) { return is_numeric($_v);}))
                            && !empty($numericBg)) {
                            $min = min($numericBg);
                            $max = max($numericBg);
                            if(count($mins) < 3 || $min < min($mins)) {
                                $mins[$date] = $min;
                                $dailyData[$date] = $data;
                                if(count($mins) > 3) {
                                    $maxInMin = max($mins);
                                    $key = array_search($maxInMin, $mins);
                                    unset($mins[$key]);
                                }
                            }
                            if (count($maxs) < 3 || $max > max($maxs)) {
                                $maxs[$date] = $max;
                                $dailyData[$date] = $data;
                                if(count($maxs) > 3) {
                                    $minInMax = min($maxs);
                                    $key = array_search($minInMax, $maxs);
                                    unset($maxs[$key]);
                                }
                            }
                        } else {
                            $dailyData[$date] = $data;
                        }
                    }
                }
                $data = $this->fetchAndPrepareData($startDate, $endDate);
                if(in_array(Request::route()->getName(), ['pump-site-location'])) {
                    if(!array_key_exists('siteChange', $data->getTreatmentsData()) || count($data->getTreatmentsData()['siteChange']) == 1) {
                        throw new \Exception(__("Pour utiliser cette vue, assurez-vous d'enregistrer les changements de site avec un événement Nightscout 'Pump site change' et de noter en commentaire l'emplacement de la pompe"));
                    }
                    $pumpSiteChanges = $data->getTreatmentsData()['siteChange'];
                    $endTimestamp = $endDateObject->format('U') * DiabetesData::__1SECOND + DiabetesData::__1DAY;
                    $pumpSiteChanges[$endTimestamp] = '';
                    ksort($pumpSiteChanges);
                    $pumpSiteChangesCount = [];
                    //echo "<pre>";
                    foreach($pumpSiteChanges as $date => $location) {
                        $pumpSiteEndDate = $date;
                        if(isset($pumpSiteStartDate) && isset($previousLocation)) {
                            //var_dump($previousLocation);
                            $localData = clone $data;
                            $localData->filter($pumpSiteStartDate / DiabetesData::__1SECOND, $pumpSiteEndDate / DiabetesData::__1SECOND);
                            //var_dump(count($localData->getBloodGlucoseData()), $pumpSiteStartDate / DiabetesData::__1SECOND, $pumpSiteEndDate / DiabetesData::__1SECOND);
                            if(!isset($dailyData[$previousLocation])) {
                                $dailyData[$previousLocation] = $localData;
                                $pumpSiteChangesCount[$previousLocation] = 1;
                            } else {
                                //var_dump('merge', count($dailyData[$previousLocation]->getBloodGlucoseData()). '+'. count($localData->getBloodGlucoseData()));
                                $dailyData[$previousLocation]->mergeWith($localData);
                                //var_dump(count($dailyData[$previousLocation]->getBloodGlucoseData()));
                                $pumpSiteChangesCount[$previousLocation] ++;
                            }
                        }
                        $pumpSiteStartDate = $date;
                        $previousLocation = $location;
                    }
                    $dailyDataWithCount = [];
                    foreach($dailyData as $key => $values) {
                        if(array_key_exists($key, $pumpSiteChangesCount)) {
                            $dailyDataWithCount[$key.' ('.$pumpSiteChangesCount[$key].')'] = $values;
                        } else {
                            $dailyDataWithCount[$key] = $values;
                        }
                    }
                    $dailyData = $dailyDataWithCount;
                    foreach($dailyData as $localData) {
                        $localData->computeAverage();
                        $localData->computeTimeInRange();
                    }
                    /*echo "</pre>";
                    die();*/
                }
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
            'E dd MMM'
        );

        $result = view(
            'web.'.Request::route()->getName(),
            [
                'data' => $data,
                'dailyData' => @$dailyData,
                //min-max view
                'mins' => @$mins,
                'maxs' => @$maxs,

                'chart' => $chart,
                'formDefault' => ['startDate' => $startDate, 'endDate' => $endDate,
                    'isFocusOnNightAllowed' => $this->isFocusOnNightAllowed(),
                    'focusOnNight' => Request::session()->get('focusOnNight')
                ],
                'dateFormatter' => $dateFormatter
            ]);
        ServerTiming::stop('AgpController');
        return $result;
    }

    private function checkBgFrequency() {
        $nightscoutProvider = $this->getNightscoutProvider(null, null);
        $result = $nightscoutProvider->getLastTwoEntries();
        if(count($result) == 2) {
            $timeDiff = $result[0]['date'] - $result[1]['date'];
            /*var_dump($result, readableDate($result[1]['date']), readableDate($result[0]['date']),
                     $timeDiff, DiabetesData::__1MINUTE * 3);*/
            if($timeDiff < DiabetesData::__1MINUTE * 3) {
                Request::session()->put('bgFrequency', 1);
                //echo "data every mn";
            } else {
                Request::session()->put('bgFrequency', 5);
                //echo "data every 5mn";
            }
            return;
        }
        //default to 5
        Request::session()->put('bgFrequency', 5);
    }

    private function fetchAndPrepareData($_startDate, $_endDate):DiabetesData {
        if($_startDate instanceof DateTime) {
            $startDateObject = $_startDate;
        } else {
            $startDateObject = DateTime::createFromFormat('d/m/Y H:i:s', $_startDate.' 00:00:00');
        }
        if($_endDate instanceof DateTime) {
            $endDateObject = $_endDate;
        } else {
            $endDateObject = DateTime::createFromFormat('d/m/Y H:i:s', $_endDate.' 23:59:59');
        }
        if(!$startDateObject || !$endDateObject) {
            throw new \Exception(__("Vérifiez les dates sélectionnées"));
        }
        if($this->getFocusOnNight()) {
            $startDateObject->modify('-12hours');
            $endDateObject->modify('-12hours');
        }
        /*$startDate = DateTime::createFromFormat('d/m/Y H:i:s', '04/11/2023 12:00:00');
        $endDate = DateTime::createFromFormat('d/m/Y H:i:s', '04/11/2023 23:59:00');*/

        $nightscoutProvider = $this->getNightscoutProvider($startDateObject, $endDateObject);
        $forceTreatmentRefresh = false;
        if(Request::route()->getName() == 'daily' && !empty(Request::get('setNullTreatment'))) {
            $this->setNullTreatment($nightscoutProvider, Request::get('setNullTreatment'));
            $forceTreatmentRefresh = true;
        }
        try {
            $rawData = ['deviceStatus' => [], 'bloodGlucose' => $nightscoutProvider->fetchEntries()];
            $rawData['treatments'] = $nightscoutProvider->fetchTreatments($forceTreatmentRefresh);
            if(in_array(Request::route()->getName(), ['daily', 'iob', 'battery'])) {
                $rawData['deviceStatus'] = $nightscoutProvider->fetchDeviceStatus();
            }
        } catch (\Exception $exception) {
            if($exception->getCode() == 401) {
                throw new \Exception(__("Token invalide, impossible de se connecter."));
            }
            throw $exception;
        }
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
        $data->prepareDailyData(30);
        //$data->smoothAgp([5 => 2, 25=> 3, 50 => 4, 75 => 3, 95 => 2]);
        $data->smoothAgp([5 => 1, 25 => 1, 50 => 1, 75 => 1, 95 => 1]);
        return $data;
    }

    private function getFocusOnNight() {
        if($this->isFocusOnNightAllowed() && Request::session()->get('focusOnNight') == true) {
            return true;
        }
        return false;
    }

    /**
     * @param DateTime $_startDateObject
     * @param DateTime $_endDateObject
     * @return NightscoutProvider
     */
    private function getNightscoutProvider(?DateTime $_startDateObject, ?DateTime $_endDateObject): NightscoutProvider {
        $key = ($_startDateObject instanceof DateTime?$_startDateObject->format('U'):'')
            .'-'.
            ($_endDateObject instanceof DateTime?$_endDateObject->format('U'):'');
        if(array_key_exists($key, $this->m_nightscoutProviders) && $this->m_nightscoutProviders[$key] instanceof NightscoutProvider) {
            return $this->m_nightscoutProviders[$key];
        }
        $this->m_nightscoutProviders[$key] = new NightscoutProvider(
            Request::session()->get('url'), Request::session()->get('apiSecret'),
            $_startDateObject, $_endDateObject);
        return $this->m_nightscoutProviders[$key];
    }

    /**
     * @return bool
     */
    private function isFocusOnNightAllowed(): bool {
        if(!in_array(Request::route()->getName(), ['daily', 'overlay', 'daytoday', 'battery'])) {
            return false;
        }
        return true;
    }

    private function setNullTreatment(NightscoutProvider $_nightscoutProvider, $_identifier) {
        $_nightscoutProvider->setNullTreatment($_identifier);



    }
}
