<?php

namespace Interfaces\Http\Controllers\Api\V1;

use App\Application\Auth\Commands\LoginCommand;
use App\Application\Auth\Commands\LogoutCommand;
use App\Application\Auth\Commands\RefreshTokenCommand;
use App\Application\Auth\UseCases\LoginUseCase;
use App\Application\Auth\UseCases\LogoutUseCase;
use App\Application\Auth\UseCases\RefreshTokenUseCase;
use App\Http\Controllers\Controller;
use App\Interfaces\Http\Requests\Auth\LoginRequest;
use App\Interfaces\Http\Requests\Auth\LogoutRequest;
use App\Interfaces\Http\Requests\Auth\RefreshRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Interfaces\Http\Resources\Auth\AuthTokensResource;

class AuthController extends Controller
{
    public function __construct(
        private LoginUseCase $loginUseCase,
        private RefreshTokenUseCase $refreshTokenUseCase,
        private LogoutUseCase $logoutUseCase,
    ) {}

    public function login(LoginRequest $request): AuthTokensResource
    {
        $command = new LoginCommand(
            email: $request->validated('email'),
            password: $request->validated('password'),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        $tokens = $this->loginUseCase->handle($command);

        return new AuthTokensResource($tokens);
    }

    public function refresh(RefreshRequest $request): AuthTokensResource
    {
        $command = new RefreshTokenCommand(
            refreshToken: $request->validated('refresh_token'),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        $tokens = $this->refreshTokenUseCase->handle($command);

        return new AuthTokensResource($tokens);
    }

    public function logout(LogoutRequest $request): JsonResponse
    {
        $command = new LogoutCommand(
            user: $request->user(),
            refreshToken: $request->validated('refresh_token'),
        );

        $this->logoutUseCase->handle($command);

        return response()->json([
            'message' => 'Вы вышли из системы.'
        ]);
    }
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
        ]);
    }

}
