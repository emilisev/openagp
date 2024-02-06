<?php

namespace App\Models;

use DateTime;

class DiabetesData {

    private int $m_agpStepInMinutes;

    private $m_analyzedCarbs;

    private int $m_average;

    private int $m_begin;

    private array $m_bloodGlucoseData = [];

    private array $m_bloodGlucoseDataByRoundedTime;

    private float $m_cgmActivePercent;

    private $m_dailyTimeInRange;

    private $m_dailyTimeInRangePercent;

    private array $m_diabetesAgpData;

    private int $m_end;

    /**
     * glucose management indicator
     */
    private float $m_gmi;

    private bool $m_hasBasalTreatment = false;

    private array $m_insulinAgpData = [];

    private array $m_rawData;

    /**
     * @var array{
     *     veryHigh: int,
     *     veryLow: int,
     *     high: int,
     *       low : int
     *     }
     */
    private array $m_targets;

    private array $m_timeInRangePercent = [];

    private array $m_treatmentsData = ['insulin' => [], 'carbs' => [], 'notes' => []];

    /**
     * @var int
     */
    private int $m_utcOffset;

    private float $m_variation;

    const __1MINUTE = 60 * 1000;

    const __1SECOND = 1000;

    public function __construct($_rawData, $_utcOffset = 0) {
        $this->m_rawData = $_rawData;
        $this->m_utcOffset = $_utcOffset;
    }

    /********************** PUBLIC METHODS *********************/
    public function computeAverage(): void {
        if(empty($this->m_bloodGlucoseData)) {
            return;
        }
        $this->m_average = array_sum($this->m_bloodGlucoseData) / count($this->m_bloodGlucoseData);
        $gmi = 3.31 + 0.02392 * $this->m_average;
        $this->m_gmi = round($gmi * 10) / 10;
        $this->m_variation = $this->computeStandardDeviation($this->m_bloodGlucoseData) / $this->m_average * 100;
        $potentialDataCount = floor(($this->m_end - $this->m_begin) / (60 * 5));
        /*echo "<pre>";
        var_dump(readableTimeArray($this->m_bloodGlucoseData), $potentialDataCount);*/
        $this->m_cgmActivePercent = count($this->m_bloodGlucoseData) * 100 / $potentialDataCount;
    }

    public function computeBloodGlucoseAgp(): void {
        $incrementInSeconds = $this->m_agpStepInMinutes * 60;
        $dataByIncrement = [];
        $dataByCentile = [];
        $step = 0;
        foreach($this->m_bloodGlucoseData as $microDate => $value) {
            $date = $microDate / self::__1SECOND;
            $timeInDay = (date('H', $date) * 60 * 60) + (date('i', $date) * 60) + date('s', $date);
            $step = floor($timeInDay / $incrementInSeconds) * $incrementInSeconds;
            /*if(!array_key_exists($step, $dataByIncrement)) {
                var_dump(date('Y-m-d H:i:s', $date), $timeInDay, $step);
                echo '<br/>';
            }*/
            $dataByIncrement[$step * self::__1SECOND][] = $value;
        }
        ksort($dataByIncrement);
        foreach($dataByIncrement as $step => $values) {
            sort($values);
            $dataByCentile[5][$step] = $values[max(0, floor(count($values) * 0.05) - 1)];
            $dataByCentile[25][$step] = $values[max(0, floor(count($values) * 0.25) - 1)];
            $dataByCentile[50][$step] = $values[max(0, floor(count($values) * 0.50) - 1)];
            $dataByCentile[75][$step] = $values[max(0, floor(count($values) * 0.75) - 1)];
            $dataByCentile[95][$step] = $values[max(0, floor(count($values) * 0.95) - 1)];
        }
        $step += $incrementInSeconds * self::__1SECOND;
        foreach($dataByCentile as $centile => $data) {
            $dataByCentile[$centile][$step] = @$data[0];
        }
        $this->m_diabetesAgpData = $dataByCentile;
    }

