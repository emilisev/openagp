@php
use Spatie\Menu\Laravel\Menu;
use Jenssegers\Agent\Agent;

$menu = Menu::new();
$agent = new Agent();
if(strlen(Request::session()->get('url')) > 0) {
	if(!$agent->isMobile()) {
        $menu->url('/agp', 'AGP')
        ->url('/daily', 'Quotidien')
        ->url('/weekly', 'Semainier');
    }

    $menu->url('/logout', 'Se déconnecter');
}
$menu->setActiveFromRequest();

@endphp
<header class="menu">
    <div class="layout-row">
        <div><h1>OpenAGP <span style="font-size: 0.6em">#WeAreNotWaiting</span> </h1></div>
        <div class="nav-menu-primary">
            <nav class="navigation">
                {{ $menu }}
                {{--<ul>
                    <li><a href="/agp">AGP</a></li>
                    <li><a href="/daily">Quotidien</a></li>
                    <li><a href="/weekly">Semainier</a></li>
                    <li><a href="/logout">Se déconnecter</a></li>
                </ul>--}}
            </nav>
        </div>
    </div>
</header>
