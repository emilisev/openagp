<?php

namespace App\Models;

use DateTime;
use App\Helpers\ParserHelper;

class DiabetesData {

    private int $m_agpStepInMinutes;

    private array $m_analyzedCarbs;

    private int $m_average;

    private array $m_batteryInfo = [];

    private int $m_begin;

    private array $m_bloodGlucoseData = [];

    private array $m_bloodGlucoseDataByRoundedTime;

    private float $m_cgmActivePercent;

    private array $m_dailyTimeInRange;

    private array $m_dailyTimeInRangePercent;

    private array $m_diabetesAgpData = [];

    private int $m_end;

    /**
     * glucose management indicator
     */
    private float $m_gmi;

    private bool $m_hasBasalTreatment = false;

    private array $m_insulinAgpData = [];

    private array $m_iobCentileData = [];

    private array $m_profiles = [];

    private array $m_ratiosByLunchType = [];

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

    private array $m_tempBasalRates = [];

    private array $m_timeInRangePercent = [];

    private array $m_treatmentsData = ['insulin' => [], 'carbs' => [], 'notes' => [], 'profileTemp' => []];

    /**
     * @var int
     */
    private int $m_utcOffset;

    private float $m_variation;

    /**
     * @var array[]
     */
    private array $m_weeklyTimeInRangePercent;

