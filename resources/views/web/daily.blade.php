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
    <div id="main">
        <div class="content-card big-title">
            <header>
                <span><a href="?day={{ date('d/m/Y',  $previousDay) }}"><i class="bi bi-caret-left-fill"></i></a>
                    {{ date('D d M',  $data->getBegin()) }}
                @if(isset($nextDay))
                    <a href="?day={{ date('d/m/Y',  $nextDay) }}"><i class="bi bi-caret-right-fill"></i></a>
                @endif
                </span>
                </header>
            <content>
                <div class="layout-row">
                    <div id="average" style="width:30%"></div>
                    <x-averageGauge renderTo="average" :data="$data" :height="130"/>
                    <div id="time-in-range-chart" style="width:70px"></div>
                    <x-timeInRange renderTo="time-in-range-chart" :data="$data" :height="130"/>
                    @include('cards.timeInRange.text', ['style' => 'light'])
                </div>
                <div id="daily-chart" class="highcharts-light"></div>
                <x-daily renderTo="daily-chart" :data="$data"/>
            </content>
            {{--<x-daily renderTo="daily-chart" :data="$data"/>--}}
        </div>
    </div>
@endsection
