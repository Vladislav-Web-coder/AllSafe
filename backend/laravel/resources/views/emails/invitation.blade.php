<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1a1a2e; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
        .button { display: inline-block; padding: 12px 30px; background: #4a90d9; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
        .button:hover { background: #357abd; }
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
        <h2>Приглашение в организацию</h2>

        <p>
            <strong>{{ $inviterName }}</strong> приглашает вас присоединиться к организации
            <strong>«{{ $organizationName }}»</strong> в роли <strong>{{ $role }}</strong>.
        </p>

        <p>Для принятия приглашения нажмите на кнопку:</p>

        <a href="{{ $acceptUrl }}" class="button">Принять приглашение</a>

        <p class="note">
            Ссылка действительна в течение 7 дней.<br>
            Если вы не ожидали этого приглашения, проигнорируйте это письмо.
        </p>
    </div>
    <div class="footer">
        <p>ИБ Комплаенс — система управления соответствием в области информационной безопасности</p>
    </div>
</div>
</body>
</html>
