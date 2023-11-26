<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Request;

class UserController extends BaseController {

    public function logout() {
        Request::session()->invalidate();
        return view('web.logout');
    }

}
