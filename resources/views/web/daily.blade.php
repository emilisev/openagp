@extends('layouts.nosidebar')
@php
/** @var $data \App\Models\DiabetesData */
$previousDay = $data->getBegin() - 60*60*24;
$nextDay = $data->getBegin() + 60*60*24;
@endphp
@section('content')
    <div id="main"><h1>Quotidien</h1>
        <div id="time-in-range" class="content-card big-title">
            <header>
                <span><a href="?day={{ date('d/m/Y',  $previousDay) }}"><i class="bi bi-caret-left-fill"></i></a>
                    {{ date('D d M',  $data->getBegin()) }}
                <a href="?day={{ date('d/m/Y',  $nextDay) }}"><i class="bi bi-caret-right-fill"></i></a></span>
                </header>
            <content>
                <div class="layout-row">
                    <div id="time-in-range-chart"></div>
                    <x-timeInRange type="chart" renderTo="time-in-range-chart" :data="$data"/>
                    <x-timeInRange type="text" :data="$data"/>
                </div>
                <div id="daily-chart" class="highcharts-light"></div>
                <x-daily renderTo="daily-chart" :data="$data"/>
            </content>
            {{--<x-daily renderTo="daily-chart" :data="$data"/>--}}
        </div>
    </div>
@endsection
