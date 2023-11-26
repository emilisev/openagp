@extends('layouts.mainlayout')

@section('content')
    @if (!empty($error))
        @include('layouts.sub.error')
    @else
        @include('layouts.sub.security')
    @endif
@endsection
