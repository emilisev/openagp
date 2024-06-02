@extends('layouts.mainlayout')
@section('content')
    <h1>{{ __("Jour après jour") }}</h1>
    @include('layouts.sub.reportDateRange')
    @php
        $formDefault['isFocusOnNightAllowed'] = false;
    @endphp
    @foreach ($dailyData as $data)
    <div class="card big-title" id="daily">
        <header class="card-title">
            @include('layouts.sub.reportDateSingle',
            ['allowChange' => false, 'dailyData' => $data, 'formDefault' => $formDefault])
        </header>
        <content class="card-body container">
            <div class="row justify-content-center mb-2">
                @include('cards.average.square')
                @include('cards.variation.square')
                <div id="time-in-range-chart{{$loop->index}}" class="col-auto pe-0"></div>
                <x-avgTimeInRange renderTo="time-in-range-chart{{$loop->index}}" :data="$data" width="40" :height="130"/>
                @include('cards.timeInRange.text')

                <div id="daily-chart{{$loop->index}}" class="highcharts-light col-auto"></div>
                <x-daily renderTo="daily-chart{{$loop->index}}" :data="$data" :height="160"/>
            </div>
        </content>
    </div>
@endforeach
@endsection
