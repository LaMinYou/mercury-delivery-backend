<?php

namespace App\Http\Controllers;

use App\Models\Order;
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

    // public function logout(Request $request)
    // {
    //     $user = $request->user();
    //     if($user->role->name == 'rider'){
    //         $activeOrders = $user->riderOrders->whereIn('delivery_status', ['picking', 'delivering'])->exists();
    //     }
    //     //remove fcm token for unnecessary background notis after logout
    //     $request->user()->update([
    //         'fcm_token' => null
    //     ]);
    //     // Revoke the token that was used to authenticate the current request
    //     $request->user()->currentAccessToken()->delete();

    //     if($request->user()->role->name == 'rider') $request->user()->update(['status' => 'inactive']);

    //     return response()->json([
    //         'message' => 'Successfully logged out'
    //     ]);
    // }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            // ၁။ User Session မရှိတော့ပါက Direct Return ပြန်မည်
            if (!$user) {
                return response()->json([
                    'message' => 'Successfully logged out'
                ], 200);
            }

            // ၂။ Rider ဖြစ်မဖြစ် စစ်ဆေးခြင်း (Safe Optional Check)
            $roleName = $user->role ? $user->role->name : null;

            if ($roleName === 'rider') {
                // Active orders ရှိမရှိ စစ်ဆေးမည်
                $hasActiveOrders = $user->riderOrders()
                    ->whereIn('delivery_status', ['picking', 'delivering'])
                    ->exists();

                if ($hasActiveOrders) {
                    return response()->json([
                        'message' => 'You have active orders in progress. Please complete or transfer your assigned orders before logging out.'
                    ], 400); // 400 Bad Request
                }

                // Active order မရှိပါက status ကို inactive ပြောင်းမည်
                $user->update(['status' => 'inactive']);
            }

            // ၃။ FCM Token ကို ရှင်းထုတ်မည်
            $user->update([
                'fcm_token' => null
            ]);

            // ၄။ Current Access Token ကို လုံခြုံစွာ ဖျက်မည်
            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }

            return response()->json([
                'message' => 'Successfully logged out'
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Logout Error: ' . $e->getMessage());

            // Error တက်ခဲ့လျှင်ပင် Frontend ဘက်တွင် Storage clean လုပ်နိုင်ရန် 200 ပြန်ပေးပါမည်
            return response()->json([
                'message' => 'Logged out with cleanup'
            ], 200);
        }
    }
}
