<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            current_workspace();
        }

        return $next($request);
    }
}
