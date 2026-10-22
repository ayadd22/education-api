<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
   
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
