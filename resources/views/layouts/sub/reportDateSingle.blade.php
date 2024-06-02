@php
    $begin = $dailyData->getBegin();
    if($formDefault['isFocusOnNightAllowed'] && $formDefault['focusOnNight'] == true) {
        $begin += 60 * 60 *12;
    }
    /** @var App\Models\DiabetesData $data
    /** @var \IntlDateFormatter $dateFormatter */
    if($allowChange) {
        $previousDay = $begin - 60*60*24;
        $nextDay = $begin + 60*60*24;
        if($nextDay > time()) {
            unset($nextDay);
        }
    }

@endphp

@if(isset($previousDay))
    <a href="?day={{ date('d/m/Y',  $previousDay) }}"><i class="bi bi-caret-left-fill"></i></a>
@endif

@if(!$allowChange)<a href="/daily?day={{ date('d/m/Y',  $begin) }}">@endif
@if($formDefault['isFocusOnNightAllowed'] && $formDefault['focusOnNight'] == true)
    {{ $dateFormatter->format($begin - 60 * 60 *12) }} -
@endif
{{ $dateFormatter->format($begin) }}
@if(!$allowChange)</a>@endif

@if($allowChange)
    <form method="GET" action="/daily" style="display: none">
        <input id="day" name="day" type="text" class="form-control" value="{{ date('d/m/Y', $begin) }}"/>
        @if($formDefault['isFocusOnNightAllowed'])
            <input id="focusOnNight" name="focusOnNight" type="text" class="form-control"/>
        @endif
    </form>
    <i id="selectDateIcon" class="bi bi-calendar3" aria-hidden="true"></i>
    <script type="module">
        function dateRangePickerSubmit(start, end) {
            $('#day').val(start.format('DD/MM/YYYY'));
            $('#day')[0].form.submit();
        }

        $('#selectDateIcon').daterangepicker({
            "singleDatePicker": true,
            "autoApply": true,
            "startDate": "{{ date('d/m/Y', $begin) }}",
            maxDate : moment()
        }, dateRangePickerSubmit);
    </script>
@endif

@if(isset($nextDay))
    <a href="?day={{ date('d/m/Y',  $nextDay) }}"><i class="bi bi-caret-right-fill"></i></a>
@endif

@include('layouts.sub.focusOnNight')
