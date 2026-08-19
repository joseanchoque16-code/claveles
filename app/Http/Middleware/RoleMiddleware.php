<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (!$user) abort(401);

        // Permite usar: role:admin,operador  (coma) o role:admin role:operador
        $flat = [];
        foreach ($roles as $r) {
            foreach (explode(',', $r) as $rr) {
                $rr = trim($rr);
                if ($rr !== '') $flat[] = $rr;
            }
        }

        if (!in_array($user->role, $flat, true)) abort(403);

        return $next($request);
    }
}
