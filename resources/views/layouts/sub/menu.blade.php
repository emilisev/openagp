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
        ->addMenuLink(Link::toUrl('/daytoday', __('Jour après jour')))
        ->add(\Spatie\Menu\Html::raw($searchForm))
        ->addMenuLink(Link::toUrl('/weekly', __('Hebdo')))
        ->addMenuLink(Link::toUrl('/monthly', __('Mensuel'))))
    ->submenu(__('Analyse'), CustomMenu::new()->addClass('nav')->addClass('flex-column')
        ->addMenuLink(Link::toUrl('/ratio', __('Ratios')))
        ->addMenuLink(Link::toUrl('/sensitivity', __('Sensibilité')))
        ->addMenuLink(Link::toUrl('/iob', __('Insuline active')))
        ->addMenuLink(Link::toUrl('/overlay', __('Superposition')))
        ->addMenuLink(Link::toUrl('/insulin-profile', __('Profil')))
        ->addMenuLink(Link::toUrl('/profile-percentage', __('Pourcentage du profil')))
        ->addMenuLink(Link::toUrl('/battery', __('Batterie')))
    )->submenu(__('Actions'), CustomMenu::new()->addClass('nav')->addClass('flex-column')
        ->add(\Spatie\Menu\Html::raw($languageSwitch))
        ->addMenuLink(Link::toUrl('#', '<i class="bis bi-printer-fill"></i> '.__('Imprimer le rapport'))
            ->setAttribute('id', 'print-button'))
        ->addMenuLink(Link::toUrl('/logout', '<i class="bis bi-x-circle-fill"></i> '.__('Se déconnecter')))
    )

    ;

    /*$menu->url('/login', "Se connecter");
    $menu->url('/register', "Créer un compte");*/


    $menu->setActiveFromRequest();
@endphp
<nav id="navbar" class="collapse navbar-collapse border">
    {{ $menu }}
</nav>

