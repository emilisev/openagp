@extends('layouts.nosidebar')
@php /** @var App\Models\DiabetesData $data */ @endphp
@section('content')
    <div class="card">
        <header><span>{{ __("Déconnexion") }}</span></header>
        <content>
            {{ __("Vous avez été déconnecté avec succès.") }}
            <a href="/agp" class="btn btn-primary">{{ __("Accueil") }}</a>
        </content>
    </div>
@endsection
