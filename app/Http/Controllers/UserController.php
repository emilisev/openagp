<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;

class UserController extends BaseController {

    public function logout() {
        Request::session()->invalidate();
        return view('web.logout');
    }

    public function view() {
        $url = Request::session()->get('url');
        $apiSecret = Request::session()->get('apiSecret');
        $key = "$apiSecret@$url";
        var_dump($url, $apiSecret, Hash::make($key));

    }

}
