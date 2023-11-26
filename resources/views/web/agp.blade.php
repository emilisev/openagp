@extends('layouts.mainlayout')
@php /** @var App\Models\DiabetesData $data */ @endphp
@section('content')
    <div id="main"><h1>Ambulatory Glucose Profile (AGP)</h1>
        <p><b>{{round(($data->getEnd() - $data->getBegin())/(60*60*24))}} jours</b>
            | {{date('D j F Y', $data->getBegin())}} - {{date('D j F Y', $data->getEnd())}}
        <div id="top-row" class="layout-row">
            <div id="time-in-range" class="content-card">
                <header><span>Durée dans les plages</span></header>
                <content>
                    {{--<span class="agp-target-settings">
                        Chaque augmentation de 5 % dans la plage cible est cliniquement bénéfique.
                        <br/>
                        Chaque temps dans la plage de 1 % = environ 15 minutes par jour
                    </span>--}}
                    <div class="layout-row">
                        <div id="time-in-range-chart"></div>
                        <x-timeInRange type="chart" renderTo="time-in-range-chart" :data="$data"/>
                        <x-timeInRange type="text" :data="$data"/>
                    </div>
                    <x-timeInRange type="settings" :data="$data"/>
                </content>
            </div>
            <div id="average" class="content-card">
                <header><span>Mesures du glucose</span></header>
                <content>
                    <x-average :data="$data"/>
                </content>
            </div>
        </div>
        <div id="agp" class="content-card">
            <header><span>Ambulatory Glucose Profile (AGP)</span></header>
            <content>
                <div id="agp-chart" class="highcharts-light"></div>
            </content>
            <x-agp renderTo="agp-chart" :data="$data" :height="300"/>
        </div>
        <div id="daily" class="content-card">
            <header><span>Profil glycémique quotidien sur les 15 derniers jours</span></header>
            <content>
                <div id="weekly-chart" class="highcharts-light"></div>
            </content>
            <x-weekly renderTo="weekly-chart" :data="$data"/>
        </div>
    </div>
@endsection
