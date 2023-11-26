<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="height:100%">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>OpenAGP</title>
    {{--<link rel="stylesheet" href="{{ URL::asset('css/app.css') }}"/>
    <link rel="stylesheet" href="{{ URL::asset('css/highcharts.css') }}"/>
    <script type="text/javascript" src="{{ URL::asset('js/app.js') }}"></script>--}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="height:100%">

<div class="layout-row">
    <div class="content-card">
        <header><span>Déconnexion</span></header>
        <content>
            Vous avez été déconnecté avec succès.
            <a href="/agp" class="btn btn-primary">Accueil</a>
        </content>
    </div>

</body>
</html>
