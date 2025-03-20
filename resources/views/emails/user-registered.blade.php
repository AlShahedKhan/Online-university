<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-lg mx-auto bg-white border border-gray-300 rounded-lg shadow-lg">

        <!-- Body -->
        <div class="p-6 text-gray-800">
            <h2 class="text-xl font-semibold mb-4">Hello, {{ $user['first_name'] }} {{ $user['last_name'] }}!</h2>
            <p class="text-base leading-6 mb-4">
                Thank you for registering on our platform. Your registration was successful, but is pending approval by the Orfa AI admin. You'll receive an email confirming your approval.
            </p>
            <p class="text-base leading-6 mb-6">
                If you have any questions or need assistance, feel free to reach out to our support team. We're here to help.
            </p>
            <p class="text-base leading-6">
                Best regards,<br>
                <span class="font-semibold">Orfa AI Team</span>
                <br>
                <span>www.orfa.ai</span>
            </p>
        </div>
        <!-- Footer -->
        <div class="bg-gray-100 text-center py-4 text-sm text-gray-600">
            <p>&copy; {{ date('Y') }} Orfa AI. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
