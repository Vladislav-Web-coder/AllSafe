<?php

namespace Interfaces\Http\Controllers\Api\V1;

use App\Application\Auth\Commands\LoginCommand;
use App\Application\Auth\Commands\LogoutCommand;
use App\Application\Auth\Commands\RefreshTokenCommand;
use App\Application\Auth\UseCases\LoginUseCase;
use App\Application\Auth\UseCases\LogoutUseCase;
use App\Application\Auth\UseCases\RefreshTokenUseCase;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Services\AuditService;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\RefreshTokenRepositoryInterface;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\Services\DeviceDetector;
use App\Domain\Identity\Services\EmailService;
use App\Http\Controllers\Controller;
use App\Interfaces\Http\Requests\Auth\LoginRequest;
use App\Interfaces\Http\Requests\Auth\LogoutRequest;
use App\Interfaces\Http\Requests\Auth\RefreshRequest;
use App\Interfaces\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Interfaces\Http\Resources\Auth\AuthTokensResource;

class AuthController extends Controller
{
    public function __construct(
        private LoginUseCase $loginUseCase,
        private RefreshTokenUseCase $refreshTokenUseCase,
        private RefreshTokenRepositoryInterface $refreshTokens,
        private LogoutUseCase $logoutUseCase,
        private AuditService $audit,
        private UserRepositoryInterface $user,
        private EmailService $emailService,
        private DeviceDetector $deviceDetector,
    ) {}

    /**
     * Вход в систему.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $command = new LoginCommand(
                email: $request->validated('email'),
                password: $request->validated('password'),
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );

            $tokens = $this->loginUseCase->handle($command);

            $this->audit->log(
                action: AuditAction::AuthLoginSuccess,
                userId: $tokens->user->id,
                userEmail: $tokens->user->email,
                request: $request,
            );

            return response()->json(new AuthTokensResource($tokens));
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            $this->audit->log(
                action: AuditAction::AuthLoginFailed,
                userEmail: $request->validated('email'),
                result: 'error',
                request: $request,
            );

            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }
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

        $this->audit->logFromRequest(
            action: AuditAction::AuthLogout,
            request: $request,
        );

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

    /**
     * Регистрация нового пользователя (шаг 1: отправка кода).
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Проверяем, что email не занят
        $existingUser = $this->user->findByEmail($validated['email']);

        if ($existingUser) {
            throw ValidationException::withMessages([
                'email' => ['Пользователь с таким email уже существует.'],
            ]);
        }

        // Отправляем код верификации
        $this->emailService->sendVerificationCode(
            email: $validated['email'],
            purpose: 'register',
        );

        // Сохраняем временные данные регистрации в кэш
        Cache::put("register:{$validated['email']}", [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ], 900); // 15 минут

        return response()->json([
            'message' => 'Код подтверждения отправлен на ваш email.',
            'email' => $validated['email'],
        ]);
    }

    /**
     * Подтверждение регистрации (шаг 2: ввод кода).
     */
    public function verifyRegistration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        // Проверяем код
        $isValid = $this->emailService->verifyCode(
            email: $validated['email'],
            purpose: 'register',
            code: $validated['code'],
        );

        if (! $isValid) {
            throw ValidationException::withMessages([
                'code' => ['Неверный или истёкший код подтверждения.'],
            ]);
        }

        // Получаем временные данные
        $registrationData = Cache::get("register:{$validated['email']}");

        if (! $registrationData) {
            throw ValidationException::withMessages([
                'email' => ['Данные регистрации не найдены. Попробуйте зарегистрироваться снова.'],
            ]);
        }

        // Создаём пользователя
        $user = DB::connection('pgsql_identity')->transaction(function () use ($registrationData) {
            return User::create([
                'name' => $registrationData['name'],
                'email' => $registrationData['email'],
                'password' => Hash::make($registrationData['password']),
                'is_active' => true,
            ]);
        });

        // Удаляем временные данные
        Cache::forget("register:{$validated['email']}");

        // Автоматический вход
        $accessToken = $user->createToken('api', ['base']);
        $refreshPlain = Str::random(64);

        $this->refreshTokens->createForUser(
            user: $user,
            plainToken: $refreshPlain,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            deviceName: 'web',
        );

