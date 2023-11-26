@extends('layouts.mainlayout')
@php /** @var App\Models\DiabetesData $data */ @endphp
@section('content')
    <div id="main"><h1>Semainier</h1>
        <div id="weekly" class="content-card">
            <header><span>Profil glycémique quotidien</span></header>
            <content>
                <div id="weekly-chart" class="highcharts-light upper-labels"></div>
            </content>
            <x-weekly renderTo="weekly-chart" :data="$data"/>
        </div>
    </div>
@endsection
