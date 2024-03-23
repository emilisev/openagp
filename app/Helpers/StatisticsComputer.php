<?php

namespace App\Helpers;

use App\Models\DiabetesData;

class StatisticsComputer {

    public function computeAverage($_data, $_spanInSeconds, $_startPoint = null) {
        $arrayValues = $this->gatherDataByTimespan($_spanInSeconds, $_data, $_startPoint);
        $result = [];
        foreach($arrayValues as $key => $values) {
            $result[$key] = round(array_sum($values) / count($values) *10)/10;
        }
        return $result;
    }

	public function computeBasalSum($_data, $_spanInSeconds, $_startPoint = null) {
        $times = array_keys($_data);
        foreach($times as $key => $time) {
            if(array_key_exists($key+1, $times)) {
                $duration = ($times[$key + 1] - $time) / DiabetesData::__1MINUTE / 60;
                $_data[$times[$key]] = $_data[$times[$key]] * $duration;
            } else {
                unset($_data[$times[$key]]);
            }
        }
        return self::computeSum($_data, $_spanInSeconds, $_startPoint);
	}


	public function computeSum($_data, $_spanInSeconds, $_startPoint = null) {
        $arrayValues = $this->gatherDataByTimespan($_spanInSeconds, $_data, $_startPoint);
        $result = [];
        foreach($arrayValues as $key => $values) {
            $result[$key] = array_sum($values);
        }
        return $result;
    }

    /**
     * @param $_spanInSeconds
     * @param $_data
     * @return array
     */
    private function gatherDataByTimespan($_spanInSeconds, $_data, $_startPoint = null): array {
        $span = $_spanInSeconds * 1000;
        $arrayValues = [];
        $startPoint = $_startPoint??array_key_first($_data);
        foreach($_data as $key => $value) {
            if(is_numeric($value)) {
                $newKey = $startPoint + (floor(($key - $startPoint) / $span) * $span);
                $arrayValues[$newKey][] = $value;
            }
        }
        return $arrayValues;
    }
}
