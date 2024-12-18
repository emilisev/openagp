@php
    /** @var $data \App\Models\DiabetesData */
    $timeInRangeData = $data->getTimeInRangePercent();
@endphp
<div class="col-auto" id="time-in-range-text">
    <div class="row">
        <div class="high-container time-in-range-container col">
            <div class="block-label very-high" style="color:{{config('colors.timeInRange.veryHigh')}}">
                {{ round($timeInRangeData['veryHigh']) }}&nbsp;% {{ __("Très élevée") }}
            </div>
            <div class="goal very-high-goal d-none d-lg-block">
                {{ __("Objectif") }}&nbsp;: &lt;5&nbsp;%
            </div>
            <div class="block-label high" style="color:{{config('colors.timeInRange.high')}}">
                {{ round($timeInRangeData['high']) }}&nbsp;% {{ __("Élevée") }}
            </div>
        </div>

        <div class="summation-and-goal col d-none d-lg-block">
            <div class="summation high-summation">
                {{ round($timeInRangeData['high'] + $timeInRangeData['veryHigh']) }}&nbsp;%
            </div>
            <div class="goal high-summation-goal">
                {{ __("Objectif") }}&nbsp;: &lt;25&nbsp;%
            </div>
        </div>
    </div>

    <div class="row">
        <div class="block-label target" style="color:{{config('colors.timeInRange.tightRange')}}">
            {{ round($timeInRangeData['range'] + $timeInRangeData['tightRange']) }}&nbsp;% {{ __("Dans la plage") }}
        </div>
        <div class="goal target-goal d-none d-lg-block">
            {{ __("Objectif") }}&nbsp;: &gt;70&nbsp;%
        </div>
    </div>


    <div class="row">
        <div class="low-container time-in-range-container col">
            <div class="block-label low" style="color:{{config('colors.timeInRange.low')}}">
                {{ round($timeInRangeData['low']) }}&nbsp;% {{ __("Basse") }}
            </div>
            <div class="block-label very-low" style="color:{{config('colors.timeInRange.veryLow')}}">
                @if ($timeInRangeData['veryLow'] == 0)
                0
                @elseif ($timeInRangeData['veryLow'] < 1)
                < 1
                @else
                {{ round($timeInRangeData['veryLow']) }}
                @endif
                {{ " % " }}{{ __("Très basse") }}
            </div>
            <div class="goal very-low-goal d-none d-lg-block">
                {{ __("Objectif") }}&nbsp;: &lt;1&nbsp;%
            </div>
        </div>
        <div class="summation-and-goal col d-none d-lg-block">
            <div class="summation low-summation">
                {{ round($timeInRangeData['low'] + $timeInRangeData['veryLow']) }}&nbsp;%
            </div>
            <div class="goal low-summation-goal">
                {{ __("Objectif") }}&nbsp;: &lt;4&nbsp;%
            </div>
        </div>
    </div>
</div>
