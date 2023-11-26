<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('layouts.sub.htmlhead')
<body>
@include('layouts.sub.header')
<div class="layout-row">
    <div id="sidebar">
        @include('layouts.sub.form')
    </div>
    @yield('content')
</div>
</body>
</html>
