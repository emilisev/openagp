@extends('layouts.mainlayout')
@php /** @var App\Models\DiabetesData $data */ @endphp
@section('content')
    <h1>{{ __("Insuline active") }}</h1>
    @include('layouts.sub.reportDateRange')
    <div class="card">
        <header class="card-title">{{ __("Insuline active") }}</header>
        <content class="card-body">
            {{__('Cliquez et tracez une zone pour zoomer')}}
            <div id="chart" class="highcharts-light"></div>
        </content>
        <x-iob renderTo="chart" :data="$data"/>
    </div>
@endsection
