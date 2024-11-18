<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\ResponseJson;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key');
        
        if (!$apiKey || $apiKey !== config('app.api_key')) {
            return ResponseJson::unauthorizeResponse('Invalid API Key', []);
        }

        return $next($request);
    }
}
