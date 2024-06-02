<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class HandleParams {
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
        $url = $request->post()['url'] ?? $request->session()->get('url');
        $dates = $request->post()['dates'] ?? $request->session()->get('dates');

        if(empty($url) && $request->route()->getName() != 'welcome') {
            return redirect()->route('welcome',[]);
        }

        $yesterday = microtime(true) - 60 * 60 * 24;
        $twoWeeks = microtime(true) - 60 * 60 * 24 * 14;
        if(empty($dates)) {
            $dates = date('d/m/Y', $twoWeeks)." - ".date('d/m/Y', $yesterday);
        }

        //var_dump($request->session()->all(), $url);
        /*var_dump($url, $apiSecret, $dates);
        die();*/

        if(!empty($request->post()['url'])) {
            $request->session()->put('url', @$request->post()['url']);
        }
        if(!empty($request->post()['apiSecret'])) {
            $request->session()->put('apiSecret', @$request->post()['apiSecret']);
        }
        if(!empty($request->post()['dates'])) {
            $request->session()->put('dates', $dates);
        }
        if($request->route()->getName() == 'daily') {
            if(!empty($request->get('day'))) {
                $startDate = $endDate = $request->get('day');
            } else {
                $startDate = $endDate = date('d/m/Y');
            }
        } else {
            list($startDate, $endDate) = explode(" - ", $dates);
        }
        if(strlen($request->get('focusOnNight'))) {
            $request->session()->put('focusOnNight', $request->get('focusOnNight'));
        }


        $request->session()->put('startDate', $startDate);
        $request->session()->put('endDate', $endDate);

        $response = $next($request);
        return $response;
    }

}
