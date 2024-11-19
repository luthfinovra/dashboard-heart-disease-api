<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnsureAcceptJson
{
    public function handle(Request $request, Closure $next)
    {
        // Allow multipart/form-data for POST and PUT
        if (($request->isMethod('post') || $request->isMethod('put')) && 
            $request->hasHeader('Content-Type', 'multipart/form-data')) {
            return $next($request);
        }

        // Allow file downloads to pass through
        if ($request->route() && strpos($request->route()->getName(), 'download') !== false) {
            return $next($request);
        }

        // Set Accept header to JSON for other requests
        $request->headers->set('Accept', 'application/json');
        
        $response = $next($request);

        // Only transform non-file responses
        if (!$response instanceof BinaryFileResponse && 
            !$response instanceof StreamedResponse) {
            if (!$response instanceof JsonResponse) {
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
        }
        
        return $response;
    }
}