<div id="time-in-range-text">
    <div class="layout-row layout-align-start-center">
        <div class="high-container time-in-range-container">
            <div class="block-label very-high">
                {{ round($timeInRangeData['veryHigh']) }}&nbsp;% Très élevée
            </div>
            <div class="goal very-high-goal">
                Objectif&nbsp;: &lt;5&nbsp;%
            </div>
            <div class="block-label high">
                {{ round($timeInRangeData['high']) }}&nbsp;% Élevée
            </div>
        </div>

        <div class="summation-and-goal">
            <div class="summation high-summation">
                {{ round($timeInRangeData['high'] + $timeInRangeData['veryHigh']) }}&nbsp;%
            </div>
            <div class="goal high-summation-goal">
                Objectif&nbsp;: &lt;25&nbsp;%
            </div>
        </div>
    </div>

    <div>
        <div class="block-label target">
            {{ round($timeInRangeData['target']) }}&nbsp;% Dans la plage
        </div>
        <div class="goal target-goal">
            Objectif&nbsp;: &gt;70&nbsp;%
        </div>
    </div>


    <div class="layout-row layout-align-start-center">
        <div class="low-container time-in-range-container">
            <div class="block-label low">
                {{ round($timeInRangeData['low']) }}&nbsp;% Basse
            </div>
            <div class="block-label very-low">
                @if ($timeInRangeData['veryLow'] == 0)
                0
                @elseif ($timeInRangeData['veryLow'] < 1)
                < 1
                @else
                {{ round($timeInRangeData['veryLow']) }}
                @endif
                {{ " % Très basse" }}
            </div>
            <div class="goal very-low-goal">
                Objectif&nbsp;: &lt;1&nbsp;%
            </div>
        </div>
        <div class="summation-and-goal">
            <div class="summation low-summation">
                {{ round($timeInRangeData['low'] + $timeInRangeData['veryLow']) }}&nbsp;%
            </div>
            <div class="goal low-summation-goal">
                Objectif&nbsp;: &lt;4&nbsp;%
            </div>
        </div>
    </div>
</div>
