<!DOCTYPE html>
<html>

<head>
    <title>Application Update - {{ config('app.name') }}</title>
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
            <img src="{{ config('app.url') }}/media/krstlogo.pnglogo.png" alt="{{ config('app.name') }} Logo">
            <div>Application Update</div>
        </div>

        <!-- Body -->
        <div class="email-body">
            <p>Hello, <strong>{{ $applicantName }}</strong>,</p>
            <p>We have an update regarding your application for the <strong>{{ $jobTitle }}</strong> position at
                {{ config('app.name') }}:
            </p>
            <p>
                @switch(trim(strtolower($stage)))
                @case('applied')
                Your application has been successfully received and is under review!
                @break
                @case('shortlisted')
                Great news! You’ve been shortlisted, and we’re excited to move forward with you.
                @break
                @case('interview')
                We’ve scheduled an interview for you—stay tuned for the details!
                @break
                @case('in_progress')
                We're reveiwing your application and the team is working to update you as soon as possible.
                @break
                @case('hired')
                Welcome aboard! We're excited to have you join the team.
                @break
                @case('rejected')
                Thank you for applying. Unfortunately, we won’t be moving forward this time, but we appreciate your
                interest.
                @break
                @default
                Your application status has been updated to "{{ ucfirst($stage) }}." We’ll keep you posted on the next
                steps.
                @endswitch
            </p>

            <p>Feel free to reach out if you have any questions!</p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            Best Regards, <br>
            <strong>{{ config('app.name') }} Recruitment Team</strong> <br>
            <em>Supporting your journey with us</em> <br>
            <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
        </div>
    </div>
</body>

</html>
