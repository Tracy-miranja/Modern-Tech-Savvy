<!DOCTYPE html>
<html>

<head>
    <title>Certificate Expiring Soon - {{ config('app.name') }}</title>
    <style>
    body { font-family: Arial, sans-serif; background-color: #f4f7fc; margin: 0; padding: 0; }
    .email-container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); overflow: hidden; }
    .email-header { background-color: #ffffff; text-align: center; padding: 20px; font-size: 24px; font-weight: bold; }
    .email-header img { max-width: 150px; margin-bottom: 10px; }
    .email-body { padding: 20px; color: #333333; line-height: 1.6; }
    .email-body p { margin-bottom: 15px; }
    .email-footer { background-color: #f4f7fc; text-align: center; padding: 15px; font-size: 14px; color: #666666; }
    .email-footer a { color: #004a99; text-decoration: none; }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="email-header">
            <img src="{{ config('app.url') }}/media/krstlogo.png" alt="{{ config('app.name') }} Logo">
            <div>Certificate Expiring Soon</div>
        </div>
        <div class="email-body">
            <p>Hello, <strong>{{ optional($enrollment->employee->user)->name }}</strong>,</p>
            <p>Your certificate for <strong>{{ $enrollment->course->title }}</strong> is expiring soon.</p>
            <p><strong>Certificate Number:</strong> {{ $enrollment->certificate_number ?? 'N/A' }}</p>
            <p><strong>Expiry Date:</strong> {{ optional($enrollment->certificate_expiry_date)->format('jS M Y') }}</p>
            <p>Please arrange for renewal or re-certification ahead of this date.</p>
        </div>
        <div class="email-footer">
            Best Regards, <br>
            <strong>{{ config('app.name') }} HR Team</strong> <br>
            <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
        </div>
    </div>
</body>

</html>
