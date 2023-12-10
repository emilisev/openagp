@php
    if($data->getAverage() <= config('diabetes.bloodGlucose.targets.veryLow')) {
	    $color = config('colors.timeInRange.veryLow');
    } elseif($data->getAverage() <= config('diabetes.bloodGlucose.targets.low')) {
	    $color = config('colors.timeInRange.low');
    } elseif($data->getAverage() <= config('diabetes.bloodGlucose.targets.high')) {
	    $color = config('colors.timeInRange.target');
    } elseif($data->getAverage() <= config('diabetes.bloodGlucose.targets.veryHigh')) {
	    $color = config('colors.timeInRange.high');
    } else {
	    $color = config('colors.timeInRange.veryHigh');
	}
@endphp
<div class="col-auto text-center">
    <div class="card m-0 p-2 text-center" style="background-color: {{$color}}">
        <p class="display-3 m-0">{{$data->getAverage()}}</p>
        <span>mg/dL</span>
    </div>
    <span>Moyenne glycémie</span>
</div>
