
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>P9 Form Notification</title>
</head>
<body>
    <h1>P9 Form Notification</h1>
    <p>Dear {{ $user->name ?? 'Employee' }},</p>
    <p>Please find your P9 form for the year {{ $year }} attached.</p>
    <p>Best regards,<br>Your HR Team</p>
</body>
</html>
