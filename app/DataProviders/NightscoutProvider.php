<?php

namespace App\DataProviders;

use DateTime;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Request;

class NightscoutProvider {

    private $m_apiSecret;
    private $m_endDate;
    private $m_startDate;
    private $m_url;

    public function __construct($_url, $_apiSecret, DateTime $_startDate, DateTime $_endDate) {

        $this->m_url = $_url;
        if(!empty($_apiSecret)) {
            $this->m_url = str_replace('//', '//'.$_apiSecret.'@', $this->m_url);
            $this->m_apiSecret = sha1($_apiSecret);
        }
        if(!str_ends_with($this->m_url, '/')) {
            $this->m_url .= '/';
        }
        $this->m_startDate = clone($_startDate);
        $this->m_endDate = clone($_endDate);

        //add 1 day before and after to recompute with time offset
        $this->m_startDate->sub(new \DateInterval('P1D'));
        $this->m_endDate->add(new \DateInterval('P1D'));
    }

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    public function fetchEntries() {
        $url = $this->m_url.'api/v1/entries.json';
        $params = [
            'query' => [
                'find[dateString][$gte]' => $this->m_startDate->format('Y-m-d'),
                'find[dateString][$lte]' => $this->m_endDate->format('Y-m-d'),
                'count' => 25000,
            ],
        ];
        if(!empty($this->m_apiSecret)) {
            $params['headers'] = [
                'API-SECRET' => $this->m_apiSecret,
            ];
        }
        return $this->getCacheOrLive($url, $params);

    }

    public function fetchTreatments() {
        $url = $this->m_url.'api/v1/treatments.json';

        $client = new Client();
        $params = [
            'query' => [
                'find[created_at][$gte]' => $this->m_startDate->format('Y-m-d'),
                'find[created_at][$lte]' => $this->m_endDate->format('Y-m-d'),
                'find[insulin][$gt]' => 0,
                //'find[enteredBy]'=>'xdrip',
                'count' => 25000,
            ],
        ];
        if(!empty($this->m_apiSecret)) {
            $params['headers'] = [
                'API-SECRET' => $this->m_apiSecret,
            ];
        }
        $response = $client->request('GET', $url, $params);

        $data = $response->getBody()->getContents();
        return json_decode($data, true);
    }

    private function getCacheOrLive($_url, $_params) {
        $cacheKey = sha1($_url.json_encode($_params));
        if(Request::session()->has($cacheKey) && $this->m_endDate < new DateTime()) {
            $data = Request::session()->get($cacheKey);
        } else {
            $client = new Client();
            $response = $client->request('GET', $_url, $_params);

            $data = $response->getBody()->getContents();
            Request::session()->put($cacheKey, $data);
        }
        return json_decode($data, true);
    }


}
