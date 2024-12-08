@php
    /** @var $data \App\Models\DiabetesData */
    $targets = $data->getTargets();
@endphp
<div class="agp-target-settings d-none d-lg-block">
    <div class="range">
        <span class="range-label in-range-label">{{ __("Plage cible") }}&nbsp;:</span>
        <span class="in-range">{{ $targets['range'] }}-{{ $targets['high'] }} {{ __("mg/dL") }}</span>
    </div>

    <div class="range">
    <span class="range-label very-high-label">{{ __("Très élevée") }}&nbsp;:</span>
        <span class="very-high">> {{ $targets['veryHigh'] }} {{ __("mg/dL") }}</span>
    </div>

    <div class="range">
    <span class="range-label very-low-label">{{ __("Très basse") }}&nbsp;:</span>
        <span class="very-low">< {{ $targets['low'] }}&nbsp;{{ __("mg/dL") }}</span>
    </div>
</div>
