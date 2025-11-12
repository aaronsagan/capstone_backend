<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <style>
    body { font-family: Arial, sans-serif; background:#f6f7f9; padding:24px; }
    .box { max-width:560px; margin:0 auto; background:#fff; border-radius:8px; padding:24px; }
    .pill { display:inline-block; padding:4px 10px; border-radius:999px; color:#fff; font-size:12px; margin-bottom:12px; }
    .success{ background:#16a34a; } .warning{ background:#ca8a04; } .info{ background:#2563eb; }
    .title{ font-weight:700; font-size:18px; margin:0 0 8px; }
    .msg{ font-size:14px; line-height:1.6; margin:0; }
    .footer{ margin-top:16px; color:#6b7280; font-size:12px; }
  </style>
</head>
<body>
  <div class="box">
    @php $typeClass = ($type ?? 'info'); @endphp
    <div class="pill {{$typeClass}}">{{ ucfirst($typeClass) }}</div>
    <h1 class="title">Hello {{ $user_name ?? 'there' }},</h1>
    <p class="msg">{!! nl2br(e($alert_message ?? 'You have a new notification.')) !!}</p>
    <div class="footer">&copy; {{ date('Y') }} CharityHub</div>
  </div>
</body>
</html>
