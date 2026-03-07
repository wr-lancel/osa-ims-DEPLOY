<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; line-height: 1.5; color: #1f2937; margin: 0; padding: 0; background: #f3f4f6; }
        .wrapper { max-width: 560px; margin: 0 auto; padding: 24px; }
        .card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        h1 { font-size: 1.125rem; font-weight: 600; margin: 0 0 16px; color: #111827; }
        .message { margin: 0 0 24px; white-space: pre-wrap; }
        .btn { display: inline-block; padding: 10px 20px; background: #2563eb; color: #fff !important; text-decoration: none; border-radius: 8px; font-weight: 500; font-size: 0.875rem; }
        .footer { margin-top: 24px; font-size: 0.8125rem; color: #6b7280; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <h1>{{ $title }}</h1>
            <div class="message">{{ $body }}</div>
            <a href="{{ $viewInSystemUrl }}" class="btn">View in system</a>
            <p class="footer">This is an automated message from {{ config('app.name') }}. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
