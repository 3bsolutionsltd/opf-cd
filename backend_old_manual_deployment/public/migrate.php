<?php
/**
 * Web Migration Script
 * 
 * Run this ONCE via browser: https://opf-cd.3bs.ltd/migrate.php
 * Then DELETE this file immediately!
 * 
 * This script:
 * 1. Runs all database migrations
 * 2. Seeds production data (roles, permissions, default admin)
 * 3. Displays setup information
 */

// Security check - only run if not already migrated
$migrationLockFile = __DIR__ . '/../storage/.migration_complete';
if (file_exists($migrationLockFile)) {
    die("⚠️ Migrations already completed. Delete this file for security!");
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>OPF-CD Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: #28a745; padding: 10px; background: #d4edda; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border-radius: 4px; margin: 10px 0; }
        .info { color: #0056b3; padding: 10px; background: #cce5ff; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; padding: 10px; background: #fff3cd; border-radius: 4px; margin: 10px 0; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
        .output { max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 OPF-CD Database Setup</h1>
        
<?php

try {
    // Load Laravel
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    
    echo '<div class="info">✓ Laravel loaded successfully</div>';
    
    // Test database connection
    try {
        $pdo = DB::connection()->getPdo();
        echo '<div class="success">✓ Database connection successful</div>';
        echo '<pre>Database: ' . DB::connection()->getDatabaseName() . '</pre>';
    } catch (\Exception $e) {
        throw new Exception('Database connection failed: ' . $e->getMessage());
    }
    
    echo '<div class="output">';
    echo '<h3>Running Migrations...</h3>';
    
    // Capture output
    ob_start();
    
    // Run migrations
    $exitCode = $kernel->call('migrate', [
        '--force' => true,
        '--verbose' => true
    ]);
    
    $migrationOutput = ob_get_clean();
    
    if ($exitCode === 0) {
        echo '<div class="success">✓ Migrations completed successfully!</div>';
        echo '<pre>' . htmlspecialchars($migrationOutput) . '</pre>';
        
        // Run production seeder
        echo '<h3>Running Production Seeder...</h3>';
        ob_start();
        
        $seederExitCode = $kernel->call('db:seed', [
            '--class' => 'ProductionSeeder',
            '--force' => true
        ]);
        
        $seederOutput = ob_get_clean();
        
        if ($seederExitCode === 0) {
            echo '<div class="success">✓ Production seeder completed successfully!</div>';
            echo '<pre>' . htmlspecialchars($seederOutput) . '</pre>';
            
            // Create lock file
            file_put_contents($migrationLockFile, date('Y-m-d H:i:s'));
            
            // Display success information
            echo '<div class="success">';
            echo '<h2>✅ Setup Complete!</h2>';
            echo '<h3>Default Admin Account:</h3>';
            echo '<ul>';
            echo '<li><strong>Email:</strong> admin@opf-cd.test</li>';
            echo '<li><strong>Password:</strong> password123</li>';
            echo '</ul>';
            echo '<h3>Roles Created:</h3>';
            echo '<ul>';
            echo '<li>Admin (Full access)</li>';
            echo '<li>Finance (Financial operations)</li>';
            echo '<li>Project Manager (Project management)</li>';
            echo '<li>Viewer (Read-only access)</li>';
            echo '</ul>';
            echo '</div>';
            
            echo '<div class="warning">';
            echo '<h3>⚠️ IMPORTANT SECURITY STEPS:</h3>';
            echo '<ol>';
            echo '<li><strong>DELETE THIS FILE IMMEDIATELY!</strong> (migrate.php)</li>';
            echo '<li>Login and change the default admin password</li>';
            echo '<li>Verify APP_DEBUG=false in .env</li>';
            echo '<li>Remove any test scripts from backend folder</li>';
            echo '</ol>';
            echo '</div>';
            
            echo '<div class="info">';
            echo '<h3>Next Steps:</h3>';
            echo '<ol>';
            echo '<li><a href="/login" class="btn">Go to Login Page</a></li>';
            echo '<li>Login with admin credentials</li>';
            echo '<li>Change your password immediately</li>';
            echo '<li>Start using the system!</li>';
            echo '</ol>';
            echo '</div>';
            
        } else {
            throw new Exception('Seeder failed with exit code: ' . $seederExitCode);
        }
        
    } else {
        throw new Exception('Migration failed with exit code: ' . $exitCode . "\n" . $migrationOutput);
    }
    
    echo '</div>';
    
} catch (\Exception $e) {
    echo '</div>';
    echo '<div class="error">';
    echo '<h3>❌ Setup Failed</h3>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<h4>Troubleshooting:</h4>';
    echo '<ul>';
    echo '<li>Check database credentials in .env file</li>';
    echo '<li>Ensure database exists and is accessible</li>';
    echo '<li>Verify file permissions on storage/ folder</li>';
    echo '<li>Check storage/logs/laravel.log for details</li>';
    echo '</ul>';
    echo '<h4>Error Details:</h4>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
}

?>

    </div>
</body>
</html>
