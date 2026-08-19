<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IotKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('X-API-KEY');
        if (!$key || $key !== config('app.iot_api_key')) {
            abort(401, 'Unauthorized');
        }
        return $next($request);
    }
}
