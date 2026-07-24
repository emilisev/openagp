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

class ProfilesController extends BaseController {

    public function batchUpdate(Request $_request, string $_notes = null) {

        $nightscoutProvider = new NightscoutProvider(
            Request::session()->get('url'), Request::session()->get('apiSecret'), null, null);
        $profiles = $nightscoutProvider->fetchProfiles();

        echo "<pre>";
        $profiles = $profiles[0];
        foreach($profiles['store'] as &$profile) {
            foreach($profile['target_low'] as &$segment) {
                switch($segment['time']) {
                    case "00:00":
                        $segment['value'] = '90';
                        break;
                    case "09:00":
                        $segment['value'] = '100';
                        break;
                    case "21:00":
                        $segment['value'] = '90';
                        break;
                }
            }
            unset($segment);
            foreach($profile['target_high'] as &$segment) {
                switch($segment['time']) {
                    case "00:00":
                        $segment['value'] = '90';
                        break;
                    case "09:00":
                        $segment['value'] = '100';
                        break;
                    case "21:00":
                        $segment['value'] = '90';
                        break;
                }
            }
            unset($segment);
        }
        unset($profile);
        unset($profiles['identifier']);
        unset($profiles['srvModified']);
        unset($profiles['srvCreated']);
        unset($profiles['subject']);
        unset($profiles['date']);
        $profiles['created_at'] = $profiles['startDate'] = date('Y-m-d\TH:i:s');
        /*var_dump($profiles);
        die();*/
        try {
            $newProfiles = $nightscoutProvider->postProfiles($profiles);
            var_dump($newProfiles);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
        echo "</pre>";
    }

}
