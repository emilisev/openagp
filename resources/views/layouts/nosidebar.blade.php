<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="height:100%">
@include('layouts.sub.htmlhead')
<body style="height:100%">
@include('layouts.sub.header')
<div class="layout-row responsive">
    @yield('content')
</div>
</body>
</html>
