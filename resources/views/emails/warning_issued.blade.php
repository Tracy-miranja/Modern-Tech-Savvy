<!DOCTYPE html>
<html>

<head>
    <title>Warning Issued - {{ config('app.name') }}</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f7fc;
        margin: 0;
        padding: 0;
    }

    .email-container {
        max-width: 600px;
        margin: 20px auto;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .email-header {
        background-color: #ffffff;
        color: #ffffff;
        text-align: center;
        padding: 20px;
        font-size: 24px;
        font-weight: bold;
    }

    .email-header img {
        max-width: 150px;
        margin-bottom: 10px;
    }

    .email-body {
        padding: 20px;
        color: #333333;
        line-height: 1.6;
    }

    .email-body p {
        margin-bottom: 15px;
    }

    .email-footer {
        background-color: #f4f7fc;
        text-align: center;
        padding: 15px;
        font-size: 14px;
        color: #666666;
    }

    .email-footer a {
        color: #004a99;
        text-decoration: none;
    }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <img src="{{ config('app.url') }}/media/krstlogo.png.png" alt="{{ config('app.name') }} Logo">
            <div>Warning Issued</div>
        </div>

        <!-- Body -->
        <div class="email-body">
            <p>Hello, <strong>{{ $warning->employee->user->name }}</strong>,</p>
            <p>We regret to inform you that you have been issued a warning for the following reason:</p>
            <p><strong>Reason:</strong> {{ $warning->reason }}</p>
            <p><strong>Date Issued:</strong> {{ $warning->issue_date }}</p>
            <p><strong>Description:</strong> {{ $warning->description ?? 'No additional description provided.' }}</p>
            <p>Please address this issue promptly and contact your supervisor for further discussion if needed.</p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            Best Regards, <br>
            <strong>{{ config('app.name') }} HR Team</strong> <br>
            <em>Supporting your professional growth</em> <br>
            <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
        </div>
    </div>
</body>

</html>
