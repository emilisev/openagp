@extends('layouts.nosidebar')
@php /** @var App\Models\DiabetesData $data */ @endphp
@section('content')
    <div class="card">
        <header><span>Déconnexion</span></header>
        <content>
            Vous avez été déconnecté avec succès.<br/>
            <a href="/" class="btn btn-primary">Accueil</a>
        </content>
    </div>
@endsection
