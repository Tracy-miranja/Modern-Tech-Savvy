<!DOCTYPE html>
<html>

<head>
    <style>
        .container {
            max-width: 600px;
            margin: auto;
            font-family: Arial, sans-serif;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .header {
            background: #068f6d;
            color: white;
            padding: 20px;
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

        .body {
            padding: 20px;
            background: #ffffff;
            text-align: center;
        }

        .body p {
            margin-bottom: 15px;
            color: #333;
            font-size: 16px;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #068f6d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 16px;
        }

        .footer {
            background: #e0e0e0;
            padding: 10px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img class="logo" src="{{ config('app.url') }}/media/krstlogo.png.png" alt="{{ config('app.name') }} Logo">
            <h2 class="title">Your Business Status: {{ ucfirst($status) }}</h2>
        </div>

        <div class="body">
            <p>Your business, <strong>{{ $business->company_name }}</strong>, has been <strong>{{ $status }}</strong>.
            </p>

            <p><strong>Remarks:</strong> {{ $remarks }}</p>

            @if($status === 'deactivated')
            <p>Please contact the administrator for further details.</p>
            @endif

            <a href="{{ $loginUrl }}" class="button">Log In</a>
        </div>

        <div class="footer">
            <p>&copy; {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