    const __1MINUTE = 60 * self::__1SECOND;
    const __1SECOND = 1000;
    const __1DAY    = self::__1MINUTE * 60 * 24;
    const __BOLUS_INSULIN = ['Novorapid', 'Meal Bolus', 'Manual Bolus', 'Correction Bolus'];
    const __PROFILE_PERCENT_UP = ['basal'];
    const __PROFILE_PERCENT_DOWN = ['sens', 'carbratio'];

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
        $data = $this->getDailyTimeInRange();
        foreach($data as $label => $values) {
            $data[$label] = array_sum($values);
        }
        $sumValues = array_sum($data);
        foreach($data as $label => $sumValue) {
            $data[$label] = $sumValue * 100 / $sumValues;
        }
        $this->m_timeInRangePercent = $data;
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
            ksort($this->m_treatmentsData['insulin'][$insulinType]);
            //var_dump($this->m_treatmentsData[$insulinType]);
        }

        $this->m_treatmentsData['carbs'] = self::filterData($this->m_treatmentsData['carbs'], $_begin, $_end);
        $profilesInTimeFrame = self::filterData($this->m_profiles, $_begin, $_end);
        if(empty($profilesInTimeFrame)) {
            $this->m_profiles = array_slice($this->m_profiles, 0, 1, true);
        } else {
            $firstEntry = array_key_first($profilesInTimeFrame);
            $allTimestamps = array_keys($this->m_profiles);
            $previousEntry = null;
            rsort($allTimestamps);
            foreach($allTimestamps as $timestamp) {
                if($timestamp < $firstEntry) {
                    $previousEntry = $timestamp;
                    break;
                }
            }
            if(!is_null($previousEntry)) {
                $profilesInTimeFrame[$previousEntry] = $this->m_profiles[$previousEntry];
                ksort($profilesInTimeFrame);
            }
            $this->m_profiles = $profilesInTimeFrame;
        }
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
        if(empty($this->m_diabetesAgpData)) {
            $this->m_diabetesAgpData = $this->computeDataByCentile($this->m_bloodGlucoseData, $this->m_agpStepInMinutes);
        }
        return $this->m_diabetesAgpData;
    }

    public function getAnalyzedCarbs() {
        if(empty($this->m_analyzedCarbs)) {
            $this->computeAnalyzedCarbs();
        }
        return $this->m_analyzedCarbs;
    }

    public function getAverage(): int {
        return $this->m_average;
    }

    public function getBatteryInfo(): array {
        return $this->m_batteryInfo;
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
        if(empty($this->m_dailyTimeInRange)) {
            $this->computeDailyTimeInRange();
        }
        return $this->m_dailyTimeInRange;
    }

    public function getDailyTimeInRangePercent() {
        if(empty($this->m_dailyTimeInRangePercent)) {
            $this->computeDailyTimeInRangePercent();
        }
        return $this->m_dailyTimeInRangePercent;
    }

    public function getDailyTreatmentsByMonth(int $_monthBackCount) {
        list($minDate, $middleDate) = $this->computeDatesForMonth($_monthBackCount);
        return $this->filterTreatementsData($minDate, $middleDate);
    }

    public function getDailyTreatmentsByWeek(int $_weekBackCount) {
        list($minDate, $middleDate) = $this->computeDatesForWeek($_weekBackCount);
        /*echo "<hr/>";
        var_dump($result, $minDate->format('Y-m-d H:i:s'), $middleDate->format('Y-m-d H:i:s'));*/
        return $this->filterTreatementsData($minDate, $middleDate);
    }

    public function getDurationInDays() {
        return round(($this->getEnd() - $this->getBegin())/(60*60*24));
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

    public function getIobData() {
        if(empty($this->m_iobCentileData) && array_key_exists('iob', $this->m_treatmentsData)) {
            $this->m_iobCentileData = $this->computeDataByCentile($this->m_treatmentsData['iob'], 15);
        }
        return $this->m_iobCentileData;

    }

    /**
     * @return array
     */
    public function getProfiles(): array {
        return $this->m_profiles;
    }

    public function getRatiosByLunchType() {
        if(empty($this->m_ratiosByLunchType)) {
            $this->computeRatios();
        }
        return $this->m_ratiosByLunchType;
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

    public function getUtcOffset(): int {
        return $this->m_utcOffset;
    }

    public function getVariation(): float {
        return $this->m_variation;
    }

    public function getWeeklyTimeInRangePercent() {
        if(empty($this->m_weeklyTimeInRangePercent)) {
            $this->computeWeeklyTimeInRangePercent();
        }
        return $this->m_weeklyTimeInRangePercent;

    }

    /**
     * @return bool
     */
    public function hasBasalTreatment(): bool {
        return $this->m_hasBasalTreatment;
    }

    public function parse($_endDateSeconds): void {
        //echo "<pre>";

        foreach($this->m_rawData['bloodGlucose'] as $item) {
            //erase duplicates by rounding to minute + transform timestamp to microTimestamp
            $microTimestamp = $this->roundToFiveMinutes($item["date"]);
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
        $this->parseTreatments($_endDateSeconds);
        $this->parseDeviceStatus();
        /*echo "<pre>";
        var_dump($this->m_rawData['bloodGlucose'], $this->m_bloodGlucoseData, $this->m_treatmentsData['carbs']);*/

        //echo "</pre>";
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

    /**
     * @param $item
     * @return float|int
     */
    public function roundToFiveMinutes($item) {
        return floor(($item + $this->m_utcOffset) / (5 * self::__1MINUTE)) * (5 * self::__1MINUTE);
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
            $carbDate->setTimestamp($time / self::__1SECOND);

            $minDate = clone $carbDate;
            $minDate->modify("-60 minutes");

            $maxDate = clone $carbDate;
            $maxDate->modify("+2 hours");

            $relatedInsulin = @$this->filterTreatementsData($minDate, $maxDate)['insulin'];
            $hasRelatedInsulin = !empty($relatedInsulin) && count(array_filter($relatedInsulin));

            $minDate = clone $carbDate;
            $minDate->modify("-15 minutes");

            $lastBg = last(self::filterData($this->m_bloodGlucoseData, $minDate->format('U'), $carbDate->format('U')));
            $isMeal = $hasRelatedInsulin && $value > 10;
            $isHypo = !$hasRelatedInsulin && $lastBg < 90 && $value < 20;
            if(!$isMeal && !$isHypo) {
                if($value > 15) {
                    $isMeal = true;
                } elseif($lastBg <= 110) {
                    $isHypo = true;
                } else {
                    $isMeal = true;
                }
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

    private function computeBasalFromProfile($_basalRates, $_tempBasalRates, $_endDateSeconds) {

        ksort($_basalRates);
        /*echo "<pre>";
        var_dump($_endDateSeconds, time());*/
        //var_dump(readableDateArray($_basalRates));
        $times = array_keys($_basalRates);
        /*echo "<pre>";
        var_dump(readableDateArray(array_flip($times)));*/
        foreach($times as $index => $time) {
            //echo "<hr/>***************<hr/>";
            $profile = &$_basalRates[$time];
            $profile['appliesFrom'] = $time;
            if(array_key_exists($index + 1, $times)) {
                $profile['appliesTo'] = $times[$index + 1];
            } elseif($_endDateSeconds > time()) {
                $profile['appliesTo'] = microtime(true) * self::__1SECOND;
            } else {
                $profile['appliesTo'] = strtotime('23:59:59', $_endDateSeconds) * self::__1SECOND;
            }
            $profile['appliesToR'] = readableDate($profile['appliesTo']);
            $profile['appliesFromR'] = readableDate($profile['appliesFrom']);
            /*unset($profile['sens']);
            unset($profile['carbratio']);
            unset($profile['target_low']);
            unset($profile['target_high']);
            $profile['actualInsulin'] = [];*/
            $currentTime = $profile['appliesFrom'];
            $lastTimeToReal = $lastValuePerHour = null;
            while($currentTime < $profile['appliesTo']) {
                foreach($profile['basal'] as $key => $basalDef) {
                    //var_dump("***", $basalDef);
                    $timeFrom = strtotime($basalDef['time'], $currentTime / self::__1SECOND) * self::__1SECOND;
                    //var_dump($basalDef['time'], readableDate($timeFrom), readableDate($time));
                    $timeTo = strtotime($profile['basal'][$key + 1]['time'] ?? '23:59', $currentTime / self::__1SECOND) * self::__1SECOND;
                    if($timeTo < $profile['appliesFrom']) { //ends before profile start
                        //var_dump('ends before profile start');
                        continue;
                    }
                    if($timeFrom > $profile['appliesTo']) { //starts after profile end
                        //var_dump('starts after profile end');
                        continue;
                    }
                    if($timeFrom > microtime(true) * self::__1SECOND) { //starts in the future
                        //var_dump('starts in the future');
                        continue;
                    }
                    $timeFromReal = max($timeFrom, $profile['appliesFrom']);
                    $timeToReal = min($timeTo, $profile['appliesTo'], microtime(true) * self::__1SECOND);
                    //$actualInsulin = sprintf('%.3f', (($timeToReal - $timeFromReal) / self::__1MINUTE / 60 * $basalDef['value']));
                    /*var_dump('appliesFromR', $profile['appliesFromR'],
                             'appliesToR', $profile['appliesToR'],
                             'timeTo', readableDate($timeTo),
                             'timeFromReal', readableDate($timeFromReal),
                             'timeToReal', readableDate($timeToReal));
                    echo "<hr/>";*/
                    /*$profile['actualInsulin'][$timeFromReal] = ['from' => $timeFromReal,
                        'to' => $timeToReal,
                        'valuePerHour' => $basalDef['value'],
                        'actualValue' => $actualInsulin];*/
                    $lastTimeToReal = $timeToReal;
                    $lastValuePerHour = $basalDef['value'];
                    $this->m_treatmentsData['insulin']['basal'][$this->roundToFiveMinutes($timeFromReal)] = round($basalDef['value'] * 100) / 100;
                    $this->m_hasBasalTreatment = true;
                }
                $this->m_treatmentsData['insulin']['basal'][$this->roundToFiveMinutes($lastTimeToReal)] = $lastValuePerHour;
                $currentTime = strtotime('midnight next day', $currentTime / self::__1SECOND) * self::__1SECOND;
            }
        }
        unset($profile);
        if(!array_key_exists('basal', $this->m_treatmentsData['insulin'])) {
            return;
        }
        $basalTimes = array_keys($this->m_treatmentsData['insulin']['basal']);
        //echo "<pre>";
        foreach($_tempBasalRates as $tempBasalTime => $tempBasalRate) {
            /*echo "<hr/>";
            var_dump($tempBasalRate);*/
            foreach($basalTimes as $key => $basalTime) {
                if(array_key_exists($key + 1, $basalTimes)) {
                    if($basalTimes[$key + 1] < $tempBasalTime) { //basalTimes in the past regarding temp basal, discard
                        ;
                    } elseif($tempBasalTime >= $basalTime) {
                        if($tempBasalTime <= $basalTimes[$key + 1]) {
                            $this->m_treatmentsData['insulin']['basal'][$this->roundToFiveMinutes($tempBasalTime)] =
                                round($tempBasalRate['rate'] * 100) / 100;
                            $this->m_treatmentsData['insulin']['basal'][$this->roundToFiveMinutes($tempBasalTime + $tempBasalRate['durationInMilliseconds'])] =
                                $this->m_treatmentsData['insulin']['basal'][$basalTime];
                        }
                        //profile switch during temp basal, what happens ?
                        /*else {
                            var_dump(
                                'basalTime', readableTime($basalTime),
                                'tempBasalTime', readableTime($tempBasalTime),
                                'next basalTime', readableTime($basalTimes[$key + 1]),
                                '******');
                        }*/
                        continue;
                    }
                }
            }
        }
        //var_dump(readableDateArray($this->m_treatmentsData['insulin']['basal']));
    }

    private function computeDailyTimeInRange() {
        if(empty($this->m_bloodGlucoseData)) {
            return;
        }
        $timeInRangeCount = ['total' => []];
        foreach(array_keys(config('diabetes.bloodGlucose.targets')) as $category) {
            $timeInRangeCount[$category] = [];
        }
        foreach($this->m_bloodGlucoseData as $time => $value) {
            $day = strtotime("midnight", $time / self::__1SECOND) * self::__1SECOND;
            foreach(config('diabetes.bloodGlucose.targets') as $category => $maxValue) {
                if($value >= $maxValue) {
                    if (!array_key_exists($day, $timeInRangeCount['total'])) {
                        $timeInRangeCount['total'][$day] = 0;
                    }
                    if (!array_key_exists($day, $timeInRangeCount[$category])) {
                        $timeInRangeCount[$category][$day] = 0;
                    }
                    $timeInRangeCount[$category][$day]++;
                    $timeInRangeCount['total'][$day]++;
                    break;
                }
            }
        }
        /*echo '<pre>';
        var_dump(readableTimeArray($timeInRangeCount));
        die();*/
        $this->m_dailyTimeInRange = $timeInRangeCount;
        $this->computeDailyTimeInRangePercent();
        unset($this->m_dailyTimeInRange['total']);
        unset($this->m_dailyTimeInRangePercent['total']);

    }

    private function computeDailyTimeInRangePercent() {
        $timeInRangePercent = [];
        /*echo '<pre>';
        var_dump(readableDateArray($this->getDailyTimeInRange()['target']), readableDateArray($this->getDailyTimeInRange()['other']));*/
        foreach($this->getDailyTimeInRange() as $category => $days) {
            foreach($days as $day => $value) {
                $sum = $this->getDailyTimeInRange()['total'][$day];
                $timeInRangePercent[$category][$day] = $value / $sum * 100;
            }
        }

        /*echo '<pre>';
        var_dump($timeInRangePercent);
        die();*/
        $this->m_dailyTimeInRangePercent = $timeInRangePercent;
    }

    /**
     * @return array
     */
    private function computeDataByCentile($_data, $_incrementInminutes): array {
        $incrementInSeconds = $_incrementInminutes * 60;
        $dataByIncrement = [];
        $dataByCentile = [];
        $step = 0;
        foreach($_data as $microDate => $value) {
            $date = $microDate / self::__1SECOND;
            $timeInDay = (date('H', $date) * 60 * 60) + (date('i', $date) * 60) + date('s', $date);
            $step = floor($timeInDay / $incrementInSeconds) * $incrementInSeconds;
            /*if(!array_key_exists($step, $dataByIncrement)) {
                var_dump(date('Y-m-d H:i:s', $date), $timeInDay, $step);
                echo '<br/>';
            }*/
            if(!is_null($value)) {
                $dataByIncrement[$step * self::__1SECOND][] = $value;
            }
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
        return $dataByCentile;
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

    private function computeRatios() {
        $dataByLunchType = $ratiosByLunchType = [];
        $rawData = [
            'carbs' => $this->getTreatmentsData()['carbs'],
            'insulin' => $this->getBolusInsulin()
        ];
        //build rawData
        foreach($rawData as $dataType => $data) {
            foreach($data as $time => $value) {
                $eventDate = new DateTime();
                $eventDate->setTimestamp($time / self::__1SECOND);
                $timeInDay = $eventDate->format('Hi');
                $lunchType = null;
                foreach(array_reverse(config('diabetes.lunchTypes'), true) as $maxTime => $type) {
                    if($timeInDay <= $maxTime) {
                        $lunchType = $type;
                        continue;
                    }
                }
                $eventDate->modify('midnight');
                $dataByLunchType[$lunchType][$dataType][$eventDate->format('U') * self::__1SECOND][$time] = $value;
                /*if(count($dataByLunchType[$lunchType][$dataType][$eventDate->format('Ymd')]) > 1) {
                    var_dump($lunchType, $dataType, readableDateArray($dataByLunchType[$lunchType][$dataType][$eventDate->format('Ymd')]));
                }*/
            }
        }
        //sum data
        foreach($dataByLunchType as $lunchType => $dataTypes) {
            foreach($dataTypes as $dataType => $days) {
                foreach($days as $day => $data) {
                    if($dataType == 'carbs') {
                        $mainMealTime = array_search(max($data), $data);
                        $lastBg = last(self::filterData($this->m_bloodGlucoseData, ($mainMealTime - (self::__1MINUTE * 15)) / self::__1SECOND, $mainMealTime / self::__1SECOND));
                        $postPrandialBG = last(self::filterData($this->m_bloodGlucoseData,
                            ($mainMealTime + (self::__1MINUTE * 100)) / self::__1SECOND,
                            ($mainMealTime + (self::__1MINUTE * 120)) / self::__1SECOND));
                        $target = 'unknown';
                        if(is_numeric($lastBg) && is_numeric($postPrandialBG)
                        && $lastBg > config('diabetes.bloodGlucose.targets.low')
                            && $lastBg < config('diabetes.bloodGlucose.targets.high')) {
                            if($postPrandialBG < config('diabetes.bloodGlucose.targets.low')) {
                                $target = 'low';
                            } elseif($postPrandialBG > config('diabetes.bloodGlucose.targets.high')) {
                                $target = 'high';
                            } elseif($postPrandialBG - $lastBg > 50) {
                                $target = 'lightHigh';
                            } else {
                                $target = 'inRange';
                            }
                        }
                        $dataByLunchType[$lunchType]['target'][$day] = $target;

                    }
                    $dataByLunchType[$lunchType][$dataType][$day] = array_sum($data);
                }
            }
        }
        /*echo '<pre>';
        var_dump(readableDateArray($dataByLunchType['lunch']['insulin']));*/
        //compute ratio
        $maxRatio = 0;
        foreach($dataByLunchType as $lunchType => $datumByLunchType) {
            if(array_key_exists('insulin', $datumByLunchType)) {
                foreach($datumByLunchType['insulin'] as $day => $insulinValue) {
                    if(array_key_exists('carbs', $datumByLunchType) && array_key_exists($day, $datumByLunchType['carbs'])) {
                        $ratio = round($datumByLunchType['carbs'][$day] / $insulinValue * 10) / 10;

                        $maxRatio = max($maxRatio, $ratio);
                        $this->m_ratiosByLunchType[$lunchType][] = ['x' => $day,
                            'y' => $ratio,
                            'target' => $dataByLunchType[$lunchType]['target'][$day],
                            'carbs' => $datumByLunchType['carbs'][$day],
                            'insulin' => $insulinValue];
                    }
                }
            }
        }
        $this->m_ratiosByLunchType['maxRatio'] = $maxRatio;
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

    private function computeWeeklyTimeInRangePercent() {
        $timeInRangeByWeek = ['total' => []];
        foreach(array_keys(config('diabetes.bloodGlucose.targets')) as $category) {
            $timeInRangeByWeek[$category] = [];
        }
        for($start = $this->getBegin() * self::__1SECOND; $start <= $this->getEnd() * self::__1SECOND; $start += self::__1DAY * 7) {
            $end = $start + self::__1DAY * 7;
            $timeInRangeByWeek['total'][$start] = 0;
            foreach($this->getDailyTimeInRange() as $category => $values) {
                $valuesInWeek = array_filter(
                    $values,
                    function($_key) use ($start, $end) {
                        return $_key >= $start && $_key < $end;
                    },
                    ARRAY_FILTER_USE_KEY);
                $timeInRangeByWeek[$category][$start] = array_sum($valuesInWeek);
                $timeInRangeByWeek['total'][$start] += $timeInRangeByWeek[$category][$start];
            }
        }
        foreach($timeInRangeByWeek as $category => $weeks) {
            if($category != 'total') {
                foreach($weeks as $week => $value) {
                    $timeInRangeByWeek[$category][$week] = $value * 100 / $timeInRangeByWeek['total'][$week];
                }
            }
        }
        unset($timeInRangeByWeek['total']);
        $this->m_weeklyTimeInRangePercent = $timeInRangeByWeek;


    }

    private function decodeProfile($_item) {
        $result = json_decode($_item["profileJson"], true);
        if(!array_key_exists('percentage', $_item) || $_item['percentage'] == 100) {
            return $result;
        }
        foreach(self::__PROFILE_PERCENT_UP as $itemToUp) {
            foreach($result[$itemToUp] as &$data) {
                $data['value'] = round($data['value'] * $_item['percentage']) /100;
            }
        }
        foreach(self::__PROFILE_PERCENT_DOWN as $itemToUp) {
            foreach($result[$itemToUp] as &$data) {
                $data['value'] = round(($data['value'] / $_item['percentage'] * 1000))/10;
            }
        }
        return $result;
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

    private function getBolusInsulin() {
        $result = array_filter($this->getTreatmentsData()['insulin'],
            function ($key) {
                return in_array($key, self::__BOLUS_INSULIN);
            }, ARRAY_FILTER_USE_KEY);
        if(empty($result)) {
            return [];
        }
        $finalResult = [];
        foreach($result as $insulinData) {
            foreach($insulinData as $time => $value) {
                if(array_key_exists($time, $finalResult)) {
                    $finalResult[$time] += $value;
                } else {
                    $finalResult[$time] = $value;
                }
            }
        }
        return $finalResult;
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
        $treatmentsCountBetweenTimeFrame = [];
        for($i = 0; $i < 60 * 60 * 24; $i += 60 * $this->m_agpStepInMinutes) {
            $start = $i - ($_secondsBetweenInjections / 2);
            $end = $i + ($_secondsBetweenInjections / 2);
            for($j = $start; $j <= $end; $j++) {
                if(array_key_exists($j, $treatmentsByTimeInDay)) {
                    $proximity = ($_secondsBetweenInjections / 2) - abs($i - $j);
                    $countAtJ = $proximity * $treatmentsByTimeInDay[$j];
                    $key = $i * self::__1SECOND;
                    if(array_key_exists($key, $treatmentsCountBetweenTimeFrame)) {
                        $treatmentsCountBetweenTimeFrame[$key] += $countAtJ;
                    } else {
                        $treatmentsCountBetweenTimeFrame[$key] = $countAtJ;
                    }
                }
            }
        }
        /*echo "<pre>";
        var_dump(readableTimeArray($treatmentsCountBetweenTimeFrame), readableTimeArray($treatmentsCountBetweenTimeFrame));
        die();*/
        return $treatmentsCountBetweenTimeFrame;
    }

    private function parseDeviceStatus() {
        foreach($this->m_rawData['deviceStatus'] as $item) {
            //compute timestamp from various possibilities
            $timestamp = ParserHelper::extractTimestamp($item, $this->m_utcOffset);
            if(is_null($timestamp)) {
                continue;
            }
            $timestamp = $this->roundToFiveMinutes($timestamp);
            if(is_float(@$item['openaps']['iob']['activity'])) {
                $this->m_treatmentsData['insulinActivity'][$timestamp] = $item['openaps']['iob']['activity'];
                $this->m_batteryInfo[$timestamp] = $item['uploaderBattery'];
                $this->m_treatmentsData['iob'][$timestamp] = $item['openaps']['iob']['iob'];

            }
        }
    }

    /**
     * @param $item
     * @param $timestamp
     * @return bool isInsulinData?
     */
    private function parseInsulinData($item, $timestamp):bool {
        $isInsulinData = false;
        if(array_key_exists("rate", $item) && is_numeric($item["rate"])) {
            $this->m_hasBasalTreatment = true;
            $this->m_tempBasalRates[$timestamp] = $item;
            $isInsulinData =true;
        } elseif(@$item["pumpType"] == "OMNIPOD_DASH" //fetch possible data : insulin, carbs, notes
            || @$item["enteredBy"] == "freeaps-x"
            || strpos(@$item["enteredBy"], 'medtronic') === 0) { //pumps
            $type = $item["eventType"];
            if(array_key_exists("insulin", $item) && is_numeric($item["insulin"])) {
                $this->m_treatmentsData['insulin'][$type][$timestamp] = round($item["insulin"] * 100) / 100;
            }/* else {
                    var_dump($item);
                }*/
            if(array_key_exists("durationInMilliseconds", $item)) { //omnipod
                $this->m_treatmentsData['insulinDuration'][$type][$timestamp] = $item["durationInMilliseconds"];
                $isInsulinData =true;
            } elseif(array_key_exists("duration", $item)) {
                $this->m_treatmentsData['insulinDuration'][$type][$timestamp] = $item["duration"] * self::__1MINUTE;
                $isInsulinData =true;
            }
        } elseif(array_key_exists("insulin", $item) && is_numeric($item["insulin"])) {
            $type = 'unknown';
            if(!empty($item["insulinInjections"])
                && ($details = json_decode($item["insulinInjections"], true))
                && !(empty($details))) { //sync pen in xdrip
                $type = @$details[0]['insulin'];
            } elseif(!empty($item["notes"])) { //manually entered in xdrip
                $type = $item["notes"];
                unset($item["notes"]);
            } elseif(!empty($item["eventType"])) { //manually entered in xdrip
                $type = $item["eventType"];
            }
            $this->m_treatmentsData['insulin'][$type][$timestamp] = round($item["insulin"] * 100) / 100;
            $this->m_treatmentsData['insulinId'][$type][$timestamp] = $item["identifier"];
            $isInsulinData =true;
        }
        return $isInsulinData;
    }

    /**
     * @param $_endDateSeconds
     */
    private function parseTreatments($_endDateSeconds) {
        //echo '<pre>';
        $simpleProfiles = $completeProfiles = [];
        $treatments = ParserHelper::removeDuplicates($this->m_rawData['treatments']);
        foreach($treatments as $item) {
            //compute timestamp from various possibilities
            $timestamp = ParserHelper::extractTimestamp($item, $this->m_utcOffset);
            if(is_null($timestamp)) {
                continue;
            }
            $timestamp = $this->roundToFiveMinutes($timestamp);

            $isInsulinData = $this->parseInsulinData($item, $timestamp);

            if(array_key_exists("carbs", $item) && is_numeric($item["carbs"])) {
                $this->m_treatmentsData['carbs'][$timestamp] = $item["carbs"];
                $isInsulinData = true;
            }

            if(array_key_exists("profileJson", $item) && !empty($item["profileJson"])
            ) {
                $item["profileJson"] = $this->decodeProfile($item);
                $simpleProfiles[$timestamp] = $item["profileJson"];
                $completeProfiles[$timestamp] = $item;
            } elseif(array_key_exists("notes", $item) && !empty($item["notes"])) {
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
            } elseif(!$isInsulinData && $item["eventType"] == 'BG Check' && array_key_exists('glucose', $item)) {
                $this->m_bloodGlucoseData[$timestamp] = $item["glucose"];
                $this->m_treatmentsData['notes'][$timestamp] = $item["eventType"].' : '.$item["glucose"];
            } elseif(!$isInsulinData && !in_array($item["eventType"], ['Temporary Target', 'Bolus Wizard'])) {
                $this->m_treatmentsData['notes'][$timestamp] = $item["eventType"];
            }

        }

        //rename Meal Bolus to Manual Bolus if no carbs
        if(empty($this->m_treatmentsData['carbs'])
        && array_key_exists('Meal Bolus', $this->m_treatmentsData['insulin'])) {
            $this->m_treatmentsData['insulin']['Manual Bolus'] = $this->m_treatmentsData['insulin']['Meal Bolus'];
            unset($this->m_treatmentsData['insulin']['Meal Bolus']);
        }

        //var_dump('completeProfiles', $completeProfiles);
        if(!empty($completeProfiles) || !empty($tempBasalRates)) {
            ksort($completeProfiles);
            ksort($simpleProfiles);
            $this->m_profiles = $completeProfiles;
            $this->computeBasalFromProfile($simpleProfiles, $this->m_tempBasalRates, $_endDateSeconds);
        }
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
