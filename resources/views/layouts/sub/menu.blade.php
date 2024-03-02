@php
    use \App\Helpers\CustomMenu;
    use Spatie\Menu\Laravel\Link;
    use Jenssegers\Agent\Agent;

    $menu = CustomMenu::new()->addClass('nav')->addClass('flex-column');
    $agent = new Agent();
    $searchForm = view('layouts.sub.searchForm')->render();
    $languageSwitch = view('layouts.sub.languageSwitch')->render();
    $menu
    //->add(View::create('layouts.sub.formcalendar')->addParentClass('nav-item'))
    ->submenu(__('Statistiques'), CustomMenu::new()->addClass('nav')->addClass('flex-column')
        ->addMenuLink(Link::toUrl('/agp', __('AGP')))
        ->addMenuLink(Link::toUrl('/treatment', __('Traitements')))
        ->addMenuLink(Link::toUrl('/timeInRange', __('Temps dans la cible')))
    )
    ->submenu(__('Profil détaillé'), CustomMenu::new()->addClass('nav')->addClass('flex-column')
        ->addMenuLink(Link::toUrl('/daily', __('Quotidien')))
        ->add(\Spatie\Menu\Html::raw($searchForm))
        ->addMenuLink(Link::toUrl('/weekly', __('Hebdo')))
        ->addMenuLink(Link::toUrl('/monthly', __('Mensuel')))
        ->addMenuLink(Link::toUrl('/ratio', __('Ratios')))
    );

    /*$menu->url('/login', "Se connecter");
    $menu->url('/register', "Créer un compte");*/
    $menu->add(\Spatie\Menu\Html::raw($languageSwitch));
    $menu->addMenuLink(Link::toUrl('/logout', __('Se déconnecter')));
    $menu->setActiveFromRequest();
@endphp
<nav id="navbar" class="collapse navbar-collapse border">
    {{ $menu }}
</nav>

