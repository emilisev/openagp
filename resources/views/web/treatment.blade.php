@extends('layouts.mainlayout')
@php /** @var App\Models\DiabetesData $data */ @endphp
@section('content')
    <h1>Traitement</h1>
    @include('layouts.sub.reportDate')
    <div id="treatment" class="card">
        <header class="card-title">Glycémie et traitements</header>
        <content class="card-body">
            <div id="treatment-chart" class="highcharts-light"></div>
        </content>
        <x-treatment renderTo="treatment-chart" :data="$data"/>
    </div>
@endsection
