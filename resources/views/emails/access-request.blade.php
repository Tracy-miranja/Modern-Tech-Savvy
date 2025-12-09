<!DOCTYPE html>
<html>

<head>
    <style>
    .container {
        max-width: 600px;
        margin: auto;
        font-family: Arial, sans-serif;
    }

    .header {
        background: #068f6d;
        color: white;
        padding: 20px;
        text-align: center;
    }

    .body {
        padding: 20px;
        background: #f9f9f9;
    }

    .footer {
        background: #e0e0e0;
        padding: 10px;
        text-align: center;
    }

    .logo {
        display: block;
        margin: 0 auto 10px auto;
        max-width: 150px;
    }

    .title {
        margin: 0;
        font-size: 22px;
        font-weight: normal;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img class="logo" src="{{ config('app.url') }}/media/krstlogo.png" alt="{{ config('app.name') }} Logo">
            <h2 class="title">Your Business Status: {{ ucfirst($status) }}</h2>
        </div>
        <div class="body">
            <p>You have been granted access to {{ $business->company_name }}.</p>
            @if($tempPassword)
            <p>Your temporary password is: <strong>{{ $tempPassword }}</strong></p>
            <p>Please log in and change your password.</p>
            @endif
            <a href="{{ $loginUrl }}"
                style="display: inline-block; padding: 10px 20px; background: #068f6d; color: white; text-decoration: none;">Log
                In</a>
        </div>
        <div class="footer">
            <p>&copy; {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
