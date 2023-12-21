@extends('layouts.mainlayout')
@php /** @var App\Models\DiabetesData $data */ @endphp
@section('content')
    <h1>Mensuel</h1>
    @include('layouts.sub.reportDate')
    <div id="monthly" class="card">
        <header class="card-title">{{ __("Profil glycémique mensuel") }}</header>
        <content class="card-body">
            <div id="monthly-chart" class="highcharts-light"></div>
        </content>
        <x-monthly renderTo="monthly-chart" :data="$data"/>
    </div>
@endsection
