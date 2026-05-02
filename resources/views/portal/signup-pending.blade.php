<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Signup pending approval — KynexEdu</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
<div class="max-w-md mx-auto py-16 px-4">
    <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
        <div class="text-4xl mb-3">⏳</div>
        <h1 class="text-2xl font-bold mb-2">Awaiting Kynex Solutions approval</h1>
        <p class="text-sm text-slate-600 mt-2">
            Thank you. Your school registration has been received and is queued for review by Kynex Solutions.
            Once an account manager approves it and selects a plan (trial or paid), you will receive a confirmation
            email at <strong>{{ $email }}</strong> with login instructions.
        </p>
        <p class="text-xs text-slate-500 mt-6">
            Approvals are typically processed within one business day. For urgent requests, contact
            <a href="mailto:hello@kynexsolutions.com" class="text-blue-600 hover:underline">hello@kynexsolutions.com</a>.
        </p>
    </div>
</div>
</body>
</html>
