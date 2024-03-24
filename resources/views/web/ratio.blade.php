@extends('layouts.mainlayout')
@php /** @var App\Models\DiabetesData $data */ @endphp
@section('content')
    <h1>Ratios</h1>
    @include('layouts.sub.reportDateRange')
    <div id="monthly" class="card">
        <header class="card-title">{{ __("Ratios par repas") }}</header>
        <content class="card-body">
            <div id="ratio-chart" class="highcharts-light"></div>
        </content>
        <x-ratios renderTo="ratio-chart" :data="$data"/>
    </div>
@endsection
