<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بازیابی رمز عبور</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f4f4f5; margin: 0; padding: 24px; }
        .card { background: #fff; border-radius: 12px; max-width: 480px; margin: 0 auto; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        h1 { font-size: 18px; color: #1f2937; margin-bottom: 8px; }
        p { font-size: 14px; color: #4b5563; line-height: 1.7; }
        .token { font-family: monospace; font-size: 20px; font-weight: bold; letter-spacing: 4px; color: #d97706; background: #fef3c7; border-radius: 8px; padding: 12px 20px; display: inline-block; margin: 16px 0; }
        .btn { display: inline-block; background: #d97706; color: #fff; text-decoration: none; border-radius: 8px; padding: 12px 28px; font-size: 14px; font-weight: bold; margin-top: 8px; }
        .note { font-size: 12px; color: #9ca3af; margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 16px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>بازیابی رمز عبور</h1>
        <p>سلام {{ $user->full_name }}،</p>
        <p>برای بازیابی رمز عبور حساب کاربری خود، از توکن زیر استفاده کنید:</p>

        <div style="text-align:center">
            <span class="token">{{ $token }}</span>
        </div>

        <p>یا روی دکمه زیر کلیک کنید:</p>
        <div style="text-align:center; margin: 8px 0 16px">
            <a href="{{ $resetUrl }}" class="btn">تغییر رمز عبور</a>
        </div>

        <div class="note">
            <p>این توکن تا <strong>۱۵ دقیقه</strong> اعتبار دارد و فقط یک بار قابل استفاده است.</p>
            <p>اگر شما این درخواست را ارسال نکرده‌اید، این ایمیل را نادیده بگیرید.</p>
        </div>
    </div>
</body>
</html>
