<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Activation Link Sent — {{ $tenant->school_name }}</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
<div class="max-w-md mx-auto py-16 px-4">
    <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
        <div class="text-4xl mb-3">✉️</div>
        <h1 class="text-2xl font-bold mb-2">Activation link sent</h1>
        <p class="text-sm text-slate-600">
            Check your inbox at <strong>{{ $email }}</strong>. The link will let you set a password and access the parent portal at
            <code class="bg-slate-100 px-1 rounded">/parent</code>.
        </p>
        <p class="text-xs text-slate-500 mt-4">The link expires in 7 days. If you don't see the email, check spam.</p>
    </div>
</div>
</body>
</html>
