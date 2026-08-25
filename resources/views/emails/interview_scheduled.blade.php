<!DOCTYPE html>
<html>

<head>
    <title>Interview Scheduled - {{ config('app.name') }}</title>
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

    ul {
        list-style-type: none;
        padding-left: 0;
    }

    ul li {
        margin-bottom: 10px;
    }
    </style>
</head>

<body>
    <div class="email-container">

        <div class="email-header">
            <img src="{{ config('app.url') }}/media/krstlogo.png" alt="{{ config('app.name') }} Logo">
            <div>Interview Scheduled</div>
        </div>

        <div class="email-body">
            <p>Hello, <strong>{{ $applicantName }}</strong>,</p>
            <p>We’re excited to inform you that an interview has been scheduled for your application to the
                <strong>{{ $jobTitle }}</strong> position at {{ config('app.name') }}.
            </p>
            <p><strong>Interview Details:</strong></p>
            <ul>
                <li><strong>Date:</strong> {{ $interviewDate }}</li>
                <li><strong>Time:</strong> {{ $interviewTime }}</li>
                <li><strong>Location:</strong> {{ $location }}</li>
                <li><strong>Type:</strong> {{ ucfirst($type) }}</li>
                @if($meetingLink)
                <li><strong>Meeting Link:</strong> <a href="{{ $meetingLink }}">{{ $meetingLink }}</a></li>
                @endif
            </ul>
            <p>Please let us know if you have any questions or need to reschedule by replying to this email.</p>
        </div>

        <div class="email-footer">
            Best Regards, <br>
            <strong>{{ config('app.name') }} Recruitment Team</strong> <br>
            <em>Building your career, step by step</em> <br>
            <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
        </div>
    </div>
</body>

</html>
