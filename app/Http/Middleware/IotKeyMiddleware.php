<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IotKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $legacyKey = $request->header('X-API-KEY');
        $deviceKey = $request->header('X-DEVICE-KEY');
        $moduleUid = $request->header('X-MODULO-UID');
        $configuredKey = config('app.iot_device_key') ?: config('app.iot_api_key');
        $configuredUid = config('app.iot_module_uid');

        $legacyValid = is_string($legacyKey)
            && is_string($configuredKey)
            && $configuredKey !== ''
            && hash_equals($configuredKey, $legacyKey);
        $deviceValid = is_string($deviceKey)
            && is_string($configuredKey)
            && $configuredKey !== ''
            && hash_equals($configuredKey, $deviceKey)
            && (!$configuredUid || (is_string($moduleUid) && hash_equals($configuredUid, $moduleUid)));

        if (!$legacyValid && !$deviceValid) {
            abort(401, 'Unauthorized');
        }
        return $next($request);
    }
}
