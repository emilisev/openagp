@php

    use \App\Helpers\CustomMenu;
    use Spatie\Menu\Laravel\Link;


    $languages = CustomMenu::new()->addClass('dropdown-menu');
    foreach(config('languages.list') as $full => $short) {
        $languages->addMenuLink(
        	Link::toUrl(Request::route()->getName()."?language=$short",
             '<i class="fi fi-'.$short.'"></i>'.config('languages.labels')[$full])
        );
    }
@endphp

<div class="dropdown">
    <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
        <span class="fi fi-{{ config('languages.list')[App::currentLocale()] }}"></span>
    </button>
    {{ $languages }}
</div>