    /* * * * * * * * * * * * * * * * * * * * * * PUBLIC METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    public function computeInsulinAgp(int $_minutesBetweenInjections): void {
        //echo "<pre>";
        $secondsBetweenInjections = $_minutesBetweenInjections * 60;
        foreach($this->getTreatmentsData()['insulin'] as $insulinType => $treatments) {
            $treatmentsCountBetweenTimeFrame = $this->getInjectionsCountByTimespan($secondsBetweenInjections, $treatments);
            //construit un tableau retenant les injections regroupées selon les plus fréquentes
            $timesToKeep = [];
            $remainingTreatments = $treatments;
            //var_dump('init', $insulinType, $treatmentsByTimeInDay, $remainingTreatments);
            while(!empty($treatmentsCountBetweenTimeFrame)) {
                arsort($treatmentsCountBetweenTimeFrame);
                $timeToKeep = array_key_first($treatmentsCountBetweenTimeFrame);
                $start = $timeToKeep - ($secondsBetweenInjections * self::__1SECOND / 2);
                $end = $timeToKeep + ($secondsBetweenInjections * self::__1SECOND / 2);

                /*echo '<hr/><pre>';
                var_dump(readableTime($start), readableTime($end));*/
                $treatmentsInTimeFrame = array_filter(
                    $remainingTreatments,
                    function($_date) use ($start, $end) {
                        $timeInDay = ((date('H', $_date / self::__1SECOND) * 60 * 60)
                                + (date('i', $_date / self::__1SECOND) * 60)) * self::__1SECOND;
                        return ($timeInDay >= $start && $timeInDay <= $end);
                    },
                    ARRAY_FILTER_USE_KEY
                );
                /*var_dump('treatmentsInTimeFrame', readableDateArray($remainingTreatments), readableTimeArray($treatmentsInTimeFrame));
                die();*/
                $timesToKeep[$timeToKeep] = ['count' => $treatmentsCountBetweenTimeFrame[$timeToKeep],
                    'from' => readableTime($start),
                    'to' => readableTime($end),
                    'treatments' => readableTimeArray($treatmentsInTimeFrame),
                    'avg' => round(array_sum($treatmentsInTimeFrame) / count($treatmentsInTimeFrame) * 10) / 10,
                    'frequency' => count($treatmentsInTimeFrame)];

                /*echo '<hr/>';
                var_dump(readableTime($start), readableTime($end), 'avant', readableTimeArray($remainingTreatments));
                die();*/

