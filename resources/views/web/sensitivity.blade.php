@extends('layouts.mainlayout')
@php /** @var App\Models\DiabetesData $data */ @endphp
@section('content')
    <h1>{{ __("Sensibilité à l'insuline") }}</h1>
    @include('layouts.sub.reportDateRange')
    <div class="card">
        <header class="card-title">{{ __("Sensibilité à l'insuline") }}</header>
        <content class="card-body">
            <div id="chart" class="highcharts-light"></div>
        </content>
        <x-sensitivity renderTo="chart" :data="$data"/>
    </div>
@endsection
