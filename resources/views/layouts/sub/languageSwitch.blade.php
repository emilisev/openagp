@php

    use \App\Helpers\CustomMenu;
    use Spatie\Menu\Laravel\Link;

    $languages = CustomMenu::new()->addClass('dropdown-menu');
    $languages
    ->addMenuLink(Link::toUrl(Request::route()->getName().'?language=gb', '<i class="fi fi-gb"></i>English'))
    ->addMenuLink(Link::toUrl(Request::route()->getName().'?language=fr', '<i class="fi fi-fr"></i>Français'));
@endphp

<div class="dropdown">
    <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
        <span class="fi fi-{{ App::currentLocale() }}"></span>
    </button>
    {{ $languages }}
</div>
