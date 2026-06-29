<?php

namespace App\Repositories;

use App\Http\Resources\Api\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthRepository
{

    public function attemptSessionLogin(array $credentials): ?User
    {
        if (!Auth::attempt($credentials)) {
            return null;
        }

        request()->session()->regenerate();

        $user = Auth::user();
        return $user;
    }

    public function attemptTokenLogin(array $credentials): ?array
    {
        if (!Auth::attempt($credentials)) {
            return null;
        }

        $user = Auth::user();
        $token = $user->createToken('API Token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }

}
