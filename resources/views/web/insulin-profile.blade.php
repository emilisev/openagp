@extends('layouts.mainlayout')
@php
/** @var $data \App\Models\DiabetesData */
@endphp
@section('content')
<h1>{{ __("Profils d'insuline") }}</h1>
@include('layouts.sub.reportDateRange')
<div id="profile" class="card">
    @foreach ($data->getProfiles() as $time => $profile)
        @include('cards.profile.main')
    @endforeach
</div>
@endsection
