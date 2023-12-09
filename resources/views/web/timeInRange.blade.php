@extends('layouts.mainlayout')
@php /** @var App\Models\DiabetesData $data */ @endphp
@section('content')
<h1>Temps dans la cible</h1>
@include('layouts.sub.reportDate')
<div id="weekly" class="card">
    <header class="card-title">Objectif</header>
    <content class="card-body">
        <div id="chart" class="highcharts-light left-aligned-labels"></div>
    </content>
    <x-dailyTimeInRange renderTo="chart" :data="$data"/>
</div>
@endsection
