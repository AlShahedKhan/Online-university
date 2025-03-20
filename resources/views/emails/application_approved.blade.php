<!DOCTYPE html>
<html>
<head>
    <title>Application Approved - Login Details</title>
</head>
<body>
    <h1>Dear {{ $application->first_name }} {{ $application->last_name }},</h1>
    <p>Congratulations! Your application for the <strong>{{ $application->program }}</strong> has been approved.</p>

    <p>You have been assigned to Batch ID: <strong>{{ $batch_id }} with Student ID: {{ $student_id }}</strong></p>

    <p>Your account has been created. You can log in using the following credentials:</p>
    <p><strong>Email:</strong> {{ $application->email }}</p>
    <p><strong>Password:</strong> {{ $password }}</p>

    <p>Please change your password after logging in.</p>

    <br>
    <p>Best Regards,</p>
    <p>{{ config('app.name') }}</p>
</body>
</html>
