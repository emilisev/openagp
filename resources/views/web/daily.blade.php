@extends('layouts.mainlayout')
@php
/** @var $data \App\Models\DiabetesData */
$previousDay = $data->getBegin() - 60*60*24;
$nextDay = $data->getBegin() + 60*60*24;
if($nextDay > time()) {
	unset($nextDay);
}
@endphp
@section('content')
<div class="card big-title">
    <header class="card-title">
        <a href="?day={{ date('d/m/Y',  $previousDay) }}"><i class="bi bi-caret-left-fill"></i></a>
            {{ date('D d M',  $data->getBegin()) }}
        @if(isset($nextDay))
            <a href="?day={{ date('d/m/Y',  $nextDay) }}"><i class="bi bi-caret-right-fill"></i></a>
        @endif
    </header>
    <content class="card-body container">
        <div class="row justify-content-center">
            @include('cards.average.square')
            @include('cards.variation.square')
            <div id="time-in-range-chart" class="col-auto"></div>
            <x-avgTimeInRange renderTo="time-in-range-chart" :data="$data" :height="130"/>
            @include('cards.timeInRange.text', ['style' => 'light'])

        </div>
        <div id="daily-chart" class="highcharts-light"></div>
        <x-daily renderTo="daily-chart" :data="$data"/>
    </content>
</div>
@endsection
