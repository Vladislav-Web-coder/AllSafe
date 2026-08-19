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
        <h2>Сброс пароля</h2>

        <p>Вы запросили сброс пароля. Нажмите на кнопку для перехода к форме сброса:</p>

        <a href="{{ $resetUrl }}" class="button">Сбросить пароль</a>

        <p class="note">
            Ссылка действительна в течение 1 часа.<br>
            Если вы не запрашивали сброс пароля, проигнорируйте это письмо.
        </p>
    </div>
    <div class="footer">
        <p>ИБ Комплаенс — система управления соответствием в области информационной безопасности</p>
    </div>
</div>
</body>
</html>
