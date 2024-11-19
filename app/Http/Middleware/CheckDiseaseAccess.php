<?php

// app/Http/Middleware/CheckDiseaseAccess.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\ResponseJson;

class CheckDiseaseAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        if (!$user) {
            return ResponseJson::unauthorizeResponse('Unauthorized Access', []);
        }

        if (in_array($user->role, ['operator', 'admin'])) {
            return $next($request);
        }

        if (($user->role === 'peneliti') && $user->approval_status === 'approved') {
            return $next($request);
        }

        return ResponseJson::forbidenResponse('User is not approved', []);
    }
}