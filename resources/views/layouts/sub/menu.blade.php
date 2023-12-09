@php
    use Spatie\Menu\Laravel\Link;
    use Spatie\Menu\Laravel\Menu;
    use Jenssegers\Agent\Agent;use Spatie\Menu\Laravel\View;

    class CustomMenu extends Menu {
        function addMenuLink(Link $_link) {
            return $this->add($_link->addClass('nav-link')->addParentClass('nav-item'));
        }
    }

    $menu = CustomMenu::new()->addClass('nav')->addClass('flex-column');
    $agent = new Agent();
    $menu
    //->add(View::create('layouts.sub.formcalendar')->addParentClass('nav-item'))
    ->addMenuLink(Link::toUrl('/agp', 'AGP'))
    ->addMenuLink(Link::toUrl('/daily', 'Quotidien'))
    ->addMenuLink(Link::toUrl('/weekly', 'Semainier'))
    ->addMenuLink(Link::toUrl('/timeInRange', 'Temps dans la cible'))
    ;

    /*$menu->url('/login', "Se connecter");
    $menu->url('/register', "Créer un compte");*/
    $menu->addMenuLink(Link::toUrl('/logout', 'Se déconnecter'));
    $menu->setActiveFromRequest();
@endphp
<nav id="navbar" class="collapse navbar-collapse border">
    {{ $menu }}
</nav>

