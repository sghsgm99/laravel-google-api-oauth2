<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserLoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ResponseService;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponser;

    public function register()
    {
        /**
         * TODO
         *   - validate request
         *   - create user
         *   - return token
         */
    }

    public function login(UserLoginRequest $request)
    {
        if (!Auth::attempt($request->validated())) {
            return $this->error('Credentials not match', 401);
        }

        $token = auth()->user()->createToken('API Token')->plainTextToken;

        auth()->user()->api_token = $token;
        auth()->user()->save();

        return $this->success([
            'token' => $token,
            'user' => new UserResource(auth()->user())
        ]);
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();

        auth()->user()->api_token = null;
        auth()->user()->save();

        return $this->success([], 'Tokens Revoked');
    }

    public function authUser()
    {
        return $this->success([
            'token' => auth()->user()->api_token,
            'user' => new UserResource(auth()->user())
        ]);
    }
}
