<?php

namespace App\Helpers;

use App\Models\DiabetesData;

class ParserHelper {

/********************** PUBLIC METHODS *********************/
    static function extractTimestamp($_entry, $_utcOffset = 0) {
        //compute timestamp from various possibilities
        $timestamp = null;
        if(array_key_exists("created_at", $_entry)) {
            $date = \DateTime::createFromFormat('Y-m-d\TH:i:s.v\Z', $_entry["created_at"]);
            $timestamp = $date->format('Uv');
        } elseif(array_key_exists("timestamp", $_entry)) {
            $timestamp = $_entry["timestamp"];
        } elseif(array_key_exists("date", $_entry)) {
            $timestamp = $_entry["date"];
        } elseif(array_key_exists("srvCreated", $_entry)) {
            $timestamp = $_entry["srvCreated"];
        }
        if(!is_null($timestamp)) {
            $timestamp += $_utcOffset * DiabetesData::__1SECOND;
        }
        return $timestamp;
    }

    public static function removeDuplicates($_array) {
        $result = [];
        $dupKeys = [];
        //var_dump($_array);
        foreach($_array as $key => $item) {
            if(!isset($previousItem)) {
                ;
            } else {
                $firstTimestamp = self::extractTimestamp($previousItem);
                $secondTimestamp = self::extractTimestamp($item);
                //more than 3 minutes, keep both
                if(($secondTimestamp - $firstTimestamp) >= DiabetesData::__1MINUTE*3) {
                    ;
                } else { //less than 3 mn apart
                    $filteredItem = self::removeTimeFields($item);
                    $filteredPreviousItem = self::removeTimeFields($previousItem);
                    //identical, remove duplicate
                    if($filteredItem == $filteredPreviousItem) {
                        $dupKeys[] = $key;
                    } elseif($item['eventType'] == 'Profile Switch' && $previousItem['eventType'] == 'Note') {
                        if(self::isProfileSwitchIdenticalToNote($item, $previousItem)) {
                            $dupKeys[] = $key;
                        }
                    } elseif($previousItem['eventType'] == 'Profile Switch' && $item['eventType'] == 'Note') {
                        if(self::isProfileSwitchIdenticalToNote($previousItem, $item)) {
                            $dupKeys[] = $key - 1;
                        }
                    } /*elseif($item['eventType'] == 'Note' && $previousItem['eventType'] == 'Note') {
                        var_dump(__LINE__, $item, $previousItem);
                        die();
                    }*/
                }
            }
            $previousItem = $item;
        }
        //var_dump($dupKeys);
        $result = array_filter(
            $_array,
            function($_key) use($dupKeys) {
                return !in_array($_key, $dupKeys);
            },
            ARRAY_FILTER_USE_KEY);
        //var_dump('removeDuplicates', count($_array), count($result), array_keys($result));
        return $result;
    }

    private static function isProfileSwitchIdenticalToNote($_profileSwitch, $_note) {
        if($_profileSwitch['profile'] == $_note['notes']
            && $_profileSwitch['originalDuration'] == $_note['originalDuration']) {
            return true;
        }
        return false;

    }

    /**
     * @param $_item
     * @return array
     */
    private static function removeTimeFields($_item): array {
        return array_filter(
            $_item,
            function($_key) {
                return !in_array($_key, ['created_at', 'identifier', 'srvModified', 'srvCreated']);
            },
            ARRAY_FILTER_USE_KEY);
    }
}
