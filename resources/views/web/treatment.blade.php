@extends('layouts.mainlayout')
@php /** @var App\Models\DiabetesData $data */ @endphp
@section('content')
    <h1>{{ __("Traitements") }}</h1>
    @include('layouts.sub.reportDate')

    <content id="treatment" class="card">
        <header class="card-title">{{ __("Glycémie et traitements") }}</header>
        <content class="card-body">
            <div class="row justify-content-center mb-2">
                @include('cards.average.square')
                <x-treatment type="squares" :data="$data"/>
            </div>

            <div id="treatment-chart" class="highcharts-light"></div>
            <x-treatment renderTo="treatment-chart" :data="$data"/>
        </content>
    </content>
@endsection
