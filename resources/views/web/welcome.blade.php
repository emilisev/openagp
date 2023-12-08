@extends('layouts.mainlayout')

@section('content')
    @if (!empty($error))
        @include('layouts.sub.error')
        @include('layouts.sub.sourceform')
    @else
        @include('layouts.sub.sourceform')
        @include('layouts.sub.security')
    @endif
@endsection
