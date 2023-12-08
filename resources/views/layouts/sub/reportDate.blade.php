@php /** @var App\Models\DiabetesData $data */ @endphp
<h4>
    <b>{{round(($data->getEnd() - $data->getBegin())/(60*60*24))}} jours</b>
    | {{date('D j F Y', $data->getBegin())}} - {{date('D j F Y', $data->getEnd())}}
    <i id="selectDateIcon" class="bi bi-calendar3 text-primary" aria-hidden="true"></i>
</h4>
<form method="POST" style="display: none" action="{{ Request::route()->getName()?? ($agent->isMobile()?'/daily':'/agp') }}">
    @csrf
    <input id="dates" name="dates" type="text" class="form-control" value="{{ @$formDefault['dates'] }}"/>
</form>
<script type="module">
    function dateRangePickerSubmit(start, end) {
        $('#dates').val(start.format('DD/MM/YYYY')+" - "+end.format('DD/MM/YYYY'));
        $('#dates')[0].form.submit();
    }

    $('#selectDateIcon').daterangepicker({
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
        },
        "maxSpan": {
            "days": 90
        },
        "alwaysShowCalendars": true,
        ranges: {
            '7 derniers jours': [moment().subtract(7, 'days'), moment().subtract(1, 'days')],
            '14 derniers jours': [moment().subtract(14, 'days'), moment().subtract(1, 'days')],
            '30 derniers jours': [moment().subtract(30, 'days'), moment().subtract(1, 'days')],
            '90 derniers jours': [moment().subtract(90, 'days'), moment().subtract(1, 'days')],
        },
        maxDate : moment().subtract(1, 'days'),
        startDate: "{{ $formDefault['startDate'] }}",
        endDate: "{{ $formDefault['endDate'] }}"
    }, dateRangePickerSubmit);
</script>