        return response()->json([
            'message' => 'Регистрация успешна.',
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshPlain,
            'token_type' => 'Bearer',
            'expires_in' => 1800,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    /**
     * Повторная отправка кода верификации.
     */
    public function resendVerificationCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $this->emailService->sendVerificationCode(
            email: $validated['email'],
            purpose: 'register',
        );

        return response()->json([
            'message' => 'Код подтверждения отправлен повторно.',
        ]);
    }

    /**
     * Запрос сброса пароля.
     */
    /**
     * Запрос сброса пароля.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = $this->user->findByEmail($validated['email']);

        if (! $user) {
            // Не раскрываем существование пользователя
            return response()->json([
                'message' => 'Если пользователь с таким email существует, ссылка для сброса пароля отправлена.',
            ]);
        }

        // Генерируем токен сброса
        $token = Str::random(64);

        DB::connection('pgsql_identity')->table('password_reset_tokens')->updateOrInsert(
            ['email' => $validated['email']],
            [
                'token' => hash('sha256', $token),
                'created_at' => now(),
            ]
        );

        // Отправляем письмо со ссылкой
        $this->emailService->sendPasswordReset(
            email: $validated['email'],
            token: $token,
        );

        return response()->json([
            'message' => 'Если пользователь с таким email существует, ссылка для сброса пароля отправлена.',
        ]);
    }

    /**
     * Сброс пароля по токену.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $hashedToken = hash('sha256', $validated['token']);

        $resetRecord = DB::connection('pgsql_identity')
            ->table('password_reset_tokens')
            ->where('token', $hashedToken)
            ->first();

        if (! $resetRecord) {
            throw ValidationException::withMessages([
                'token' => ['Ссылка сброса пароля недействительна.'],
            ]);
        }

        $createdAt = \Carbon\Carbon::parse($resetRecord->created_at);

        if ($createdAt->diffInHours(now()) > 1) {
            // Удаляем истёкший токен
            DB::connection('pgsql_identity')
                ->table('password_reset_tokens')
                ->where('token', $hashedToken)
                ->delete();

            throw ValidationException::withMessages([
                'token' => ['Ссылка сброса пароля истекла. Запросите новую.'],
            ]);
        }

        $email = $resetRecord->email;

        $user = $this->user->findByEmail($email);

        if (! $user) {
            throw ValidationException::withMessages([
                'token' => ['Пользователь не найден.'],
            ]);
        }

        DB::connection('pgsql_identity')
            ->table('password_reset_tokens')
            ->where('token', $hashedToken)
            ->delete();

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $user->tokens()->delete();

        $accessToken = $user->createToken('api', ['base']);
        $refreshPlain = Str::random(64);

        $this->refreshTokens->createForUser(
            user: $user,
            plainToken: $refreshPlain,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            deviceName: 'web',
        );

        return response()->json([
            'message' => 'Пароль успешно изменён.',
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshPlain,
            'token_type' => 'Bearer',
            'expires_in' => 1800,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Смена email (шаг 1: отправка кода на текущую почту).
     */
    public function changeEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        // Проверяем пароль
        if (! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Неверный пароль.'],
            ]);
        }

        // Проверяем, что новый email не занят
        $existingUser = $this->user->findByEmail($validated['email']);

        if ($existingUser && $existingUser->id !== $user->id) {
            throw ValidationException::withMessages([
                'email' => ['Этот email уже занят.'],
            ]);
        }

        // Отправляем код на ТЕКУЩУЮ почту
        $this->emailService->sendVerificationCode(
            email: $user->email,
            purpose: 'change_email',
            userId: $user->id,
        );

        // Сохраняем новый email в кэш
        Cache::put("change_email:{$user->id}", [
            'new_email' => $validated['email'],
        ], 900); // 15 минут

        return response()->json([
            'message' => 'Код подтверждения отправлен на вашу текущую почту.',
            'current_email' => $user->email,
        ]);
    }

    /**
     * Подтверждение смены email (шаг 2: ввод кода).
     */
    public function verifyEmailChange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        // Проверяем код на ТЕКУЩЕЙ почте
        $isValid = $this->emailService->verifyCode(
            email: $user->email,
            purpose: 'change_email',
            code: $validated['code'],
        );

        if (! $isValid) {
            throw ValidationException::withMessages([
                'code' => ['Неверный или истёкший код.'],
            ]);
        }

        // Получаем новый email из кэша
        $changeData = Cache::get("change_email:{$user->id}");

        if (! $changeData || ! isset($changeData['new_email'])) {
            throw ValidationException::withMessages([
                'code' => ['Данные смены email не найдены. Начните процесс заново.'],
            ]);
        }

        $newEmail = $changeData['new_email'];

        // Ещё раз проверяем, что новый email не занят
        $existingUser = $this->user->findByEmail($newEmail);

        if ($existingUser && $existingUser->id !== $user->id) {
            Cache::forget("change_email:{$user->id}");

            throw ValidationException::withMessages([
                'code' => ['Этот email уже занят другим пользователем.'],
            ]);
        }

        // Обновляем email
        $user->update([
            'email' => $newEmail,
        ]);

        Cache::forget("change_email:{$user->id}");

        return response()->json([
            'message' => 'Email успешно изменён.',
            'new_email' => $newEmail,
        ]);
    }

    /**
     * Смена пароля.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Неверный текущий пароль.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $currentTokenId = $request->user()->currentAccessToken()->id;
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        return response()->json([
            'message' => 'Пароль успешно изменён.',
        ]);
    }
    /**
     * Список активных сессий пользователя.
     */
    /**
     * Список активных сессий пользователя.
     */
    public function sessions(Request $request): JsonResponse
    {
        $user = $request->user();

        $currentTokenId = $user->currentAccessToken()->id;

        $tokens = $user->tokens()->get();

        $sessions = $tokens->map(function ($token) use ($currentTokenId) {
            $metadata = \App\Domain\Identity\Entities\SessionMetadata::query()
                ->where('token_id', $token->id)
                ->first();

            // Определяем устройство из user_agent
            $deviceInfo = $this->deviceDetector->detect($metadata?->user_agent);

            return [
                'id' => $token->id,
                'name' => $token->name,
                'device_name' => $metadata?->device_name ?? $this->deviceDetector->getDisplayName($metadata?->user_agent),
                'browser' => $deviceInfo['browser'],
                'platform' => $deviceInfo['platform'],
                'device_type' => $deviceInfo['device_type'],
                'icon' => $deviceInfo['icon'],
                'ip_address' => $metadata?->ip_address,
                'user_agent' => $metadata?->user_agent,
                'last_activity_at' => $token->last_used_at ?? $metadata?->last_activity_at,
                'created_at' => $token->created_at,
                'is_current' => $token->id === $currentTokenId,
            ];
        });

        return response()->json([
            'data' => $sessions,
        ]);
    }

    /**
     * Завершить конкретную сессию.
     */
    public function terminateSession(Request $request, int $sessionId): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        // Проверяем пароль
        if (! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Неверный пароль.'],
            ]);
        }

        $currentTokenId = $user->currentAccessToken()->id;

        // Нельзя завершить текущую сессию через этот метод
        if ($sessionId === $currentTokenId) {
            throw ValidationException::withMessages([
                'session' => ['Нельзя завершить текущую сессию. Используйте выход.'],
            ]);
        }

        $token = $user->tokens()->where('id', $sessionId)->first();

        if (! $token) {
            abort(404, 'Сессия не найдена.');
        }

        $token->delete();

        \App\Domain\Identity\Entities\SessionMetadata::query()
            ->where('token_id', $sessionId)
            ->delete();

        $this->refreshTokens->deleteByTokenableId($sessionId);

        return response()->json([
            'message' => 'Сессия завершена.',
        ]);
    }

    /**
     * Завершить все сессии кроме текущей.
     */
    public function terminateAllSessions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        // Проверяем пароль
        if (! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Неверный пароль.'],
            ]);
        }

        $currentTokenId = $user->currentAccessToken()->id;

        $otherTokenIds = $user->tokens()
            ->where('id', '!=', $currentTokenId)
            ->pluck('id');

        $user->tokens()
            ->where('id', '!=', $currentTokenId)
            ->delete();

        \App\Domain\Identity\Entities\SessionMetadata::query()
            ->whereIn('token_id', $otherTokenIds)
            ->delete();

        return response()->json([
            'message' => 'Все сессии кроме текущей завершены.',
        ]);
    }
}