                /*$remainingTreatments = array_filter($remainingTreatments,
                    function($_date) use($start, $end) {
                        $timeInDay = (date('H', $_date) * 60 * 60) + (date('i', $_date) * 60);
                        return ($timeInDay < $start || $timeInDay > $end);
                    },
                    ARRAY_FILTER_USE_KEY
                );*/
                $remainingTreatments = array_diff_key($remainingTreatments, $treatmentsInTimeFrame);
                $treatmentsCountBetweenTimeFrame = $this->getInjectionsCountByTimespan($secondsBetweenInjections, $remainingTreatments);
                /*var_dump('inclus', readableTimeArray($treatmentsInTimeFrame),
                    'reste', readableTimeArray($remainingTreatments),
                'new treatmentsCountBetweenTimeFrame', readableTimeArray($treatmentsCountBetweenTimeFrame));*/

            }
            $this->m_insulinAgpData[$insulinType] = $timesToKeep;
        }
    }

    public function computeTimeInRange(): void {
        if(empty($this->m_bloodGlucoseData)) {
            return;
        }
        $timeInRangeCount = ['veryLow' => 0, 'low' => 0, 'target' => 0, 'high' => 0, 'veryHigh' => 0];
        foreach($this->m_bloodGlucoseData as $value) {
            if($value === null) {
                continue;
            }
            if($value > $this->m_targets['low'] && $value < $this->m_targets['high']) {
                $timeInRangeCount['target']++;
            } elseif($value <= $this->m_targets['veryLow']) {
                $timeInRangeCount['veryLow']++;
            } elseif($value >= $this->m_targets['veryHigh']) {
                $timeInRangeCount['veryHigh']++;
            } elseif($value <= $this->m_targets['low']) {
                $timeInRangeCount['low']++;
            } elseif($value >= $this->m_targets['high']) {
                $timeInRangeCount['high']++;
            }
        }
        $countValues = array_sum($timeInRangeCount);
        //echo "<pre>"; var_dump($timeInRangeCount, count($this->m_bloodGlucoseData));
        $this->m_timeInRangePercent = [
            'veryHigh' => $timeInRangeCount['veryHigh'] * 100 / $countValues,
            'high' => $timeInRangeCount['high'] * 100 / $countValues,
            'target' => $timeInRangeCount['target'] * 100 / $countValues,
            'low' => $timeInRangeCount['low'] * 100 / $countValues,
            'veryLow' => $timeInRangeCount['veryLow'] * 100 / $countValues,
        ];

    }

    public function filter($_begin, $_end): void {
        //echo '<pre>';
        $this->m_begin = $_begin;
        $this->m_end = $_end;
        $this->m_bloodGlucoseData = self::filterData($this->m_bloodGlucoseData, $_begin, $_end);
        ksort($this->m_bloodGlucoseData);
        //extrapolate missing data or set null to prevent straight lignes in graph
        $countSinceLastNull = 0;
        foreach($this->m_bloodGlucoseData as $time => $value) {
            $countSinceLastNull++;
            if(isset($previousTime) && $time - (15 * self::__1MINUTE) > $previousTime) {
                $this->m_bloodGlucoseData[$previousTime + round(($time - $previousTime) / 2)] = null;
                if($countSinceLastNull < 2) {
                    $this->m_bloodGlucoseData[$previousTime + self::__1SECOND] = $this->m_bloodGlucoseData[$previousTime];
                }
                $countSinceLastNull = 0;
            } elseif(isset($previousTime) && $time - (9 * self::__1MINUTE) > $previousTime) {
                $this->m_bloodGlucoseData[$previousTime + round(($time - $previousTime) / 2)] =
                    ($value + $this->m_bloodGlucoseData[$previousTime]) / 2;
            }
            $previousTime = $time;
        }
        ksort($this->m_bloodGlucoseData);
        foreach($this->m_treatmentsData['insulin'] as $insulinType => $data) {
            //var_dump($insulinType, readableDateArray($data), readableDate($_begin * self::__1SECOND), readableDate($_end * self::__1SECOND));
            $this->m_treatmentsData['insulin'][$insulinType] = self::filterData($data, $_begin, $_end);
            //var_dump($this->m_treatmentsData[$insulinType]);
        }

        $this->m_treatmentsData['carbs'] = self::filterData($this->m_treatmentsData['carbs'], $_begin, $_end);

        /*echo "<pre>";
        var_dump(readableDateArray($this->m_treatmentsData['insulin']["Novorapid"]));*/
        /*var_dump($this->m_treatmentsData);
        die();*/
    }

    static function filterData($_data, $_beginTimestamp, $_endTimestamp): array {
        $_beginTimestamp *= self::__1SECOND;
        $_endTimestamp *= self::__1SECOND;
        return array_filter(
            $_data, function($_key) use ($_beginTimestamp, $_endTimestamp) {
            return $_key >= $_beginTimestamp && $_key <= $_endTimestamp;
        }, ARRAY_FILTER_USE_KEY);
    }

    public function getAgpData(): array {
        return $this->m_diabetesAgpData;
    }

    public function getAnalyzedCarbs() {
        if(is_null($this->m_analyzedCarbs)) {
            $this->computeAnalyzedCarbs();
        }
        return $this->m_analyzedCarbs;
    }

    public function getAverage(): int {
        return $this->m_average;
    }

    public function getBegin(): int {
        return $this->m_begin;
    }

    public function getBloodGlucoseData(): array {
        return $this->m_bloodGlucoseData;
    }

    public function getCgmActivePercent(): float {
        return $this->m_cgmActivePercent;
    }

    public function getDailyDataByMonth(int $_monthBackCount) {
        list($minDate, $middleDate) = $this->computeDatesForMonth($_monthBackCount);
        /*echo "<hr/>";
        var_dump($minDate->format('Y-m-d H:i:s'), $middleDate->format('Y-m-d H:i:s'));*/
        return DiabetesData::filterData($this->m_bloodGlucoseDataByRoundedTime, $minDate->format('U'), $middleDate->format('U'));
    }

    public function getDailyDataByWeek(int $_weekBackCount) {
        list($minDate, $middleDate) = $this->computeDatesForWeek($_weekBackCount);
        /*echo "<hr/>";
        var_dump($minDate->format('Y-m-d H:i:s'), $middleDate->format('Y-m-d H:i:s'));*/
        return DiabetesData::filterData($this->m_bloodGlucoseDataByRoundedTime, $minDate->format('U'), $middleDate->format('U'));
    }

    public function getDailyTimeInRange() {
        if(is_null($this->m_dailyTimeInRange)) {
            $this->computeDailyTimeInRange();
        }
        return $this->m_dailyTimeInRange;
    }

    public function getDailyTimeInRangePercent() {
        if(is_null($this->m_dailyTimeInRangePercent)) {
            $this->computeDailyTimeInRangePercent();
        }
        return $this->m_dailyTimeInRangePercent;
    }

    public function getDailyTreatmentsByMonth(int $_monthBackCount) {
        list($minDate, $middleDate) = $this->computeDatesForMonth($_monthBackCount);
        $result = $this->filterTreatementsData($minDate, $middleDate);
        return $result;
    }

    public function getDailyTreatmentsByWeek(int $_weekBackCount) {
        list($minDate, $middleDate) = $this->computeDatesForWeek($_weekBackCount);
        $result = $this->filterTreatementsData($minDate, $middleDate);
        /*echo "<hr/>";
        var_dump($result, $minDate->format('Y-m-d H:i:s'), $middleDate->format('Y-m-d H:i:s'));*/
        return $result;
    }

    public function getEnd(): int {
        return $this->m_end;
    }

    public function getGmi(): float {
        return $this->m_gmi;
    }

    public function getInsulinAgpData(): array {
        if(empty($this->m_insulinAgpData)) {
            $this->computeInsulinAgp(config('diabetes.agp.insulin.minutesBetweenInjections'));
        }
        return $this->m_insulinAgpData;
    }

    public function getTargets(): array {
        return $this->m_targets;
    }

    public function getTimeInRangePercent(): array {
        return $this->m_timeInRangePercent;
    }

    public function getTreatmentsData(): array {
        return $this->m_treatmentsData;
    }

    public function getVariation(): float {
        return $this->m_variation;
    }

    /**
     * @return bool
     */
    public function hasBasalTreatment(): bool {
        return $this->m_hasBasalTreatment;
    }

    public function parse(): void {
        //echo "<pre>";
        foreach($this->m_rawData['bloodGlucose'] as $item) {
            //erase duplicates by rounding to minute + transform timestamp to microTimestamp
            $microTimestamp = floor(($item["date"] + $this->m_utcOffset) / (5 * self::__1MINUTE)) * (5 * self::__1MINUTE);
            /*if(array_key_exists($microTimestamp, $this->m_bloodGlucoseData)) {
                var_dump(
                    "override value", readableTime($microTimestamp),
                    readableTime($item["date"] + $this->m_utcOffset));
            }*/
            if(array_key_exists("sgv", $item) && is_int($item["sgv"])) {
                //filter values < 5 - ex Perrine DF 2023-12-29
                if($item["sgv"] > 5) {
                    $this->m_bloodGlucoseData[$microTimestamp] = $item["sgv"];
                }
            } elseif(array_key_exists("mbg", $item) && is_int($item["mbg"])) {
                $this->m_bloodGlucoseData[$microTimestamp] = $item["mbg"];
            } /*else {
                echo "<br/><br/><br/>";
                var_dump($item);
            }*/
        }
        //echo "<pre>";
        //var_dump($this->m_rawData['treatments']);
        foreach($this->m_rawData['treatments'] as $item) {
            //compute timestamp from various possibilities
            $timestamp = null;
            if(array_key_exists("timestamp", $item)) {
                $timestamp = $item["timestamp"];
            } elseif(array_key_exists("srvCreated", $item)) {
                $timestamp = $item["srvCreated"];
            } elseif(array_key_exists("created_at", $item)) {
                $date = DateTime::createFromFormat('Y-m-d\TH:i:s.v\Z', $item["created_at"]);
                $timestamp = $date->format('Uv');
            }
            if(is_null($timestamp)) {
                continue;
            }

            //fetch possible data : insulin, carbs, notes
            if(@$item["pumpType"] == "OMNIPOD_DASH"
                || @$item["enteredBy"] == "freeaps-x"
                || strpos(@$item["enteredBy"], 'medtronic') === 0) { //pumps
                $this->m_hasBasalTreatment = true;
                $type = $item["eventType"];
                if(array_key_exists("insulin", $item) && is_numeric($item["insulin"])) {
                    $this->m_treatmentsData['insulin'][$type][$timestamp] = $item["insulin"];
                } elseif(array_key_exists("rate", $item) && is_numeric($item["rate"])) {
                    $this->m_treatmentsData['insulin'][$type][$timestamp] = $item["rate"];
                }/* else {
                    var_dump($item);
                }*/
                if(array_key_exists("durationInMilliseconds", $item)) { //omnipod
                    $this->m_treatmentsData['insulinDuration'][$type][$timestamp] = $item["durationInMilliseconds"];
                } elseif(array_key_exists("duration", $item)) {
                    $this->m_treatmentsData['insulinDuration'][$type][$timestamp] = $item["duration"] * self::__1MINUTE;
                }
            } elseif(array_key_exists("insulin", $item) && is_numeric($item["insulin"])) {
                $type = 'unknown';
                if(!empty($item["insulinInjections"])) { //sync pen in xdrip
                    $details = json_decode($item["insulinInjections"], true);
                    $type = $details[0]['insulin'];
                } elseif(!empty($item["notes"])) { //manually entered in xdrip
                    $type = $item["notes"];
                }
                $this->m_treatmentsData['insulin'][$type][$timestamp] = $item["insulin"];
            }
            if(array_key_exists("carbs", $item) && is_numeric($item["carbs"])) {
                $this->m_treatmentsData['carbs'][$timestamp] = $item["carbs"];
            }
            if(array_key_exists("notes", $item) && !empty($item["notes"])) {
                if(preg_match('/^carb ([0-9]+)g/', $item['notes'], $carbs)) {
                    if(is_numeric($carbs[1])) {
                        $this->m_treatmentsData['carbs'][$timestamp] = (int)$carbs[1];
                        continue;
                    }
                }

                $strings = explode(" → ", $item['notes']);
                foreach($strings as $string) {
                    $filter = false;
                    foreach(config('diabetes.notes.filter') as $filterString) {
                        if(strpos($string, $filterString) !== false) {
                            $filter = true;
                            break;
                        }
                    }
                    if(!$filter) {
                        $this->m_treatmentsData['notes'][$timestamp] = $string;
                    }
                }
            }
        }
        /*echo "<pre>";
        var_dump($this->m_treatmentsData['carbs']);*/
    }

    public function prepareDailyData(int $_increment): void {
        $maxDate = new DateTime();
        $maxDate->setTimestamp($this->m_end);
        $minDate = new DateTime();
        $minDate->setTimestamp($this->m_begin);
        $data = $this->m_bloodGlucoseData;
        $incrementInSeconds = $_increment * 60;
        $dataByRoundedTime = [];
        for($i = $minDate->format('U'); $i <= $maxDate->format('U'); $i += $incrementInSeconds) {
            $dataByRoundedTime[$i] = [];
        }
        foreach($data as $microDate => $value) {
            $date = $microDate / self::__1SECOND;
            $timeInDay = (date('H', $date) * 60 * 60) + (date('i', $date) * 60) + date('s', $date);
            $secondsFromMidnight = floor($timeInDay / $incrementInSeconds) * $incrementInSeconds;
            $midnight = strtotime("midnight", $date);
            $roundedTime = $midnight + $secondsFromMidnight;
            //var_dump(date('Y-m-d H:i:s', $midnight), date('Y-m-d H:i:s', $date), date('Y-m-d H:i:s', $roundedTime));
            if($value === null) {
                $dataByRoundedTime[$roundedTime * self::__1SECOND] = $value;
            } else {
                $dataByRoundedTime[$roundedTime * self::__1SECOND][] = $value;
                //echo "<br/>";
            }
        }
        /*echo "<pre>";
        var_dump(1699693200, date('Y-m-d H:i:s', 1699693200), 1699695000, date('Y-m-d H:i:s', 1699695000));
        var_dump(array_filter($data, function($_key) {
            return $_key >= 1699695000 && $_key <= 1699695000;
        }, ARRAY_FILTER_USE_KEY));*/
        //var_dump($dataByRoundedTime);
        foreach($dataByRoundedTime as &$dataSet) {
            if($dataSet === null) {
                $dataSet = null;
            } elseif(!empty($dataSet)) {
                $dataSet = round(array_sum($dataSet) / count($dataSet));
            } else {
                $dataSet = null;
            }
        }

        $this->m_bloodGlucoseDataByRoundedTime = $dataByRoundedTime;

    }

    public function setAgpStep(int $_agpStepInMinutes) {
        $this->m_agpStepInMinutes = $_agpStepInMinutes;
    }

    public function setTargets(array $_targets): void {
        $this->m_targets = $_targets;
    }

    public function smoothAgp(array $_smoothSpan): void {
        foreach($this->m_diabetesAgpData as $centile => $data) {
            $this->m_diabetesAgpData[$centile] = $this->smoothData($data, $_smoothSpan[$centile]);
        }
    }

    /* * * * * * * * * * * * * * * * * * * * * * PRIVATE METHODS  * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    private function computeAnalyzedCarbs() {
        $carbs = ['meal' => [], 'hypo' => [], 'unknown' => []];
        foreach($this->getTreatmentsData()['carbs'] as $time => $value) {
            $carbDate = new DateTime();
            $carbDate->setTimestamp($time/self::__1SECOND);

            $minDate = clone $carbDate;
            $minDate->modify("-90 minutes");

            $maxDate = clone $carbDate;
            $maxDate->modify("+15 minutes");

            $hasRelatedInsulin = count(array_filter($this->filterTreatementsData($minDate, $maxDate)['insulin']));

            $minDate = clone $carbDate;
            $minDate->modify("-15 minutes");

            $lastBg = last(self::filterData($this->m_bloodGlucoseData, $minDate->format('U'), $carbDate->format('U')));
            $isMeal = $hasRelatedInsulin;
            $isHypo = !$hasRelatedInsulin && $lastBg < 90;
            if(!$hasRelatedInsulin && !$isHypo && $lastBg <= 110) {
                $isHypo = true;
            }
            if(!$hasRelatedInsulin && !$isHypo && $lastBg > 110) {
                $isMeal = true;
            }
            if($isMeal) {
                $carbs['meal'][$time] = $value;
            } elseif($isHypo) {
                $carbs['hypo'][$time] = $value;
            } else {
                $carbs['unknown'][$time] = $value;
            }
        }
        $this->m_analyzedCarbs = $carbs;

    }

    private function computeDailyTimeInRange() {
        if(empty($this->m_bloodGlucoseData)) {
            return;
        }
        $timeInRangeCount = ['target' => [], 'other' => []];
        foreach($this->m_bloodGlucoseData as $time => $value) {
            $day = strtotime("midnight", $time / self::__1SECOND) * self::__1SECOND;
            if(!array_key_exists($day, $timeInRangeCount['target'])) {
                $timeInRangeCount['target'][$day] = 0;
                $timeInRangeCount['other'][$day] = 0;
            }

            if($value > $this->m_targets['low'] && $value < $this->m_targets['high']) {
                $timeInRangeCount['target'][$day]++;
            } else {
                $timeInRangeCount['other'][$day]++;
            }
        }
        $this->m_dailyTimeInRange = $timeInRangeCount;
    }

    private function computeDailyTimeInRangePercent() {
        $timeInRangePercent = ['target' => [], 'other' => []];
        foreach($this->getDailyTimeInRange()['target'] as $time => $timeInRangeCount) {
            $sum = $timeInRangeCount + $this->getDailyTimeInRange()['other'][$time];
            $timeInRangePercent['target'][$time] = $timeInRangeCount / $sum * 100;
            $timeInRangePercent['other'][$time] = $this->getDailyTimeInRange()['other'][$time] / $sum * 100;
        }
        $this->m_dailyTimeInRangePercent = $timeInRangePercent;

    }

    /**
     * @param int $_monthBackCount
     * @return DateTime[]
     */
    private function computeDatesForMonth(int $_monthBackCount): array {
        $days = $_monthBackCount * 30;
        $maxDate = new DateTime();
        $maxDate->setTimestamp($this->m_end);
        $maxDate->modify("midnight + 1day");
        $minDate = clone($maxDate);
        $minDate->modify("-$days days midnight");
        $middleDate = clone($minDate);
        $middleDate->modify('+30 days midnight');
        return array($minDate, $middleDate);
    }

    /**
     * @param int $_weekBackCount
     * @return DateTime[]
     */
    private function computeDatesForWeek(int $_weekBackCount): array {
        $maxDate = new DateTime();
        $maxDate->setTimestamp($this->m_end);
        $maxDate->modify("midnight + 1day");
        $minDate = clone($maxDate);
        $minDate->modify("-$_weekBackCount weeks midnight");
        $middleDate = clone($minDate);
        $middleDate->modify('+1 week midnight');
        return array($minDate, $middleDate);
    }

    private function computeStandardDeviation($_array): float {
        $count = count($_array);
        $variance = 0.0;
        $average = array_sum($_array) / $count;

        foreach($_array as $i) {
            // sum of squares of differences between
            // all numbers and means.
            $variance += pow(($i - $average), 2);
        }

        return sqrt($variance / $count);
    }

    /**
     * @param DateTime $minDate
     * @param DateTime $middleDate
     * @return array
     */
    private function filterTreatementsData(DateTime $minDate, DateTime $middleDate): array {
        $result = [];
        foreach($this->m_treatmentsData['insulin'] as $type => $values) {
            $result['insulin'][$type] = DiabetesData::filterData($values, $minDate->format('U'), $middleDate->format('U'));
        }
        $result['carbs'] = DiabetesData::filterData($this->m_treatmentsData['carbs'], $minDate->format('U'), $middleDate->format('U'));
        $result['notes'] = DiabetesData::filterData($this->m_treatmentsData['notes'], $minDate->format('U'), $middleDate->format('U'));
        return $result;
        /*echo "<hr/>";
        var_dump($result, $minDate->format('Y-m-d H:i:s'), $middleDate->format('Y-m-d H:i:s'));*/
    }

    private function getInjectionsCountByTimespan(int $_secondsBetweenInjections, array $_treatments): array {
        if(empty($_treatments)) {
            return [];
        }
        $treatmentsByTimeInDay = [];
        //construit un tableau avec le nb d'injection pour chaque minute de la journée
        //pondère les injections > 2/3 moyenne pour privilégier les injections réelles aux corrections
        $actualDose = array_sum($_treatments) / count($_treatments) * 2 / 3;
        foreach($_treatments as $date => $value) {
            $timeInDay = (date('H', $date / self::__1SECOND) * 60 * 60) + (date('i', $date / self::__1SECOND) * 60);
            $weight = ($value > $actualDose) ? 2 : 1;
            if(array_key_exists($timeInDay, $treatmentsByTimeInDay)) {
                $treatmentsByTimeInDay[$timeInDay] += $weight;
            } else {
                $treatmentsByTimeInDay[$timeInDay] = $weight;
            }
        }
        //construit un tableau indexé toutes les 30mn,
        //contenant le nb d'injection réalisés dans la plage $_minutesBetweenInjections autour de l'index
        $treatmentsCountBetweenTimeFrame = $treatmentsCountBetweenTimeFrame2 = [];
        for($i = 0; $i < 60 * 60 * 24; $i += 60 * $this->m_agpStepInMinutes) {
            $start = $i - ($_secondsBetweenInjections / 2);
            $end = $i + ($_secondsBetweenInjections / 2);
            /*$entriesInTimeFrame = array_filter($treatmentsByTimeInDay,
                function ($_time) use ($start, $end) {
                    return ($_time >= $start && $_time <= $end);
                },
                ARRAY_FILTER_USE_KEY
            );
            //var_dump("$start / $end ", count($entriesInTimeFrame));
            if(!empty($entriesInTimeFrame)) {
                $countAtI = array_sum($entriesInTimeFrame);
                $treatmentsCountBetweenTimeFrame[$i] = $countAtI;
            }*/
            for($j = $start; $j <= $end; $j++) {
                if(array_key_exists($j, $treatmentsByTimeInDay)) {
                    $proximity = ($_secondsBetweenInjections / 2) - abs($i - $j);
                    $countAtJ = $proximity * $treatmentsByTimeInDay[$j];
                    $key = $i * self::__1SECOND;
                    if(array_key_exists($key, $treatmentsCountBetweenTimeFrame2)) {
                        $treatmentsCountBetweenTimeFrame2[$key] += $countAtJ;
                    } else {
                        $treatmentsCountBetweenTimeFrame2[$key] = $countAtJ;
                    }
                }
            }
        }
        /*echo "<pre>";
        var_dump(readableTimeArray($treatmentsCountBetweenTimeFrame), readableTimeArray($treatmentsCountBetweenTimeFrame2));
        die();*/
        return $treatmentsCountBetweenTimeFrame2;
    }

    private function smoothData($_data, int $_smoothSpan): array {
        $keys = array_keys($_data);
        $smoothData = [];
        foreach($keys as $index => $currentTime) {
            $dataToSmoothOn = [$_data[$currentTime]];
            $i = 0;
            $searchIndex = $index;
            while($i <= $_smoothSpan) {
                $searchIndex--;
                if($searchIndex < 0) {
                    $searchIndex = count($keys) - 1;
                }
                $dataToSmoothOn[] = $_data[$keys[$searchIndex]];
                $i++;
            }
            $i = 0;
            $searchIndex = $index;
            while($i <= $_smoothSpan) {
                $searchIndex++;
                if($searchIndex == count($keys)) {
                    $searchIndex = 0;
                }
                $dataToSmoothOn[] = $_data[$keys[$searchIndex]];
                $i++;
            }
            $smoothData[$currentTime] = array_sum($dataToSmoothOn) / count($dataToSmoothOn);
        }
        return $smoothData;

    }
}

function readableTime($_time) {
    //return $_time;
    $date = strtotime('midnight') + ($_time / 1000);
    return $_time.' ('.date('Y m d H:i', $date).')';
}

function readableDate($_time) {
    //return $_time;
    return $_time.' ('.date('Y-m-d H:i', $_time / 1000).')';
}

function readableTimeArray($_array): array {
    $array = [];
    foreach($_array as $key => $value) {
        $array[readableTime($key)] = $value;
    }
    return $array;
}

function readableDateArray($_array): array {
    $array = [];
    foreach($_array as $key => $value) {
        $array[readableDate($key)] = $value;
    }
    return $array;
}
