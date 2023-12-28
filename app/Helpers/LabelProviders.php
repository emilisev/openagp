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
    }

}
