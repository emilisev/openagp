<?php
namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;

class ServerTimingMiddleware extends \BeyondCode\ServerTiming\Middleware\ServerTimingMiddleware {

    public function handle(Request $request, Closure $next) {
        $response = parent::handle($request, $next);

        $response->setContent(str_replace('</body>', $this->generateFooter().'</body>', $response->getContent()));

        return $response;
    }

    protected function generateFooter(): string {
        $footer = '<footer class="fixed-bottom">';
        $events = $this->timing->events();
        unset($events['App']);
        foreach($events as $label => $duration) {
            $duration = round($duration)/1000;
            $footer .= "$label : {$duration}s<br/>";
        }
        $footer .= '<br/><br/></footer>';
        return $footer;
    }
}
