<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * Usage on routes:
     *   ->middleware('role:admin')
     *   ->middleware('role:teacher')
     *   ->middleware('role:admin,teacher')   // any of the listed roles passes
     *
     * This middleware runs after auth:sanctum, so $request->user() is always
     * present. If it is somehow missing, a 401 JSON response is returned.
     *
     * @param  string  ...$roles  Allowed role values (must match UserRole cases).
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        $allowed = array_map(
            fn (string $r) => UserRole::from(trim($r)),
            $roles,
        );

        if (! in_array($user->role, $allowed, strict: true)) {
            return response()->json(['message' => 'Insufficient permissions.'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
