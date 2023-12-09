@php
    /** @var $data \App\Models\DiabetesData */
    $timeInRangeData = $data->getTimeInRangePercent();
	if(!isset($style)) {
		$style = 'complete';
    }
@endphp
<div @if($style == 'complete') class="col" @else class="col-auto" @endif id="time-in-range-text">
    <div @if($style == 'complete') class="row"@endif >
        <div class="high-container time-in-range-container col">
            <div class="block-label very-high" style="color:{{config('colors.timeInRange.veryHigh')}}">
                {{ round($timeInRangeData['veryHigh']) }}&nbsp;% Très élevée
            </div>
            @if($style == 'complete')
            <div class="goal very-high-goal">
                Objectif&nbsp;: &lt;5&nbsp;%
            </div>
            @endif
            <div class="block-label high" style="color:{{config('colors.timeInRange.high')}}">
                {{ round($timeInRangeData['high']) }}&nbsp;% Élevée
            </div>
        </div>

        @if($style == 'complete')
        <div class="summation-and-goal col">
            <div class="summation high-summation">
                {{ round($timeInRangeData['high'] + $timeInRangeData['veryHigh']) }}&nbsp;%
            </div>
            <div class="goal high-summation-goal">
                Objectif&nbsp;: &lt;25&nbsp;%
            </div>
        </div>
        @endif
    </div>

    <div>
        <div class="block-label target" style="color:{{config('colors.timeInRange.target')}}">
            {{ round($timeInRangeData['target']) }}&nbsp;% Dans la plage
        </div>
        @if($style == 'complete')
        <div class="goal target-goal">
            Objectif&nbsp;: &gt;70&nbsp;%
        </div>
        @endif
    </div>


    <div @if($style == 'complete') class="row"@endif >
        <div class="low-container time-in-range-container col">
            <div class="block-label low" style="color:{{config('colors.timeInRange.low')}}">
                {{ round($timeInRangeData['low']) }}&nbsp;% Basse
            </div>
            <div class="block-label very-low" style="color:{{config('colors.timeInRange.veryLow')}}">
                @if ($timeInRangeData['veryLow'] == 0)
                0
                @elseif ($timeInRangeData['veryLow'] < 1)
                < 1
                @else
                {{ round($timeInRangeData['veryLow']) }}
                @endif
                {{ " % Très basse" }}
            </div>
            @if($style == 'complete')
            <div class="goal very-low-goal">
                Objectif&nbsp;: &lt;1&nbsp;%
            </div>
            @endif
        </div>
        @if($style == 'complete')
        <div class="summation-and-goal col">
            <div class="summation low-summation">
                {{ round($timeInRangeData['low'] + $timeInRangeData['veryLow']) }}&nbsp;%
            </div>
            <div class="goal low-summation-goal">
                Objectif&nbsp;: &lt;4&nbsp;%
            </div>
        </div>
        @endif
    </div>
</div>
