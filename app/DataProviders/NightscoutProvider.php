<?php

namespace App\DataProviders;

use BeyondCode\ServerTiming\Facades\ServerTiming;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response;
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

    private string $m_url;
    private $m_urlV1;

    public function __construct($_url, $_apiSecret, ?DateTime $_startDate, ?DateTime $_endDate) {

        $this->m_url = $_url;
        if(!str_ends_with($this->m_url, '/')) {
            $this->m_url .= '/';
        }
        //api v1
        if(!empty($_apiSecret)) {
            $this->m_urlV1 = str_replace('//', '//'.$_apiSecret.'@', $this->m_url);
        }
        $this->m_apiSecret = $_apiSecret;
        if(!empty($_startDate) && !empty($_endDate)) {
            $this->m_startDate = clone($_startDate);
            $this->m_endDate = clone($_endDate);
            $this->m_actualEndDate = $_endDate;
            $this->m_actualStartDate = $_startDate;

            //add 1 day before and after to recompute with time offset
            $this->m_startDate->sub(new \DateInterval('P1D'));
            $this->m_endDate->add(new \DateInterval('P1D'));
        }
    }

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    public function fetchEntries() {
        return $this->fetchEntriesV3();
    }

    public function fetchTreatments($_forceRefresh = false) {
        return $this->fetchTreatmentsV3($_forceRefresh);
    }

    public function searchNotes($_notes) {
        $this->openSession();
        $url = $this->m_url.'api/v3/treatments';
        $params = [
            'query' => [
                'notes$re' => $_notes,
                'sort$desc' => 'srvCreated',
                'fields' => 'timestamp,srvCreated',
                'limit' => 10,
            ],
        ];
        if(!empty($this->m_token)) {
            $params['headers'] = [
                'Authorization' => "Bearer $this->m_token",
            ];
        }
        $client = new Client();
        $response = $client->request('GET', $url, $params);

        $data = $response->getBody()->getContents();
        $rawResult = json_decode($data, true);
        /*echo "<pre>";
        var_dump($params, $rawResult);*/
        return $rawResult['result'];
    }

    public function setNullTreatment($_identifier) {
        $this->openSession();
        $client = new Client();
        $file = fopen(__DIR__.'/log.txt', 'w');
        $params = [
            'debug' => $file, 'json' => [
                "insulin"=> null,
                "enteredBy"=>"openAgp"
            ],
            'headers' => [
                'Authorization' => "Bearer $this->m_token"
            ]
        ];
        $response = $client->request('PATCH', $this->m_url.'api/v3/treatments/'.$_identifier, $params);

        $data = $response->getBody()->getContents();

    }

    private function fetchEntriesV1() {
        $url = $this->m_urlV1.'api/v1/entries.json';
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
        $result = $this->getCacheOrLive($url, $params);
        return($result);

    }

    private function fetchEntriesV3() {
        return $this->fetchCollectionV3('entries');
    }

    private function fetchTreatmentsV1() {
        $url = $this->m_urlV1.'api/v1/treatments.json';

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


    private function fetchTreatmentsV3($_forceRefresh = false) {
        $result = $this->fetchCollectionV3('treatments', $_forceRefresh);
        if(empty($result)) {
            return $result;
        }
        $this->openSession();
        $url = $this->m_url.'api/v3/treatments';
        $params = [
            'query' => [
                'date$lte' => $this->m_actualStartDate->format('U'),
                'sort$desc' => 'date',
                'limit' => 1,
                'eventType' => 'Profile Switch',
            ],
        ];
        $params['headers'] = [
            'Authorization' => "Bearer $this->m_token",
        ];
        $lastProfileSwitchBeforeStart = $this->getCacheOrLive($url, $params);
        $result = array_merge($result, $lastProfileSwitchBeforeStart);
        return $result;
    }

    private function fetchCollectionV3($_collection, $_forceRefresh = false) {
        //echo '<pre>';
        $url = $this->m_url.'api/v3/'.$_collection;
        $currentDate = clone($this->m_actualStartDate);
        $rawResults = [];
        $result = [];
        $pool = [];
        $cacheKeys = [];
        while($currentDate < $this->m_actualEndDate) {
            $dateField = ($_collection == 'entries'?'date': 'created_at');
            $fields = ($_collection == 'entries' ? 'date,sgv,mbg' : 'timestamp,srvCreated,'.
            'created_at,pumpType,enteredBy,insulin,rate,durationInMilliseconds,duration,insulinInjections,'.
            'notes,carbs,identifier,date,eventType,profileJson');
            $params = [
                'query' => [
                    $dateField.'$gte' => $currentDate->format('U'),
                    'fields' => $fields,
                    'limit' => 400,
                ],
            ];
            $currentDate->add(new \DateInterval('P1D'));
            $cacheKey = (!$_forceRefresh && $currentDate < new DateTime())?sha1($url.json_encode($params['query'])):null;
            if(!empty($cacheKey) && Request::session()->has($cacheKey)) {
                $rawResults[] = Request::session()->get($cacheKey);
            } else {
                $this->openSession();
                if(!empty($this->m_token)) {
                    $params['headers'] = [
                        'Authorization' => "Bearer $this->m_token",
                    ];
                }

                //var_dump(http_build_query($params['query']));
                $pool[] = new Psr7Request('GET', $url.'?'.http_build_query($params['query']), $params['headers']);
                $cacheKeys[] = $cacheKey;
            }
        }
        //echo '<pre>';
        $options = array(
            'concurrency' => 5,
            'fulfilled' => function (Response $response, $index) use(&$rawResults, $cacheKeys) {
                $contents = $response->getBody()->getContents();
                if(!empty($cacheKeys[$index])) {
                    Request::session()->put($cacheKeys[$index], $contents);
                }
                $rawResults[] = $contents;
            },
            'rejected' => function ($exception) {
                var_dump('rejected', $exception->getMessage());
            }
        );
        //var_dump("parallel $_collection: ", count($pool));
        if(!empty($pool)) {
            ServerTiming::start('Nightscout');
            $guzzlePool = new Pool(new Client(), $pool, $options);
            $promise = $guzzlePool->promise();
            $promise->wait();
            ServerTiming::stop('Nightscout');
        }

        foreach($rawResults as $rawResult) {
            //var_dump($rawResult);
            $rawResult = json_decode($rawResult, true);
            if(array_key_exists('result', $rawResult)) {
                $rawResult = $rawResult['result'];
            }
            $result = array_merge($result, $rawResult);
        }
        //echo '</pre>';
        return($result);

    }


    private function getCacheOrLive($_url, $_params) {
        $cacheKey = sha1($_url.json_encode($_params['query']));
        //var_dump($_url, $this->m_endDate > new DateTime(), Request::session()->has($cacheKey), $cacheKey);
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
                //var_dump("put $cacheKey");
            }
        }
        $result = json_decode($data, true);
        if(array_key_exists('result', $result)) {
            $result = $result['result'];
        }
        return $result;
    }

    private function openSession(): void {
        if(!empty($this->m_token)) {
            return;
        }
        //api v3 - get token
        $client = new Client();
        ServerTiming::start('Nightscout');
        $response = $client->request('GET', $this->m_url.'api/v2/authorization/request/'.$this->m_apiSecret);
        $data = json_decode($response->getBody()->getContents(), true);
        ServerTiming::stop('Nightscout');
        $this->m_token = $data['token'];
    }
}
