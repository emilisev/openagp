<?php

namespace App\DataProviders;

use DateTime;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Request;

class NightscoutProvider {

    /**
     * @var DateTime
     */
    private $m_actualEndDate;

    private $m_actualStartDate;

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
        $this->m_actualEndDate = $_endDate;
        $this->m_actualStartDate = $_startDate;

        //add 1 day before and after to recompute with time offset
        $this->m_startDate->sub(new \DateInterval('P1D'));
        $this->m_endDate->add(new \DateInterval('P1D'));
    }

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    public function fetchEntries() {
        $url = $this->m_url.'api/v1/entries.json';
        $params = [
            'query' => [
                'find[date][$gte]' => $this->m_actualStartDate->format('U')*1000,
                'find[date][$lte]' => $this->m_actualEndDate->format('U')*1000,
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
                //'find[insulin][$gt]' => 0,
                //'find[enteredBy]'=>'xdrip',
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

    private function getCacheOrLive($_url, $_params) {
        $cacheKey = sha1($_url.json_encode($_params));
        //var_dump($_url, $this->m_endDate > new DateTime(), Request::session()->has($cacheKey));
        if(Request::session()->has($cacheKey)) {
            $data = Request::session()->get($cacheKey);
            /*if($this->m_endDate > new DateTime()) {
                var_dump($data);
                die();
            }*/
        } else {
            $client = new Client();
            $response = $client->request('GET', $_url, $_params);

            $data = $response->getBody()->getContents();
            if($this->m_actualEndDate < new DateTime()) {
                Request::session()->put($cacheKey, $data);
            }
        }
        $result = json_decode($data, true);
        return $result;
    }
}
