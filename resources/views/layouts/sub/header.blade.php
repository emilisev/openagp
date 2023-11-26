@php /** @uses Spatie\Menu\Laravel\Menu $menu */
/*$menu = Menu::new();
if(Request::session()->has('url')) {
    $menu->url('/agp', 'AGP')
    ->url('/daily', 'Quotidien')
    ->url('/weekly', 'Semainier')
    ->url('/logout', 'Se déconnecter');
}
$menu->setActiveFromRequest()*/
@endphp
<header class="menu">
    <div class="layout-row">
        <div><h1>OpenAGP #WeAreNotWaiting</h1></div>
        <div class="nav-menu-primary">
            <nav class="navigation">
                {{--{{ $menu }}--}}
                <ul>
                    <li><a href="/agp">AGP</a></li>
                    <li><a href="/daily">Quotidien</a></li>
                    <li><a href="/weekly">Semainier</a></li>
                    <li><a href="/logout">Se déconnecter</a></li>
                </ul>
            </nav>
        </div>
    </div>
</header>
