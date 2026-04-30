<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot Password - OPF-CD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-full bg-slate-950 text-gray-100">
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8" x-data="forgotPasswordForm()">
            <!-- Logo/Header -->
            <div>
                <div class="mx-auto h-16 w-16 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center">
                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                </div>
                <h2 class="mt-6 text-center text-3xl font-bold tracking-tight">
                    Forgot Password
                </h2>
                <p class="mt-2 text-center text-sm text-gray-400">
                    Enter your email and we'll send you a reset link
                </p>
            </div>

            <!-- Form -->
            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-8 space-y-6">

                    <!-- Success Message -->
                    <div x-show="successMessage" x-cloak class="rounded-lg bg-green-500/10 border border-green-500/30 p-4">
                        <p class="text-sm text-green-400" x-text="successMessage"></p>
                    </div>

                    <!-- Error Message -->
                    <div x-show="errorMessage" x-cloak class="rounded-lg bg-red-500/10 border border-red-500/30 p-4">
                        <p class="text-sm text-red-400" x-text="errorMessage"></p>
                    </div>

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                            Email Address
                        </label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            required
                            x-model="email"
                            :disabled="sent"
                            class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-gray-100 placeholder-gray-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all disabled:opacity-50"
                            placeholder="you@company.com"
                        >
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button
                            type="submit"
                            :disabled="loading || sent"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg text-sm font-medium text-white bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span x-show="!loading && !sent">Send Reset Link</span>
                            <span x-show="loading" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Sending...
                            </span>
                            <span x-show="sent && !loading">Link Sent</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Back to Login -->
            <p class="text-center text-sm text-gray-400">
                <a href="/login" class="font-medium text-indigo-400 hover:text-indigo-300 transition-colors">
                    &larr; Back to login
                </a>
            </p>
        </div>
    </div>

    <script>
        function forgotPasswordForm() {
            return {
                email: '',
                loading: false,
                sent: false,
                successMessage: '',
                errorMessage: '',

                async submit() {
                    this.loading = true;
                    this.errorMessage = '';
                    this.successMessage = '';

                    try {
                        const response = await fetch('/forgot-password', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ email: this.email }),
                        });

                        const data = await response.json();

                        if (response.status === 429) {
                            this.errorMessage = data.message;
                        } else {
                            this.successMessage = data.message;
                            this.sent = true;
                        }
                    } catch (e) {
                        this.errorMessage = 'An unexpected error occurred. Please try again.';
                    } finally {
                        this.loading = false;
                    }
                }
            };
        }
    </script>
</body>
</html>
