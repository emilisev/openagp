import $ from 'jquery';
window.$ = $;

import './bootstrap';

import highcharts from 'highcharts';
import highchartsmore from 'highcharts/highcharts-more';
highchartsmore(highcharts);
import highchartsheatmap from 'highcharts/modules/heatmap';
highchartsheatmap(highcharts);
import highchartsacessibility from 'highcharts/modules/accessibility';
highchartsacessibility(highcharts);
window.Highcharts = highcharts;

import moment from 'moment';
window.moment = moment;

import daterangepicker from 'daterangepicker';
window.daterangepicker = daterangepicker;

$(document).ready(function() {
    $(".show-hide-password a").on('click', function(event) {
        event.preventDefault();
        if($('.show-hide-password input').attr("type") == "text"){
            $('.show-hide-password input').attr('type', 'password');
            $('.show-hide-password i').addClass( "bi-eye-slash" );
            $('.show-hide-password i').removeClass( "bi-eye" );
        }else if($('.show-hide-password input').attr("type") == "password"){
            $('.show-hide-password input').attr('type', 'text');
            $('.show-hide-password i').removeClass( "bi-eye-slash" );
            $('.show-hide-password i').addClass( "bi-eye" );
        }
    });
    $('.upper-labels .highcharts-data-label').each(function(item) {
        var current = $(this).attr("transform");
        var currentX = current.match(/[0-9]+,/);
        $(this).attr("transform", 'translate('+currentX+' 0)');
    });
});
