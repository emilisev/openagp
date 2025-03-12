<?php

namespace App\Helpers;

class LabelProviders {

    static function get($_string) {
        if($_string == 'meal') {
            return __('Glucides repas');
        }
        if($_string == 'hypo') {
            return __('Glucides hypo');
        }
        if($_string == 'night') {
            return __('Nuit');
        }
        if($_string == 'breakfast') {
            return __('Petit-déjeuner');
        }
        if($_string == 'lunch') {
            return __('Déjeuner');
        }
        if($_string == 'afternoonsnack') {
            return __('Goûter');
        }
        if($_string == 'diner') {
            return __('Dîner');
        }
        if($_string == 'insulinActivity') {
            return __('Activé de l\'insuline');
        }
        if($_string == '') {
            return __('');
        }

        return $_string;
    }

}
