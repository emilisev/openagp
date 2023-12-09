@extends('layouts.mainlayout')
@php /** @var App\Models\DiabetesData $data */ @endphp
@section('content')
    <h1>Semainier</h1>
    @include('layouts.sub.reportDate')
    <div id="weekly" class="card">
        <header class="card-title">Profil glycémique quotidien</header>
        <content class="card-body">
            <div id="weekly-chart" class="highcharts-light upper-labels"></div>
        </content>
        <x-weekly renderTo="weekly-chart" :data="$data"/>
    </div>
@endsection
