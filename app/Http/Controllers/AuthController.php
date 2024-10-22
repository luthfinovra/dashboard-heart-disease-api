<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseJson;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Services\AuthUserService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private AuthUserService $authUserService;

    public function __construct(AuthUserService $authUserService)
    {
        $this->authUserService = $authUserService;
    }

    public function register(RegisterUserRequest $request)
    {
        [$success, $message, $data] = $this->authUserService->register(
            $request->name,
            $request->email,
            $request->password,
            $request->institution,
            $request->gender,
            $request->phone_number,
            $request->tujuan_permohonan,
        );

        if (!$success) {
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse($message, $data);
    }

    public function login(LoginUserRequest $request)
    {
        [$success, $message, $data] = $this->authUserService->login(
            $request->email,
            $request->password
        );

        if (!$success) {
            return ResponseJson::failedResponse($message, $data);
        }

        return ResponseJson::successResponse($message, $data);
    }

    public function logout(Request $request)
    {
        $this->authUserService->logout($request->user());
        return ResponseJson::successResponse('Berhasil Logout', []);
    }
}
