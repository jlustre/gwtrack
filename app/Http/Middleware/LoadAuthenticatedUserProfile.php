<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoadAuthenticatedUserProfile
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            $user->unsetRelation('profile');
            $user->load('profile');
        }

        return $next($request);
    }
}
