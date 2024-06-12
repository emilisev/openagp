@extends('layouts.mainlayout')
@php
/** @var $data \App\Models\DiabetesData */
if($data instanceof \App\Models\DiabetesData) {
    $data = [$data];
}
$graphHeight = count($data) > 1?130:null;
@endphp
@section('content')
@foreach ($data as $dailyData)
<div id="daily" class="card big-title">
    <header class="card-title">
        @include('layouts.sub.reportDateSingle', ['data' => $dailyData, 'allowChange' => count($data) == 1])
    </header>
    <content class="card-body container">
        <div class="row justify-content-center mb-2">
            @include('cards.average.square', ['data' => $dailyData])
            @include('cards.variation.square', ['data' => $dailyData])
            <div id="time-in-range-chart{{$loop->index}}" class="col-auto pe-0"></div>
            <x-avgTimeInRange renderTo="time-in-range-chart{{$loop->index}}" :data="$dailyData" width="40"/>
            {{--<div id="time-in-range">@include('cards.timeInRange.text')</div>--}}
            @include('cards.timeInRange.text', ['data' => $dailyData])

        @if(count($data) == 1)
            </div>
        {{__('Cliquez et tracez une zone pour zoomer')}}
        @endif
        <div id="daily-chart{{$loop->index}}" class="highcharts-light @if(count($data) > 1) col-auto @endif"></div>
        <x-daily renderTo="daily-chart{{$loop->index}}" :data="$dailyData" :height="$graphHeight"/>
        @if(count($data) > 1)
                    </div>
        @endif
    </content>
</div>
@endforeach
@endsection
