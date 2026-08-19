<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1a1a2e; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
        .code { font-size: 32px; font-weight: bold; color: #4a90d9; letter-spacing: 8px; text-align: center; margin: 20px 0; padding: 20px; background: white; border-radius: 8px; }
        .note { font-size: 14px; color: #666; margin-top: 20px; }
        .footer { margin-top: 20px; font-size: 12px; color: #999; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>ИБ Комплаенс</h1>
    </div>
    <div class="content">
        @if($purpose === 'register')
            <h2>Подтверждение регистрации</h2>
            <p>Спасибо за регистрацию! Введите этот код для подтверждения вашего email:</p>
        @elseif($purpose === 'change_email')
            <h2>Подтверждение смены email</h2>
            <p>Введите этот код для подтверждения смены email:</p>
        @elseif($purpose === 'reset_password')
            <h2>Сброс пароля</h2>
            <p>Введите этот код для сброса пароля:</p>
        @endif

        <div class="code">{{ $code }}</div>

        <p class="note">
            Код действителен в течение 15 минут.<br>
            Если вы не запрашивали этот код, проигнорируйте это письмо.
        </p>
    </div>
    <div class="footer">
        <p>ИБ Комплаенс — система управления соответствием в области информационной безопасности</p>
    </div>
</div>
</body>
</html>
