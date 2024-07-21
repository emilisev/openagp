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
    private $m_apiVersion = 3;
    private $m_endDate;
    private $m_startDate;

    private string $m_url;
    private $m_urlV1;

    const APIV_1 = "APIV1";

    public function __construct($_url, $_apiSecret, ?DateTime $_startDate, ?DateTime $_endDate) {

        $this->m_url = $this->m_urlV1 = $_url;
        if(!str_ends_with($this->m_url, '/')) {
            $this->m_url .= '/';
        }
        //api v1
        if(!empty($_apiSecret)) {
            $this->m_urlV1 = str_replace('//', '//'.$_apiSecret.'@', $this->m_url);
            $this->m_apiSecretV1 = sha1($_apiSecret);
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
        try {
            return $this->fetchEntriesV3();
        } catch (\Exception $exception) {
            if($exception->getMessage() == self::APIV_1) {
                return $this->fetchEntriesV1();
            }
            throw $exception;
        }
    }

    public function fetchDeviceStatus() {
        if($this->m_apiVersion != 3) {
            return [];
        }
        //never use cache - too much data to store in session
        return $this->fetchDeviceStatusV3(true);
    }

    public function fetchTreatments($_forceRefresh = false) {
        try {
            return $this->fetchTreatmentsV3($_forceRefresh);
        } catch (\Exception $exception) {
            if($exception->getMessage() == self::APIV_1) {
                return $this->fetchTreatmentsV1();
            }
            throw $exception;
        }
    }

    public function searchNotes($_notes) {
        $searchString = '('.strtolower($_notes[0]).'|'.strtoupper($_notes[0]).')'.substr($_notes, 1);
        try {
            $this->openSession();
        } catch (\Exception $exception) {
            if($exception->getMessage() == self::APIV_1) {
                throw new \Exception(__("Recherche dans les notes impossible avec cette version de Nightscout"));
            }
        }
        $url = $this->m_url.'api/v3/treatments';
        $params = [
            'query' => [
                'notes$re' => $searchString,
                'sort$desc' => 'srvCreated',
                'fields' => 'date,timestamp,srvCreated',
                'limit' => 10,
            ],
        ];
        $rawResult = $this->getDataFromUrl($url, $params);
        /*echo "<pre>";
        var_dump($params, $rawResult);
        foreach($rawResult['result'] as $res) {
            var_dump(readableDate($res['timestamp']));
        }
        die();*/
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

    private function fetchDeviceStatusV3(bool $_forceRefresh) {
        return $this->fetchCollectionV3('devicestatus', $_forceRefresh);
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
        if(!empty($this->m_apiSecretV1)) {
            $params['headers'] = [
                'API-SECRET' => $this->m_apiSecretV1,
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
        if(!empty($this->m_apiSecretV1)) {
            $params['headers'] = [
                'API-SECRET' => $this->m_apiSecretV1,
            ];
        }
        $result = $this->getCacheOrLive($url, $params);
        $params['query'] = [
            'find[created_at][$lte]' => $this->m_startDate->format('Y-m-d'),
            'sort$desc' => 'created_at',
            'find[profileJson][$gt]' => '0',
            'count' => 1,
        ];
        $lastProfileSwitchBeforeStart = $this->getCacheOrLive($url, $params);
        $result = array_merge($result, $lastProfileSwitchBeforeStart);
        return $result;
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
                'created_at$lte' => $this->m_actualStartDate->format('U'),
                'sort$desc' => 'created_at',
                'limit' => 1,
                'profileJson$re' => 'units',
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
            $fields = ($_collection == 'entries' ? 'date,sgv,mbg' : /*'timestamp,srvCreated,'.
            'created_at,pumpType,enteredBy,insulin,rate,durationInMilliseconds,duration,insulinInjections,'.
            'notes,carbs,identifier,date,eventType,profileJson,profile,percentage'*/'_all');
            $params = [
                'query' => [
                    $dateField.'$gte' => $currentDate->format('U'),
                    'fields' => $fields,
                    'sort' => $dateField,
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
            if($_collection == 'devicestatus') {
                foreach($rawResult as &$entry) {
                    unset($entry['openaps']['suggested']);
                }
                unset($entry);
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
        array_walk($result, function(&$_item) {
            if(array_key_exists('_id', $_item) && !array_key_exists('identifier', $_item)) {
                $_item['identifier'] = $_item['_id'];
            }
        });
        return $result;
    }

    /**
     * @param string $_url
     * @param array $_params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getDataFromUrl(string $_url, array $_params): mixed {
        if(!empty($this->m_token)) {
            $_params['headers'] = [
                'Authorization' => "Bearer $this->m_token",
            ];
        }
        $client = new Client();
        $response = $client->request('GET', $_url, $_params);

        $data = $response->getBody()->getContents();
        $rawResult = json_decode($data, true);
        return $rawResult;
    }

    private function openSession(): void {
        if(!empty($this->m_token)) {
            return;
        }
        //api v3 - get token
        $client = new Client();
        ServerTiming::start('Nightscout');
        try {
            $response = $client->request('GET', $this->m_url.'api/v2/authorization/request/'.$this->m_apiSecret);
            $data = json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $exception) {
            if(in_array($exception->getCode(), [404, 401])) {
                $this->m_apiVersion = 1;
                throw new \Exception(self::APIV_1, 1);
            } else {
                throw $exception;
            }
        }
        ServerTiming::stop('Nightscout');
        $this->m_token = $data['token'];
    }
}

function readableDate($_time) {
    //return $_time;
    return $_time.' ('.date('Y-m-d H:i', $_time / 1000).')';
}
