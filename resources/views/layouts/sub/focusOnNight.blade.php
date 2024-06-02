@if($formDefault['isFocusOnNightAllowed'])
    <div class="float-end">
        <i id="selectDateIcon" class="bi bi-sun" aria-hidden="true" title="{{__("Focus sur la journée")}}"></i>
        <div class="form-switch d-inline">
            <input class="form-check-input float-none" type="checkbox" role="switch"
                   id="focusOnNightSwitch"
                   @if($formDefault['focusOnNight'] == true)
                   data-toggle="switch" checked
                @endif>
        </div>
        <i id="selectDateIcon" class="bi bi-moon" aria-hidden="true" title="{{__("Focus sur les nuits")}}"></i>
    </div>
@endif
