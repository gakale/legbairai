<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebSocketDebugMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Journaliser les informations de la requête pour le débogage
        \Illuminate\Support\Facades\Log::info('WebSocket Auth Debug', [
            'path' => $request->path(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'is_authenticated' => $request->user() ? true : false,
            'user_id' => $request->user() ? $request->user()->id : null,
            'socket_id' => $request->input('socket_id'),
            'channel_name' => $request->input('channel_name'),
        ]);
        
        // Si l'utilisateur n'est pas authentifié, mais que nous sommes en mode développement,
        // nous pouvons permettre l'accès pour faciliter les tests
        if (!$request->user() && app()->environment('local')) {
            \Illuminate\Support\Facades\Log::warning('WebSocket Auth: Allowing unauthenticated access in local environment');
            // Trouver un utilisateur pour les tests (premier utilisateur)
            $testUser = \App\Models\User::first();
            if ($testUser) {
                \Illuminate\Support\Facades\Auth::login($testUser);
                \Illuminate\Support\Facades\Log::info('WebSocket Auth: Logged in test user', ['user_id' => $testUser->id]);
            }
        }
        
        return $next($request);
    }
}
