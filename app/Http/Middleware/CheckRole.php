<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // အရင်ဆုံး Login ဝင်ထားသလား စစ်မယ်
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $userRole = (string) auth()->user()->role_id;

        // လက်ရှိ user ရဲ့ role_id ဟာ ခွင့်ပြုထားတဲ့ roles list ထဲမှာ ပါသလား စစ်မယ်
        if (!in_array($userRole, $roles)) {
            return response()->json(['message' => 'ဒီနေရာကို ဝင်ခွင့်မရှိပါ (Forbidden)'], 403);
        }

        return $next($request);
    }
}