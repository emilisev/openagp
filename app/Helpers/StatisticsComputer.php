<?php

namespace App\Helpers;

class StatisticsComputer {

    public function computeAverage($_data, $_spanInSeconds, $_startPoint = null) {
        $arrayValues = $this->gatherDataByTimespan($_spanInSeconds, $_data, $_startPoint);
        $result = [];
        foreach($arrayValues as $key => $values) {
            $result[$key] = round(array_sum($values) / count($values) *10)/10;
        }
        return $result;
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
