<?php

namespace App\Http\Middleware;

use Closure;

class EnableLoginWithAuthentik
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        dump("auth -> handle");
        
        if (config('services.authentik.enabled')) {
            return $next($request);
        }

        abort(404);
    }
}
