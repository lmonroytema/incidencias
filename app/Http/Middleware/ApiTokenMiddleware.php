<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Consultant;

class ApiTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-API-TOKEN');
        if (!$token) {
            return response()->json(['message' => 'Token requerido'], 401);
        }

        $consultant = Consultant::where('api_token', $token)->first();
        if (!$consultant) {
            return response()->json(['message' => 'Token inválido'], 401);
        }

        $request->attributes->set('consultant', $consultant);
        return $next($request);
    }
}
