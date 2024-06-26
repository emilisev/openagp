@extends('layouts.mainlayout')
@section('content')
<h1>Ambulatory Glucose Profile (AGP)</h1>
@include('layouts.sub.reportDateRange')
<div id="agp-first-row" class="row align-items-center mx-0">
    <div id="time-in-range" class="card col-6">
        <header class="card-title">{{ __("Temps dans la cible") }}</header>
        <content class="card-body">
            <div class="row">
                <div class="col-2" id="time-in-range-chart"></div>
                <x-avgTimeInRange renderTo="time-in-range-chart" :data="$data"/>
                @include('cards.timeInRange.text', ['style' => 'light'])
            </div>
            @include('cards.timeInRange.settings')
        </content>
    </div>
    <div id="average" class="card col mx-0">
        <header class="card-title">{{ __("Mesures du glucose") }}</header>
        <content class="card-body">
            @include('cards.average.text')
        </content>
    </div>
</div>
<div id="agp" class="card">
    <header class="card-title">{{ __("Ambulatory Glucose Profile (AGP)") }}</header>
    <content class="card-body">
        <div id="agp-chart" class="highcharts-light"></div>
    </content class="card-body">
    <x-agp renderTo="agp-chart" :data="$data" :height="300"/>
</div>
<div id="daily" class="card">
    <header class="card-title">{{ __("Profil glycémique quotidien sur les 15 derniers jours") }}</header>
    <content class="card-body">
        <div id="weekly-chart" class="highcharts-light upper-labels"></div>
    </content>
    <x-weekly renderTo="weekly-chart" :data="$data"/>
</div>
@endsection
