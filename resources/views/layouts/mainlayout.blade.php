<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('layouts.sub.htmlhead')
<body>
@include('layouts.sub.header')
<div class="layout-row responsive">
    @include('layouts.sub.form')
    @yield('content')
</div>
</body>
</html>
