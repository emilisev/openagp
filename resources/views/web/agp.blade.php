@extends('layouts.mainlayout')
@section('content')
    <div id="main"><h1>Ambulatory Glucose Profile (AGP)</h1>
        @include('layouts.sub.reportDate')
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
                        <x-timeInRange renderTo="time-in-range-chart" :data="$data" :height="180"/>
                        @include('cards.timeInRange.text')
                    </div>
                    @include('cards.timeInRange.settings')
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
                <div id="weekly-chart" class="highcharts-light upper-labels"></div>
            </content>
            <x-weekly renderTo="weekly-chart" :data="$data"/>
        </div>
    </div>
@endsection
