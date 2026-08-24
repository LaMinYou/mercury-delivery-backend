<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use Laravel\Reverb\Loggers\Log;

class CustomerAuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'login_target' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $field = filter_var($request->login_target, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (User::where($field, $request->login_target)->exists()) {
            return response()->json(['message' => 'sorry email or phone is already in use'], 422);
        }
        $user = User::create([
            'name' => $request->name,
            $field => $request->login_target,
            'password' => Hash::make($request->password),
            'role_id' => 3
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['access_token' => $token, 'user' => $user], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login_target' => 'required|string',
            'password' => 'required|string',
        ]);

        $field = filter_var($request->login_target, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($field, $request->login_target)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'email or password is incorrect'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['access_token' => $token, 'user' => $user]);
    }

    // redirect to google
    public function redirectToGoogle()
    {
        $client = new Client(['verify' => false]);

        return Socialite::driver('google')->setHttpClient($client)->stateless()->redirect();
    }

    // handle callback from Google
    public function handleGoogleCallback()
    {
        try {
            $client = new Client(['verify' => false]);
            $googleUser = Socialite::driver('google')->setHttpClient($client)->stateless()->user();

            $user = User::updateOrCreate([
                'email' => $googleUser->getEmail(),
            ], [
                'name' => $googleUser->getName(),
                // 'password' => Hash::make(Str::random(24)),
                'role_id' => 3
            ]);

            if (!$user->password) {
                $user->password = Hash::make(Str::random(24));
                $user->save();
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            $userData = json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
            ]);

            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:8080'));

            return redirect($frontendUrl . '/social-callback?' . http_build_query([
                'access_token' => $token,
                'user' => $userData
            ]));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Google Login failed', 'error_details' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
{
    try {
        $user = $request->user();

        if ($user) {
            $user->update(['fcm_token' => null]);

            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ], 200);

    } catch (\Exception $e) {
        Log::error('Logout Error: ' . $e->getMessage());

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out with cleanup'
        ], 200);
    }
}
}
