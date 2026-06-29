<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\AuthRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class AuthService
{

    private $authRepository;

    public function __construct(AuthRepository $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function login(array $data): User
    {
        $user = $this->authRepository->attemptSessionLogin([
            'email'     => $data['email'],
            'password'  => $data['password']
        ]);

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.']
            ]);
        }

        return $user->load('roles');
    }

    public function tokenLogin(array $data): array
    {
        $result = $this->authRepository->attemptTokenLogin([
            'email'     => $data['email'],
            'password'  => $data['password']
        ]);

        if (!$result) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.']
            ]);
        }

        $result['user']->load('roles');

        return $result;
    }
}
