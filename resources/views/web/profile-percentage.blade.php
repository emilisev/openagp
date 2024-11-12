@extends('layouts.mainlayout')
@php
/** @var $data \App\Models\DiabetesData */
@endphp
@section('content')
<h1>{{ __("Pourcentage du profil") }}</h1>
@include('layouts.sub.reportDateRange')
<div class="card">
    <header class="card-title">{{ __("Insuline active") }}</header>
    <content class="card-body">
        <div id="chart" class="highcharts-light"></div>
    </content>
    <x-profilePercentage renderTo="chart" :data="$data"/>
</div>
@endsection
