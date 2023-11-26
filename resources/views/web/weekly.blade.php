@extends('layouts.mainlayout')
@php /** @var App\Models\DiabetesData $data */ @endphp
@section('content')
    <div id="main"><h1>Semainier</h1>
        <div id="daily" class="content-card">
            <header><span>Profil glycémique quotidien</span></header>
            <content>
                <div id="daily-chart" class="highcharts-light"></div>
            </content>
            <x-weekly renderTo="daily-chart" :data="$data"/>
        </div>
    </div>
@endsection
