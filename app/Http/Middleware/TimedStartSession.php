<?php
namespace App\Http\Middleware;

use BeyondCode\ServerTiming\Facades\ServerTiming;
use Illuminate\Http\Request;
use Closure;
use Illuminate\Routing\Route;

class TimedStartSession extends \Illuminate\Session\Middleware\StartSession {

    public function handle($request, $next) {
        ServerTiming::start('StartSession');
        if (! $this->sessionConfigured()) {
            return $next($request);
        }

        $session = $this->getSession($request);

        if ($this->manager->shouldBlock() ||
            ($request->route() instanceof Route && $request->route()->locksFor())) {
            return $this->handleRequestWhileBlocking($request, $session, $next);
        }

        ServerTiming::stop('StartSession');

        return $this->handleStatefulRequest($request, $session, $next);
    }
}
