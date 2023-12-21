<?php

namespace App\Helpers;

use Spatie\Menu\Laravel\Link;
use Spatie\Menu\Laravel\Menu;

class CustomMenu extends Menu {
    function addMenuLink(Link $_link) {
        return $this->add($_link->addClass('nav-link')->addParentClass('nav-item'));
    }
}
