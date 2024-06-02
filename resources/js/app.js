import $ from 'jquery';
window.$ = $;

import './bootstrap';

import highcharts from 'highcharts';
import highchartsmore from 'highcharts/highcharts-more';
highchartsmore(highcharts);
import highchartsheatmap from 'highcharts/modules/heatmap';
import highchartsvariwide from 'highcharts/modules/variwide';
highchartsheatmap(highcharts);
highchartsvariwide(highcharts);
import highchartsacessibility from 'highcharts/modules/accessibility';
highchartsacessibility(highcharts);
import highchartsannotations from 'highcharts/modules/annotations';
highchartsannotations(highcharts);
window.Highcharts = highcharts;

import moment from 'moment';
window.moment = moment;

import daterangepicker from 'daterangepicker';
window.daterangepicker = daterangepicker;
$.fn.daterangepicker.defaultOptions = {
    "locale": {
        "format": "DD/MM/YYYY",
        "separator": " - ",
        "applyLabel": "Appliquer",
        "cancelLabel": "Annuler",
        "fromLabel": "Du",
        "toLabel": "Au",
        "customRangeLabel": "Personnaliser",
        "weekLabel": "W",
        "daysOfWeek": [
            "D",
            "L",
            "M",
            "M",
            "J",
            "V",
            "S"
        ],
        "monthNames": [
            "Janvier",
            "Février",
            "Mars",
            "Avril",
            "Mai",
            "Juin",
            "Juillet",
            "Août",
            "Septembre",
            "Octobre",
            "Novembre",
            "Décembre"
        ],
        "firstDay": 1
    }
}


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
    $('.upper-labels.highcharts-data-label').each(function(item) {
        var current = $(this).attr("transform");
        var currentX = current.match(/[0-9]+,/);
        $(this).attr("transform", 'translate('+currentX+' 0)');
    });
    $('.left-aligned-labels .highcharts-data-label').each(function(item) {
        var current = $(this).attr("transform");
        var currentY = current.match(/,-?[0-9]+/);
        console.log(current, currentY, 'translate(0'+currentY+')');
        $(this).attr("transform", 'translate(0'+currentY+')');
    });
    $('#focusOnNightSwitch').on('change', function (event, state) {
        $('#focusOnNight').val(this.checked?1:0);
        $('#focusOnNight')[0].form.submit();
    })

});

Highcharts.setNullTreatment = function(identifier) {
    if(confirm("Voulez-vous vraiment effacer ce traitement ?")) {
        if(document.location.href.includes('?')) {
            var url = document.location.href+"&setNullTreatment="+identifier;
        } else{
            var url = document.location.href+"?setNullTreatment="+identifier;
        }
        document.location = url;
    }
}
var maxPrintWidth = 600;
window.onbeforeprint = function() {
    $(Highcharts.charts).each(function(i,chart){
        chart.oldhasUserSize = chart.hasUserSize;
        chart.resetParams = [chart.chartWidth, chart.chartHeight, false];
        var height = chart.renderTo.clientHeight;
        var width = chart.renderTo.clientWidth;
        chart.setSize(Math.min(maxPrintWidth, chart.chartWidth), chart.chartHeight);
    });
}
window.onafterprint = function() {
    $(Highcharts.charts).each(function(i,chart){
        chart.setSize.apply(chart, chart.resetParams);
        chart.hasUserSize = chart.oldhasUserSize;
    });
}
