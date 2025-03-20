<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Account Created</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-lg mx-auto bg-white border border-gray-300 rounded-lg shadow-lg">
        <!-- Header -->
        <div class="bg-green-600 text-white text-center py-6">
            <h1 class="text-2xl font-bold">Welcome to Orfa AI</h1>
        </div>
        <!-- Body -->
        <div class="p-6 text-gray-800">
            <h2 class="text-xl font-semibold mb-4">Hello, {{ $user['first_name'] }} {{ $user['last_name'] }}!</h2>
            <p class="text-base leading-6 mb-4">
                Your account has been successfully created by an administrator. You can now log in and start using the platform.
            </p>
            <p class="text-base leading-6 mb-4">
                Here are your account details:
            </p>
            <ul class="text-base leading-6 mb-6">
                <li><strong>Email:</strong> {{ $user['email'] }}</li>
                <li><strong>Password:</strong> {{ $user['password'] }} </li>
            </ul>
            <p class="text-base leading-6 mb-6">
                We recommend that you change your password when you first login. <br>
                To get started, click the button below to log in:
            </p>
            <div class="text-center">
                <a href="https://orfa.ai/login" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    Log In
                </a>
            </div>
            <p class="text-base leading-6 mt-6">
                If you have any questions, feel free to contact our support team.
            </p>
            <p class="text-base leading-6 mt-4">
                Best regards,<br>
                <span class="font-semibold">Orfa AI Team</span>
                <br>
                <span><a href="https://orfa.ai">https://orfa.ai</a></span>
            </p>
        </div>
        <!-- Footer -->
        <div class="bg-gray-100 text-center py-4 text-sm text-gray-600">
            <p>&copy; {{ date('Y') }} Orfa AI. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
