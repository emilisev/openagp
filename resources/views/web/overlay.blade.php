@extends('layouts.mainlayout')
@php /** @var App\Models\DiabetesData $data */ @endphp
@section('content')
    <h1>{{ __("Superposition") }}</h1>
    @include('layouts.sub.reportDateRange')
    <div id="overlay" class="card">
        <header class="card-title">{{ __("Superposition") }}</header>
        <content class="card-body">
            <div id="overlay-chart" class="highcharts-light"></div>
        </content>
        <x-overlay renderTo="overlay-chart" :data="$data"/>
    </div>
@endsection
