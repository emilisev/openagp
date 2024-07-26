@extends('layouts.mainlayout')
@php /** @var App\Models\DiabetesData $data */ @endphp
@section('content')
    <h1>{{ __("Batterie") }}</h1>
    <div class="card">
        <header class="card-title">
            @include('layouts.sub.reportDateSingle', ['dailyData' => $data, 'allowChange' => true])
        </header>
        <content class="card-body">
            <div id="chart" class="highcharts-light"></div>
        </content>
        <x-battery renderTo="chart" :data="$data"/>
    </div>
@endsection
