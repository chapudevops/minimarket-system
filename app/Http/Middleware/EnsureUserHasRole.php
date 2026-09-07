<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Restringe una ruta a los roles indicados: role:Vendedor,Almacenero
     *
     * La regla vive en User::puedeConRol(), compartida con la directiva @rol de
     * Blade. El Administrador pasa siempre, aunque no este listado: evita que
     * una ruta nueva deje al administrador afuera por olvido.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if ($usuario && $usuario->puedeConRol($roles)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta acción.',
            ], 403);
        }

        abort(403, 'No tienes permiso para acceder a esta sección.');
    }
}
