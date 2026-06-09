@php
    if($value <= config('diabetes.bloodGlucose.targets.veryLow')) {
	    $color = config('colors.timeInRange.veryLow');
    } elseif($value <= config('diabetes.bloodGlucose.targets.low')) {
	    $color = config('colors.timeInRange.low');
    } elseif($value <= config('diabetes.bloodGlucose.targets.high')) {
	    $color = config('colors.timeInRange.range');
    } elseif($value <= config('diabetes.bloodGlucose.targets.veryHigh')) {
	    $color = config('colors.timeInRange.high');
    } else {
	    $color = config('colors.timeInRange.veryHigh');
	}
@endphp
<div class="col-auto text-center">
    <div class="card m-0 p-2 text-center" style="background-color: {{$color}}">
        <p class="display-3 m-0">{{$value}}</p>
        <span>{{ date('d/m',  $date) }}</span>
    </div>
    <span>{{$type}}</span>
</div>
