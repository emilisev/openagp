<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @include('layouts.sub.htmlhead')
    <body>
        @include('layouts.sub.header')
        @includeWhen(strlen(Request::session()->get('url')) > 0, 'layouts.sub.menu')
        <div id="main">
            @yield('content')
            @include('layouts.sub.footer')
        </div>
    </body>
</html>
