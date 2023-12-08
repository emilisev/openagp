@extends('layouts.mainlayout')
@php /** @var App\Models\DiabetesData $data */ @endphp
@section('content')
    <div id="main"><h1>Temps dans la cible</h1>
        @include('layouts.sub.reportDate')
        <div id="weekly" class="content-card">
            <header><span>Objectif</span></header>
            <content>
                <div id="chart" class="highcharts-light upper-labels"></div>
            </content>
            <x-dailyTimeInRange renderTo="chart" :data="$data"/>
        </div>
    </div>
@endsection
