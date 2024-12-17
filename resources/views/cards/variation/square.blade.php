@php
    if($data->getVariation() <= config('diabetes.variation.targets.good')) {
	    $color = config('colors.timeInRange.range');
    } elseif($data->getVariation() <= config('diabetes.variation.targets.high')) {
	    $color = config('colors.timeInRange.high');
    } else {
	    $color = config('colors.timeInRange.veryHigh');
	}
@endphp
<div class="col-auto text-center">
        <div class="card m-0 p-2 text-center" style="background-color: {{$color}}">
        <p class="display-3 m-0">{{round(($data->getVariation()*10))/10}}</p>
        <span>%</span>
        </div>
        <span>Variation</span>
</div>
