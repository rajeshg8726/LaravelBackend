<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!($request->user() instanceof Admin)) {
            return response()->json(['message' => 'Forbidden. Admin access required.'], 403);
        }
        return $next($request);
    }
}
