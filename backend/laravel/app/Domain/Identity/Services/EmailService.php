<?php

namespace App\Domain\Identity\Services;

use App\Domain\Identity\Entities\EmailVerificationCode;
use App\Mail\InvitationMail;
use App\Mail\PasswordResetMail;
use App\Mail\VerificationCodeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailService
{
    /**
     * Генерирует и отправляет код верификации.
     */
    public function sendVerificationCode(
        string $email,
        string $purpose,
        ?int $userId = null,
    ): EmailVerificationCode {
        // Удаляем старые коды для этого email и purpose
        EmailVerificationCode::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->where('used', false)
            ->delete();

        // Генерируем 6-значный код
        $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $verificationCode = EmailVerificationCode::create([
            'user_id' => $userId,
            'email' => $email,
            'purpose' => $purpose,
            'code' => hash('sha256', $code),
            'used' => false,
            'expires_at' => now()->addMinutes(15),
        ]);

        // Отправляем письмо
        Mail::to($email)->send(new VerificationCodeMail($code, $purpose));

        return $verificationCode;
    }

    /**
     * Проверяет код верификации.
     */
    public function verifyCode(string $email, string $purpose, string $code): bool
    {
        $verificationCode = EmailVerificationCode::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $verificationCode) {
            return false;
        }

        if ($verificationCode->code !== hash('sha256', $code)) {
            return false;
        }

        $verificationCode->markAsUsed();

        return true;
    }

    /**
     * Отправляет приглашение в организацию.
     */
    public function sendInvitation(
        string $email,
        string $organizationName,
        string $inviterName,
        string $role,
        string $token,
    ): void {
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
        $acceptUrl = "{$frontendUrl}/accept-invitation?token={$token}";

        $roleLabels = [
            'owner' => 'Владелец',
            'admin' => 'Администратор',
            'security_officer' => 'Специалист по ИБ',
            'legal_officer' => 'Юрист',
            'auditor' => 'Аудитор',
            'employee' => 'Сотрудник',
            'viewer' => 'Наблюдатель',
        ];

        Mail::to($email)->send(new InvitationMail(
            organizationName: $organizationName,
            inviterName: $inviterName,
            role: $roleLabels[$role] ?? $role,
            acceptUrl: $acceptUrl,
        ));
    }

    /**
     * Отправляет письмо для сброса пароля.
     */
    public function sendPasswordReset(string $email, string $token): void
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');

        $resetUrl = "{$frontendUrl}/reset-password?token={$token}";

        Mail::to($email)->send(new PasswordResetMail($resetUrl));
    }
}
