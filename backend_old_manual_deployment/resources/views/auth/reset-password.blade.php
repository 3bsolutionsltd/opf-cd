<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Password - OPF-CD</title>
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
        <div class="max-w-md w-full space-y-8" x-data="resetPasswordForm('{{ $token }}', '{{ $email }}')">
            <!-- Logo/Header -->
            <div>
                <div class="mx-auto h-16 w-16 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center">
                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h2 class="mt-6 text-center text-3xl font-bold tracking-tight">
                    Reset Password
                </h2>
                <p class="mt-2 text-center text-sm text-gray-400">
                    Choose a new password for your account
                </p>
            </div>

            <!-- Form -->
            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-8 space-y-6">

                    <!-- Success Message -->
                    <div x-show="successMessage" x-cloak class="rounded-lg bg-green-500/10 border border-green-500/30 p-4">
                        <p class="text-sm text-green-400" x-text="successMessage"></p>
                        <p class="text-sm text-gray-400 mt-2">
                            <a href="/login" class="text-indigo-400 hover:text-indigo-300">Go to login &rarr;</a>
                        </p>
                    </div>

                    <!-- Error Message -->
                    <div x-show="errorMessage" x-cloak class="rounded-lg bg-red-500/10 border border-red-500/30 p-4">
                        <p class="text-sm text-red-400" x-text="errorMessage"></p>
                    </div>

                    <template x-if="!done">
                        <div class="space-y-6">
                            <!-- Email (hidden, carried through) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Email Address</label>
                                <input
                                    type="email"
                                    x-model="email"
                                    disabled
                                    class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-gray-400 opacity-75"
                                >
                            </div>

                            <!-- New Password -->
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">
                                    New Password
                                </label>
                                <input
                                    id="password"
                                    type="password"
                                    required
                                    x-model="password"
                                    class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-gray-100 placeholder-gray-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all"
                                    placeholder="Minimum 8 characters"
                                >
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-2">
                                    Confirm Password
                                </label>
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    required
                                    x-model="passwordConfirmation"
                                    class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-gray-100 placeholder-gray-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all"
                                    placeholder="Repeat your password"
                                >
                            </div>

                            <!-- Submit Button -->
                            <div>
                                <button
                                    type="submit"
                                    :disabled="loading"
                                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg text-sm font-medium text-white bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span x-show="!loading">Reset Password</span>
                                    <span x-show="loading" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Resetting...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </template>
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
        function resetPasswordForm(token, email) {
            return {
                token: token,
                email: email,
                password: '',
                passwordConfirmation: '',
                loading: false,
                done: false,
                successMessage: '',
                errorMessage: '',

                async submit() {
                    this.errorMessage = '';
                    this.successMessage = '';

                    if (this.password !== this.passwordConfirmation) {
                        this.errorMessage = 'Passwords do not match.';
                        return;
                    }

                    if (this.password.length < 8) {
                        this.errorMessage = 'Password must be at least 8 characters.';
                        return;
                    }

                    this.loading = true;

                    try {
                        const response = await fetch('/reset-password', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                email: this.email,
                                token: this.token,
                                password: this.password,
                                password_confirmation: this.passwordConfirmation,
                            }),
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.successMessage = data.message;
                            this.done = true;
                        } else {
                            this.errorMessage = data.message || 'Reset failed. Please request a new link.';
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
