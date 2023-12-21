@extends('layouts.mainlayout')

@section('content')
    @include('layouts.sub.languageSwitch')
    @if (!empty($error))
        @include('layouts.sub.error')
        @include('layouts.sub.sourceForm')
    @else
        @include('layouts.sub.sourceForm')
        @include('layouts.sub.security')
    @endif
@endsection
