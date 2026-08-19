<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use League\Config\Exception\ValidationException;
use Nette\Utils\Json;

class AuthController extends Controller
{

    public function getAdminUser()
    {
        $admin = User::where('role_id', 1)->first();
        return response()->json($admin);
    }
    //required|string|regex:/^[a-z0-9\-_]+$/|min:3|max:50|unique:users
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string|min:3|max:50|',
            'password' => 'required'
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'ပေးထားသော အချက်အလက်များ မှားယွင်းနေပါသည်။'], 422);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    // app/Http/Controllers/AuthController.php

    public function logout(Request $request)
    {
        //remove fcm token for unnecessary background notis after logout
        $request->user()->update([
            'fcm_token' => null
        ]);
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        if($request->user()->role->name == 'rider') $request->user()->update(['status' => 'inactive']);

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
}
