@php
    use Spatie\Menu\Laravel\Link;
    use Spatie\Menu\Laravel\Menu;
    use Jenssegers\Agent\Agent;

    class CustomMenu extends Menu {
        function addMenuLink(Link $_link) {
            return $this->add($_link->addClass('nav-link')->addParentClass('nav-item'));
        }
    }

    $menu = CustomMenu::new()->addClass('nav')->addClass('flex-column');
    $agent = new Agent();
    $menu
    //->add(View::create('layouts.sub.formcalendar')->addParentClass('nav-item'))
    ->submenu('Statistiques', CustomMenu::new()->addClass('nav')->addClass('flex-column')
        ->addMenuLink(Link::toUrl('/agp', 'AGP'))
        ->addMenuLink(Link::toUrl('/timeInRange', 'Temps dans la cible'))
        ->addMenuLink(Link::toUrl('/treatment', 'Traitements'))
    )
    ->submenu('Profil détaillé', CustomMenu::new()->addClass('nav')->addClass('flex-column')
        ->addMenuLink(Link::toUrl('/daily', 'Quotidien'))
        ->addMenuLink(Link::toUrl('/weekly', 'Hebdo'))
        ->addMenuLink(Link::toUrl('/monthly', 'Mensuel'))
    );

    /*$menu->url('/login', "Se connecter");
    $menu->url('/register', "Créer un compte");*/
    $menu->addMenuLink(Link::toUrl('/logout', 'Se déconnecter'));
    $menu->setActiveFromRequest();
@endphp
<nav id="navbar" class="collapse navbar-collapse border">
    {{ $menu }}
</nav>

