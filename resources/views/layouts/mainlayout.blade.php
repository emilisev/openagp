<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('layouts.sub.htmlhead')
<body>
@include('layouts.sub.header')
<div class="container">
    <div class="row">
        @if(strlen(Request::session()->get('url')) > 0)
            @include('layouts.sub.menu')
        @endif
        <div class="col-lg">
            @yield('content')
        </div>
    </div>
</div>
@include('layouts.sub.footer')
</body>
</html>
