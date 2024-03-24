@php
/** @var App\Models\DiabetesData $data
/** @var \IntlDateFormatter $dateFormatter */

if($allowChange) {
    $previousDay = $data->getBegin() - 60*60*24;
    $nextDay = $data->getBegin() + 60*60*24;
    if($nextDay > time()) {
        unset($nextDay);
    }
}

@endphp

@if(isset($previousDay))
    <a href="?day={{ date('d/m/Y',  $previousDay) }}"><i class="bi bi-caret-left-fill"></i></a>
@endif

@if(!$allowChange)<a href="/daily?day={{ date('d/m/Y',  $dailyData->getBegin()) }}">@endif
{{ $dateFormatter->format($dailyData->getBegin()) }}
@if(!$allowChange)</a>@endif

@if($allowChange)<i id="selectDateIcon" class="bi bi-calendar3" aria-hidden="true"></i>
<form method="GET" style="display: none" action="/daily">
    <input id="day" name="day" type="text" class="form-control"/>
</form>
<script type="module">
    function dateRangePickerSubmit(start, end) {
        $('#day').val(start.format('DD/MM/YYYY'));
        $('#day')[0].form.submit();
    }

    $('#selectDateIcon').daterangepicker({
        "singleDatePicker": true,
        "autoApply": true,
        "startDate": "{{ date('d/m/Y', $dailyData->getBegin()) }}",
        maxDate : moment()
    }, dateRangePickerSubmit);
</script>
@endif

@if(isset($nextDay))
    <a href="?day={{ date('d/m/Y',  $nextDay) }}"><i class="bi bi-caret-right-fill"></i></a>
@endif
