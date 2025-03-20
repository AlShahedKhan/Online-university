<!DOCTYPE html>
<html>
<head>
    <title>Professor Account Created</title>
</head>
<body>
    <h2>Welcome, {{ $name }}!</h2>
    <p>Your professor account has been created successfully.</p>
    <p><strong>Email:</strong> {{ $email }}</p>
    <p><strong>Temporary Password:</strong> {{ $password }}</p>
    <p>You can log in to your account using the following link:</p>
    <a href="{{ $login_url }}">Login Here</a>
    <p>It is recommended to change your password after logging in.</p>
    <br>
    <p>Best Regards,</p>
    <p>Online University</p>
</body>
</html>
