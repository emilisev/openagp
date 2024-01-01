<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class ChooseLanguage {
/********************** PUBLIC METHODS *********************/
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param string|null ...$guards
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, \Closure $next, ...$guards) {
        if(!empty($request->get('language'))) {
            App::setLocale($request->get('language'));
        } else {
            if(!empty($request->session()->get('language'))) {
                App::setLocale($request->session()->get('language'));
            }
        }
        $response = $next($request);
        if(!empty($request->get('language'))) {
            $request->session()->put('language', $request->get('language'));
        }
        return $response;
    }

}
