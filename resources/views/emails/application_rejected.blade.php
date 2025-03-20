<!DOCTYPE html>
<html>
<head>
    <title>Application Rejected</title>
</head>
<body>
    <h1>Dear {{ $application->full_name }},</h1>
    <p>We regret to inform you that your application for {{ $application->program }} has been rejected.</p>
    <p>For further inquiries, please contact our support team.</p>
    <br>
    <p>Best Regards,</p>
    <p>{{ config('app.name') }}</p>
</body>
</html>
