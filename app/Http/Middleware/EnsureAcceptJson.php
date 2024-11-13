<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EnsureAcceptJson
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');
        
        $response = $next($request);

        if (!$response instanceof \Illuminate\Http\JsonResponse) {
            if ($response instanceof Response) {
                $content = $response->getContent();

                $decoded = json_decode($content);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data = $decoded;
                } else {
                    $data = ['message' => $content];
                }

                $response = response()->json(
                    $data,
                    $response->status()
                );
            }
        }
        
        $response->header('Content-Type', 'application/json');
        
        return $response;
    }
}