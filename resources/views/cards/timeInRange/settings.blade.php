<div class="agp-target-settings">
    <div class="range">
        <span class="range-label in-range-label">Plage cible&nbsp;:</span>
        <span class="in-range">{{ $targets['low'] }}-{{ $targets['high'] }} mg/dL</span>
    </div>

    <div class="range">
    <span class="range-label very-high-label">Très élevée&nbsp;:</span>
        <span class="very-high">> {{ $targets['veryHigh'] }} mg/dL</span>
    </div>

    <div class="range">
    <span class="range-label very-low-label">Très basse&nbsp;:</span>
        <span class="very-low">< {{ $targets['veryLow'] }}&nbsp;mg/dL</span>
    </div>
</div>
