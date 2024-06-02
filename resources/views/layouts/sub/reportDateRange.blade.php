@php
/** @var App\Models\DiabetesData $data
/** @var \IntlDateFormatter $dateFormatter \*/
@endphp
<h4>
    <b>{{round(($data->getEnd() - $data->getBegin())/(60*60*24))}} {{ __("jours") }}</b>
    | {{$dateFormatter->format($data->getBegin())}} - {{$dateFormatter->format($data->getEnd())}}
    <i id="selectDateIcon" class="bi bi-calendar3 text-primary" aria-hidden="true"></i>
    @include('layouts.sub.focusOnNight')
</h4>
<form method="POST" style="display: none" action="{{ Request::route()->getName()?? ($agent->isMobile()?'/daily':'/agp') }}">
    @csrf
    <input id="dates" name="dates" type="text" class="form-control" value="{{ @$formDefault['dates'] }}"/>
    @if($formDefault['isFocusOnNightAllowed'])
        <input id="focusOnNight" name="focusOnNight" type="text" class="form-control"/>
    @endif
</form>
<script type="module">
    function dateRangePickerSubmit(start, end) {
        $('#dates').val(start.format('DD/MM/YYYY')+" - "+end.format('DD/MM/YYYY'));
        $('#dates')[0].form.submit();
    }

    $('#selectDateIcon').daterangepicker({
        "maxSpan": {
            "days": 90
        },
        "alwaysShowCalendars": true,
        ranges: {
            '{{ __('7 derniers jours') }}': [moment().subtract(7, 'days'), moment().subtract(1, 'days')],
            '{{ __('14 derniers jours') }}': [moment().subtract(14, 'days'), moment().subtract(1, 'days')],
            '{{ __('30 derniers jours') }}': [moment().subtract(30, 'days'), moment().subtract(1, 'days')],
            '{{ __('90 derniers jours') }}': [moment().subtract(90, 'days'), moment().subtract(1, 'days')],
        },
        maxDate : moment().subtract(1, 'days'),
        startDate: "{{ $formDefault['startDate'] }}",
        endDate: "{{ $formDefault['endDate'] }}"
    }, dateRangePickerSubmit);
</script>
