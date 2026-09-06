<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\SignInRequest;
use App\Http\Requests\Account\SignUpRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private function respondWithToken(User $user)
    {
        $newToken = $user->createToken('auth_token-'.$user->id);
        $user->tokens()->where('id', '!=', $newToken->accessToken->id)->delete();

        return response()->json([
            'auth_token' => $newToken->plainTextToken,
            'id' => $user->id,
        ]);
    }

    public function signIn(SignInRequest $request)
    {
        $user = User::query()->where('login', $request->json('login'))->first();
        if (!Hash::check($request->json('password'), $user->password))
        {
            return response([
                'message' => 'Access Denied'
            ], 403);
        }

        return $this->respondWithToken($user);
    }

    public function signUp(SignUpRequest $request)
    {
        //Create user
        $user = new User();
        $user->password = $request->json('password');
        $user->login = $request->json('login');
        $user->save();

        return $this->respondWithToken($user);
    }

    public function signOut(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response([
            'message' => 'Logged out'
        ]);
    }
}