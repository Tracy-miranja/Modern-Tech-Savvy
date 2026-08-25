<!DOCTYPE html>
<html>

<head>
    <title>Application Received - {{ config('app.name') }}</title>
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

        <div class="email-header">
            <img src="{{ config('app.url') }}/media/krstlogo.png" alt="{{ config('app.name') }} Logo">
            <div>Application Received</div>
        </div>

        <div class="email-body">
            <p>Hello, <strong>{{ $applicantName }}</strong>,</p>
            <p>Thank you for applying to the <strong>{{ $jobTitle }}</strong> position at {{ config('app.name') }}! We
                received your application on {{ $applicationDate }}.</p>
            <p>Our team is reviewing your submission, and we’ll get back to you soon with the next steps.</p>
            <p>We appreciate your interest in joining us!</p>
        </div>

        <div class="email-footer">
            Best Regards, <br>
            <strong>{{ config('app.name') }} Recruitment Team</strong> <br>
            <em>Welcoming you to our community</em> <br>
            <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
        </div>
    </div>
</body>

</html>
