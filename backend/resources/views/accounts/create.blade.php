<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Account - OPF-CD</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        option {
            background-color: rgb(15 23 42);
            color: rgb(243 244 246);
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .card-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .card-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .card-body {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
            font-size: 14px;
        }

        .required {
            color: #ef4444;
        }

        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            font-family: inherit;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .help-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 6px;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .error-message {
            background: #fee;
            color: #c00;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success-message {
            background: #efe;
            color: #060;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .breadcrumb {
            margin-bottom: 20px;
            text-align: center;
        }

        .breadcrumb a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            font-size: 14px;
        }

        .breadcrumb a:hover {
            opacity: 1;
            text-decoration: underline;
        }

        .breadcrumb span {
            color: white;
            opacity: 0.6;
            margin: 0 8px;
        }
    </style>
</head>
<body x-data="accountCreate()">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Dashboard</a>
            <span>→</span>
            <a href="/accounts">Accounts</a>
            <span>→</span>
            <span style="opacity: 1;">Create Account</span>
        </div>
        <div class="card">
            <div class="card-header">
                <h1>Create New Account</h1>
                <p>Add a bank account, mobile money, or cash account</p>
            </div>

            <div class="card-body">
                <div x-show="error" x-text="error" class="error-message"></div>
                <div x-show="success" x-text="success" class="success-message"></div>

                <form @submit.prevent="submitAccount">
                    <div class="form-group">
                        <label for="name">Account Name <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="name" 
                            x-model="form.name" 
                            placeholder="e.g., Stanbic Bank Business Account"
                            required
                            maxlength="255"
                        >
                        <div class="help-text">Descriptive name for this account</div>
                    </div>

                    <div class="form-group">
                        <label for="type">Account Type <span class="required">*</span></label>
                        <select id="type" x-model="form.type" required>
                            <option value="">-- Select Type --</option>
                            <option value="bank">Bank Account</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="cash">Cash</option>
                        </select>
                        <div class="help-text">Type of account for tracking purposes</div>
                    </div>

                    <div class="form-group">
                        <label for="currency">Currency <span class="required">*</span></label>
                        <select id="currency" x-model="form.currency" required>
                            <option value="">-- Select Currency --</option>
                            <option value="UGX">UGX (Ugandan Shilling)</option>
                            <option value="USD">USD (US Dollar)</option>
                        </select>
                        <div class="help-text">Currency for this account</div>
                    </div>

                    <div class="form-group">
                        <label for="opening_balance">Opening Balance <span class="required">*</span></label>
                        <input 
                            type="number" 
                            id="opening_balance" 
                            x-model.number="form.opening_balance" 
                            step="0.01"
                            min="0"
                            required
                        >
                        <div class="help-text">Initial balance when starting to track this account (must be ≥ 0)</div>
                    </div>

                    <div class="button-group">
                        <a href="/accounts" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" :disabled="submitting">
                            <span x-show="!submitting">Create Account</span>
                            <span x-show="submitting">Creating...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function accountCreate() {
            return {
                form: {
                    name: '',
                    type: '',
                    currency: '',
                    opening_balance: 0
                },
                submitting: false,
                error: '',
                success: '',

                async submitAccount() {
                    this.submitting = true;
                    this.error = '';
                    this.success = '';

                    try {
                        const response = await fetch('/api/accounts', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.form)
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            this.success = data.message;
                            setTimeout(() => {
                                window.location.href = '/accounts';
                            }, 1500);
                        } else {
                            this.error = data.message || 'Failed to create account. Please try again.';
                        }
                    } catch (error) {
                        console.error('Error creating account:', error);
                        this.error = 'An error occurred. Please try again.';
                    } finally {
                        this.submitting = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
